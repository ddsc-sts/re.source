<?php

class DeliveryController
{
    private const MAX_ATTEMPTS = 5;

    private static function companyId(): int
    {
        return (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
    }

    private static function userId(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    private static function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405); exit('Método não permitido.');
        }
        if (!csrf_validate()) {
            flash('error', 'A sessão expirou. Recarregue a página.');
            redirect_to('/admin/logistica');
        }
    }

    private static function sendDeliveryEmail(PDO $pdo, int $companyId, int $negotiationId): void
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
            enviarEmailFluxo($target['email'], $target['name'], 'Entrega confirmada — Re.Source',
                'Negociacao concluida', 'A entrega foi confirmada. O saldo da venda ja foi liberado ao vendedor.',
                $baseUrl . '/frete/acompanhar?negociacao=' . $negotiationId);
        } catch (Throwable $error) {
            error_log('Falha no alerta de entrega: ' . $error->getMessage());
        }
    }

    public static function portal(): void
    {
        global $pdo;
        $stmt = $pdo->query(
            "SELECT f.*, n.protocol_number, n.proposed_total, l.title AS listing_title,
                    buyer.nome_fantasia AS buyer_name, seller.nome_fantasia AS seller_name
             FROM freights f
             INNER JOIN negotiations n ON n.id = f.negotiation_id
             INNER JOIN listings l ON l.id = n.listing_id
             INNER JOIN companies buyer ON buyer.id = n.buyer_company_id
             INNER JOIN companies seller ON seller.id = n.seller_company_id
             WHERE f.status IN ('contracted','preparing','in_transit','out_for_delivery','delivered')
             ORDER BY f.updated_at DESC"
        );
        $freights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $user = AdminAuth::user();
        $metrics = [];
        require VIEW_PATH . '/delivery/portal.php';
    }

    public static function validate(): void
    {
        self::requirePost();
        $freightId = filter_input(INPUT_POST, 'freight_id', FILTER_VALIDATE_INT);
        $code = trim((string) ($_POST['code'] ?? ''));
        if (!$freightId || !preg_match('/^\d{6}$/', $code)) {
            flash('error', 'Informe um código válido de seis dígitos.');
            redirect_to('/admin/logistica');
        }

        global $pdo;
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "SELECT f.*, n.buyer_company_id, n.seller_company_id, n.listing_id,
                        n.proposed_total, n.status AS negotiation_status
                 FROM freights f INNER JOIN negotiations n ON n.id = f.negotiation_id
                 WHERE f.id = ? LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$freightId]);
            $freight = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$freight) throw new DomainException('Frete não encontrado.');
            if ($freight['delivery_code_used_at'] || in_array($freight['status'], ['delivered', 'concluded'], true)) {
                throw new DomainException('Esta entrega já foi confirmada.');
            }
            if (!$freight['delivery_code_hash'] || !$freight['delivery_code_expires_at']) {
                throw new DomainException('O comprador ainda não gerou o código.');
            }
            if (strtotime($freight['delivery_code_expires_at']) < time()) {
                throw new DomainException('O código expirou. Solicite um novo ao comprador.');
            }
            if ((int) $freight['delivery_code_attempts'] >= self::MAX_ATTEMPTS) {
                throw new DomainException('Limite de tentativas atingido. Solicite um novo código.');
            }

            $valid = password_verify($code, $freight['delivery_code_hash']);
            $pdo->prepare(
                'INSERT INTO delivery_attempts (freight_id, company_id, user_id, ip_address, success)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $freightId, self::companyId() ?: null, self::userId() ?: null,
                $_SERVER['REMOTE_ADDR'] ?? null, $valid ? 1 : 0,
            ]);

            if (!$valid) {
                $pdo->prepare('UPDATE freights SET delivery_code_attempts = delivery_code_attempts + 1 WHERE id = ?')
                    ->execute([$freightId]);
                $pdo->commit();
                flash('error', 'Código incorreto. Tentativa registrada.');
                redirect_to('/admin/logistica');
            }

            $amount = (float) $freight['proposed_total'];
            if ($amount <= 0) throw new DomainException('Valor financeiro da negociação inválido.');

            $pdo->prepare(
                "UPDATE freights SET status = 'concluded', delivered_at = NOW(), validated_at = NOW(),
                 delivery_code_used_at = NOW(), delivery_code_hash = NULL WHERE id = ?"
            )->execute([$freightId]);
            $pdo->prepare("UPDATE negotiations SET status = 'concluded', concluded_at = NOW() WHERE id = ?")
                ->execute([$freight['negotiation_id']]);
            $pdo->prepare("UPDATE listings SET status = 'concluded' WHERE id = ?")
                ->execute([$freight['listing_id']]);

            $stmt = $pdo->prepare(
                "SELECT id FROM financial_transactions
                 WHERE negotiation_id = ? AND type = 'sale' LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$freight['negotiation_id']]);
            if ($stmt->fetchColumn()) throw new DomainException('O saldo desta entrega já foi liberado.');

            $pdo->prepare(
                "INSERT INTO financial_transactions
                    (company_id, negotiation_id, type, amount, status, description)
                 VALUES (?, ?, 'sale', ?, 'completed', ?)"
            )->execute([
                $freight['seller_company_id'], $freight['negotiation_id'], $amount,
                'Venda liberada após confirmação da entrega',
            ]);
            $pdo->prepare('UPDATE companies SET balance = balance + ? WHERE id = ?')
                ->execute([$amount, $freight['seller_company_id']]);
            $pdo->prepare(
                "INSERT INTO freight_status_history (freight_id, status, description, created_by_user_id)
                 VALUES (?, 'concluded', 'Codigo validado. Entrega confirmada e saldo liberado.', ?)"
            )->execute([$freightId, self::userId() ?: null]);

            $stmtNotification = $pdo->prepare(
                "INSERT INTO notifications (company_id, type, title, body, data_json)
                 VALUES (?, 'negotiation_concluded', 'Entrega confirmada', ?, ?)"
            );
            foreach ([(int) $freight['buyer_company_id'], (int) $freight['seller_company_id']] as $companyId) {
                $stmtNotification->execute([
                    $companyId,
                    'Entrega confirmada e saldo liberado ao vendedor.',
                    json_encode(['negotiation_id' => (int) $freight['negotiation_id']], JSON_UNESCAPED_UNICODE),
                ]);
            }
            $pdo->commit();
            flash('success', 'Entrega confirmada. Negociação concluída e saldo liberado.');
            foreach ([(int) $freight['buyer_company_id'], (int) $freight['seller_company_id']] as $recipientId) {
                self::sendDeliveryEmail($pdo, $recipientId, (int) $freight['negotiation_id']);
            }
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível validar a entrega.');
        }
        redirect_to('/admin/logistica');
    }

    public static function history(): void
    {
        global $pdo;
        $companyId = self::companyId();
        $stmt = $pdo->prepare(
            "SELECT f.*, n.protocol_number, n.proposed_total, l.title AS listing_title,
                    buyer.nome_fantasia AS buyer_name, seller.nome_fantasia AS seller_name
             FROM freights f
             INNER JOIN negotiations n ON n.id = f.negotiation_id
             INNER JOIN listings l ON l.id = n.listing_id
             INNER JOIN companies buyer ON buyer.id = n.buyer_company_id
             INNER JOIN companies seller ON seller.id = n.seller_company_id
             WHERE n.buyer_company_id = ? OR n.seller_company_id = ?
             ORDER BY f.updated_at DESC"
        );
        $stmt->execute([$companyId, $companyId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        view('delivery/history', compact('history'));
    }
}
