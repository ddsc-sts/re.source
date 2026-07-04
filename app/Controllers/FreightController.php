<?php

class FreightController
{
    private static function companyId(): int
    {
        return (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
    }

    private static function userId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    private static function negotiation(PDO $pdo, int $id, int $companyId, bool $lock = false): array
    {
        $sql = "SELECT n.*, l.title AS listing_title, l.unit,
                       buyer.nome_fantasia AS buyer_name, buyer.address_id AS buyer_address_id,
                       seller.nome_fantasia AS seller_name, seller.address_id AS seller_address_id,
                       CONCAT_WS(', ', NULLIF(seller_address.city, ''), NULLIF(seller_address.state, '')) AS origin_location,
                       CONCAT_WS(', ', NULLIF(buyer_address.city, ''), NULLIF(buyer_address.state, '')) AS destination_location,
                       p.responsible_for_freight
                FROM negotiations n
                INNER JOIN listings l ON l.id = n.listing_id
                INNER JOIN companies buyer ON buyer.id = n.buyer_company_id
                INNER JOIN companies seller ON seller.id = n.seller_company_id
                LEFT JOIN addresses buyer_address ON buyer_address.id = buyer.address_id
                LEFT JOIN addresses seller_address ON seller_address.id = seller.address_id
                LEFT JOIN proposals p ON p.id = (
                    SELECT p2.id FROM proposals p2 WHERE p2.negotiation_id = n.id
                    ORDER BY p2.id DESC LIMIT 1
                )
                WHERE n.id = ? AND (n.buyer_company_id = ? OR n.seller_company_id = ?)
                LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $companyId, $companyId]);
        $negotiation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$negotiation) throw new DomainException('Negociação não encontrada ou sem acesso.');
        return $negotiation;
    }

    private static function canChoose(array $negotiation, int $companyId): bool
    {
        return match ($negotiation['responsible_for_freight'] ?? 'buyer') {
            'seller' => (int) $negotiation['seller_company_id'] === $companyId,
            'shared' => in_array($companyId, [(int) $negotiation['buyer_company_id'], (int) $negotiation['seller_company_id']], true),
            default => (int) $negotiation['buyer_company_id'] === $companyId,
        };
    }

    private static function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405); exit('Método não permitido.');
        }
        if (!csrf_validate()) {
            flash('error', 'A sessão expirou. Recarregue a página.');
            redirect_to('/logistica');
        }
    }

    private static function recordStatus(PDO $pdo, int $freightId, string $status, string $description): void
    {
        $pdo->prepare(
            'INSERT INTO freight_status_history (freight_id, status, description, created_by_user_id)
             VALUES (?, ?, ?, ?)'
        )->execute([$freightId, $status, $description, self::userId() ?: null]);
    }

    private static function sendFreightEmail(PDO $pdo, int $companyId, string $subject, string $title, string $text, int $negotiationId): void
    {
        try {
            $stmt = $pdo->prepare(
                "SELECT email, name FROM users WHERE company_id = ? AND is_active = 1 AND deleted_at IS NULL
                 ORDER BY (role = 'admin_company') DESC, id ASC LIMIT 1"
            );
            $stmt->execute([$companyId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) return;
            require_once CONFIG_PATH . '/mailer.php';
            $baseUrl = rtrim((string) env('APP_URL', 'http://localhost/re.source'), '/');
            enviarEmailFluxo($target['email'], $target['name'], $subject, $title, $text,
                $baseUrl . '/frete/acompanhar?negociacao=' . $negotiationId);
        } catch (Throwable $error) {
            error_log('Falha no alerta de frete: ' . $error->getMessage());
        }
    }

    private static function createQuotes(PDO $pdo, array $negotiation): void
    {
        $negotiationId = (int) $negotiation['id'];
        $pdo->prepare("UPDATE freight_quotes SET status = 'expired' WHERE negotiation_id = ? AND status = 'active'")
            ->execute([$negotiationId]);

        $quantity = max(1, (float) ($negotiation['proposed_quantity'] ?? 1));
        $weightKg = match ($negotiation['unit']) {
            'ton' => $quantity * 1000,
            'kg' => $quantity,
            default => $quantity * 10,
        };
        $factor = min(10000, $weightKg) / 100;
        $options = [
            ['EcoLog Transportes', 'Econômico', 'rodoviario', round(79 + $factor * 1.25, 2), 8],
            ['Rota Circular', 'Standard', 'rodoviario', round(119 + $factor * 1.65, 2), 5],
            ['Verde Express', 'Expresso', 'expresso', round(189 + $factor * 2.20, 2), 2],
        ];
        $stmt = $pdo->prepare(
            "INSERT INTO freight_quotes
                (negotiation_id, provider_name, service_name, modality, price, delivery_days, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
        );
        foreach ($options as $option) $stmt->execute([$negotiationId, ...$option]);
    }

    public static function quote(): void
    {
        $negotiationId = filter_input(INPUT_GET, 'negociacao', FILTER_VALIDATE_INT);
        if (!$negotiationId) {
            flash('error', 'Negociação inválida.'); redirect_to('/conversas');
        }
        global $pdo;
        $companyId = self::companyId();
        try {
            $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId);
            if (!in_array($negotiation['status'], ['accepted', 'awaiting_freight'], true)) {
                throw new DomainException('O frete só pode ser escolhido depois do acordo mútuo.');
            }
            $stmt = $pdo->prepare("SELECT * FROM freights WHERE negotiation_id = ? LIMIT 1");
            $stmt->execute([$negotiationId]);
            $freight = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($freight) {
                redirect_to('/frete/acompanhar?negociacao=' . (int) $negotiationId);
            }
            $stmt = $pdo->prepare("SELECT * FROM freight_quotes WHERE negotiation_id = ? AND status = 'active' AND expires_at > NOW() ORDER BY price");
            $stmt->execute([$negotiationId]);
            $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$quotes) {
                self::createQuotes($pdo, $negotiation);
                $stmt->execute([$negotiationId]);
                $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $canChoose = self::canChoose($negotiation, $companyId);
            view('freight/quote', compact('negotiation', 'quotes', 'canChoose'));
        } catch (DomainException $error) {
            flash('warning', $error->getMessage()); redirect_to('/conversas');
        }
    }

    public static function contract(): void
    {
        self::requirePost();
        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        $quoteId = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
        global $pdo;
        $companyId = self::companyId();
        try {
            $pdo->beginTransaction();
            $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId, true);
            if ($negotiation['status'] !== 'accepted' || !self::canChoose($negotiation, $companyId)) {
                throw new DomainException('Sua empresa não pode contratar o frete desta negociação.');
            }
            $stmt = $pdo->prepare("SELECT * FROM freight_quotes WHERE id = ? AND negotiation_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1 FOR UPDATE");
            $stmt->execute([$quoteId, $negotiationId]);
            $quote = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$quote) throw new DomainException('Cotação inválida ou expirada.');
            $platformFee = round((float) $quote['price'] * 0.05, 2);
            $total = (float) $quote['price'] + $platformFee;
            $tracking = 'RS-' . date('ymd') . '-' . str_pad((string) $negotiationId, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $stmt = $pdo->prepare(
                "INSERT INTO freights
                    (negotiation_id, origin_address_id, destination_address_id, carrier_company_name,
                     service_name, modality, quote_value, platform_fee, total_value, delivery_days,
                     tracking_code, status, contracted_at, estimated_pickup, estimated_delivery)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'contracted', NOW(),
                         DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL ? DAY))"
            );
            $stmt->execute([
                $negotiationId, $negotiation['seller_address_id'], $negotiation['buyer_address_id'],
                $quote['provider_name'], $quote['service_name'], $quote['modality'], $quote['price'],
                $platformFee, $total, $quote['delivery_days'], $tracking, $quote['delivery_days'],
            ]);
            $freightId = (int) $pdo->lastInsertId();
            self::recordStatus($pdo, $freightId, 'contracted', 'Frete contratado e codigo de rastreamento gerado.');
            $pdo->prepare("UPDATE freight_quotes SET status = IF(id = ?, 'selected', 'expired') WHERE negotiation_id = ?")
                ->execute([$quoteId, $negotiationId]);
            $pdo->prepare("UPDATE negotiations SET status = 'awaiting_freight' WHERE id = ?")
                ->execute([$negotiationId]);
            $pdo->commit();
            flash('success', 'Frete contratado. O código de rastreamento já está disponível.');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível contratar o frete.');
            redirect_to('/frete?negociacao=' . (int) $negotiationId);
        }
        redirect_to('/frete/acompanhar?negociacao=' . (int) $negotiationId);
    }

    public static function show(): void
    {
        $negotiationId = filter_input(INPUT_GET, 'negociacao', FILTER_VALIDATE_INT);
        global $pdo;
        try {
            $negotiation = self::negotiation($pdo, (int) $negotiationId, self::companyId());
            $stmt = $pdo->prepare("SELECT * FROM freights WHERE negotiation_id = ? LIMIT 1");
            $stmt->execute([$negotiationId]);
            $freight = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$freight) throw new DomainException('Frete ainda não contratado.');
            $stmt = $pdo->prepare('SELECT * FROM freight_status_history WHERE freight_id = ? ORDER BY id ASC');
            $stmt->execute([$freight['id']]);
            $statusHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $deliveryCode = $_SESSION['delivery_code_once'][$freight['id']] ?? null;
            unset($_SESSION['delivery_code_once'][$freight['id']]);
            view('freight/show', compact('negotiation', 'freight', 'deliveryCode', 'statusHistory'));
        } catch (DomainException $error) {
            flash('warning', $error->getMessage()); redirect_to('/conversas');
        }
    }

    public static function startShipping(): void
    {
        self::requirePost();
        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        global $pdo;
        try {
            $pdo->beginTransaction();
            $negotiation = self::negotiation($pdo, (int) $negotiationId, self::companyId(), true);
            $stmt = $pdo->prepare("SELECT * FROM freights WHERE negotiation_id = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$negotiationId]);
            $freight = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$freight || !in_array($freight['status'], ['contracted', 'preparing'], true)) {
                throw new DomainException('Este frete não pode iniciar o transporte.');
            }
            $pdo->prepare("UPDATE freights SET status = 'in_transit', picked_up_at = NOW() WHERE id = ?")
                ->execute([$freight['id']]);
            $pdo->prepare("UPDATE negotiations SET status = 'shipping' WHERE id = ?")
                ->execute([$negotiationId]);
            self::recordStatus($pdo, (int) $freight['id'], 'in_transit', 'Coleta confirmada. Material em transporte.');
            $pdo->commit();
            flash('success', 'Coleta confirmada. O frete está em transporte.');
            foreach ([(int) $negotiation['buyer_company_id'], (int) $negotiation['seller_company_id']] as $recipientId) {
                self::sendFreightEmail($pdo, $recipientId, 'Coleta realizada — Re.Source', 'Frete em transporte',
                    'A coleta foi confirmada e o material esta a caminho do comprador.', (int) $negotiationId);
            }
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível iniciar o transporte.');
        }
        redirect_to('/frete/acompanhar?negociacao=' . (int) $negotiationId);
    }

    public static function generateDeliveryCode(): void
    {
        self::requirePost();
        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        global $pdo;
        $companyId = self::companyId();
        try {
            $pdo->beginTransaction();
            $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId, true);
            if ((int) $negotiation['buyer_company_id'] !== $companyId) {
                throw new DomainException('Somente o comprador pode gerar o código de recebimento.');
            }
            $stmt = $pdo->prepare("SELECT * FROM freights WHERE negotiation_id = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$negotiationId]);
            $freight = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$freight || !in_array($freight['status'], ['in_transit', 'out_for_delivery'], true)) {
                throw new DomainException('O código só pode ser gerado durante o transporte.');
            }
            $code = (string) random_int(100000, 999999);
            $pdo->prepare(
                "UPDATE freights SET status = 'out_for_delivery', delivery_code_hash = ?,
                 delivery_code_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR),
                 delivery_code_attempts = 0, delivery_code_used_at = NULL WHERE id = ?"
            )->execute([password_hash($code, PASSWORD_DEFAULT), $freight['id']]);
            self::recordStatus($pdo, (int) $freight['id'], 'out_for_delivery', 'Codigo de entrega gerado pelo comprador.');
            $_SESSION['delivery_code_once'][$freight['id']] = $code;
            $pdo->commit();
            flash('success', 'Código gerado. Ele será exibido apenas uma vez.');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível gerar o código.');
        }
        redirect_to('/frete/acompanhar?negociacao=' . (int) $negotiationId);
    }
}
