<?php

class ProposalController
{
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
            http_response_code(405);
            exit('Método não permitido.');
        }
        if (!csrf_validate()) {
            flash('error', 'A sessão do formulário expirou. Tente novamente.');
            redirect_to('/conversas');
        }
    }

    private static function negotiation(PDO $pdo, int $id, int $companyId, bool $lock = false): array
    {
        $sql = "SELECT n.*, l.title AS listing_title, l.unit, l.status AS listing_status
                FROM negotiations n
                INNER JOIN listings l ON l.id = n.listing_id
                WHERE n.id = ? AND (n.buyer_company_id = ? OR n.seller_company_id = ?)
                LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $companyId, $companyId]);
        $negotiation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$negotiation) {
            throw new DomainException('Você não possui acesso a esta negociação.');
        }
        return $negotiation;
    }

    private static function latestProposal(PDO $pdo, int $negotiationId, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM proposals WHERE negotiation_id = ? ORDER BY id DESC LIMIT 1'
            . ($lock ? ' FOR UPDATE' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$negotiationId]);
        $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
        return $proposal ?: null;
    }

    private static function otherCompany(array $negotiation, int $companyId): int
    {
        return (int) $negotiation['buyer_company_id'] === $companyId
            ? (int) $negotiation['seller_company_id']
            : (int) $negotiation['buyer_company_id'];
    }

    private static function notify(PDO $pdo, int $companyId, string $type, string $title, string $body, int $negotiationId): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (company_id, type, title, body, data_json)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $companyId, $type, $title, $body,
            json_encode(['negotiation_id' => $negotiationId], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private static function systemMessage(PDO $pdo, int $negotiationId, string $content): void
    {
        $stmt = $pdo->prepare('INSERT INTO messages (negotiation_id, sender_user_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$negotiationId, self::userId(), $content]);
    }

    private static function emailTarget(PDO $pdo, int $companyId): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT u.email, u.name
             FROM users u
             INNER JOIN companies c ON c.id = u.company_id
             WHERE u.company_id = ?
               AND u.is_active = 1
               AND u.deleted_at IS NULL
               AND c.notify_proposals = 1
             ORDER BY (u.role = 'admin_company') DESC, u.id ASC LIMIT 1"
        );
        $stmt->execute([$companyId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        return $target ?: null;
    }

    private static function sendEmailSafe(?array $target, string $subject, string $title, string $text, int $negotiationId): void
    {
        if (!$target) return;
        try {
            require_once CONFIG_PATH . '/mailer.php';
            $baseUrl = rtrim((string) env('APP_URL', 'http://localhost/re.source'), '/');
            enviarEmailFluxo(
                (string) $target['email'],
                (string) $target['name'],
                $subject,
                $title,
                $text,
                $baseUrl . '/conversas/abrir?id=' . $negotiationId
            );
        } catch (Throwable $error) {
            error_log('Falha no alerta de proposta: ' . $error->getMessage());
        }
    }

    private static function redirectToChat(int $negotiationId): never
    {
        redirect_to('/conversas/abrir?id=' . $negotiationId);
    }

    public static function save(): void
    {
        self::requirePost();
        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        $quantity = (float) str_replace(',', '.', trim((string) ($_POST['quantity'] ?? '')));
        $unitPrice = (float) str_replace(',', '.', trim((string) ($_POST['unit_price'] ?? '')));
        $deadline = trim((string) ($_POST['delivery_deadline'] ?? ''));
        $freight = (string) ($_POST['responsible_for_freight'] ?? 'buyer');
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if (!$negotiationId || $quantity <= 0 || $quantity > 999999999 || $unitPrice < 0 || $unitPrice > 999999999) {
            flash('error', 'Informe quantidade e valor válidos.');
            self::redirectToChat((int) $negotiationId);
        }
        if (!in_array($freight, ['buyer', 'seller', 'shared'], true)) {
            flash('error', 'Responsável pelo frete inválido.');
            self::redirectToChat((int) $negotiationId);
        }
        if ($deadline !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $deadline);
            if (!$date || $date->format('Y-m-d') !== $deadline || $date < new DateTimeImmutable('today')) {
                flash('error', 'Informe um prazo de entrega válido.');
                self::redirectToChat((int) $negotiationId);
            }
        }
        if (mb_strlen($notes) > 1000) {
            flash('error', 'As observações devem ter no máximo 1.000 caracteres.');
            self::redirectToChat((int) $negotiationId);
        }

        global $pdo;
        $companyId = self::companyId();
        $emailTarget = null;
        try {
            $pdo->beginTransaction();
            $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId, true);
            if (in_array($negotiation['status'], ['accepted', 'awaiting_freight', 'shipping', 'delivered', 'concluded', 'cancelled'], true)) {
                throw new DomainException('A proposta não pode mais ser alterada neste estágio.');
            }

            $latest = self::latestProposal($pdo, (int) $negotiationId, true);
            $total = round($quantity * $unitPrice, 2);
            if ($latest && $latest['status'] === 'pending') {
                if ((int) $latest['sender_company_id'] !== $companyId) {
                    throw new DomainException('Responda à proposta atual antes de enviar outra.');
                }
                $stmt = $pdo->prepare(
                    "UPDATE proposals SET quantity = ?, unit_price = ?, total_price = ?,
                     delivery_deadline = ?, responsible_for_freight = ?, notes = ?,
                     buyer_accepted_at = NULL, seller_accepted_at = NULL
                     WHERE id = ?"
                );
                $stmt->execute([$quantity, $unitPrice, $total, $deadline ?: null, $freight, $notes ?: null, $latest['id']]);
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO proposals
                        (negotiation_id, sender_company_id, quantity, unit_price, total_price,
                         delivery_deadline, responsible_for_freight, notes, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
                );
                $stmt->execute([$negotiationId, $companyId, $quantity, $unitPrice, $total, $deadline ?: null, $freight, $notes ?: null]);
            }

            $pdo->prepare(
                "UPDATE negotiations SET status = 'proposal_sent', protocol_number = NULL,
                 proposed_quantity = ?, proposed_price = ?, proposed_total = ?, agreement_at = NULL
                 WHERE id = ?"
            )->execute([$quantity, $unitPrice, $total, $negotiationId]);
            $pdo->prepare("UPDATE listings SET status = 'negotiating' WHERE id = ?")
                ->execute([$negotiation['listing_id']]);

            $otherCompany = self::otherCompany($negotiation, $companyId);
            self::notify($pdo, $otherCompany, 'proposal_received', 'Nova proposta recebida',
                'Uma proposta foi enviada para ' . $negotiation['listing_title'] . '.', (int) $negotiationId);
            self::systemMessage($pdo, (int) $negotiationId, '📄 Nova proposta enviada: ' . number_format($quantity, 3, ',', '.') . ' × R$ ' . number_format($unitPrice, 2, ',', '.'));
            $emailTarget = self::emailTarget($pdo, $otherCompany);
            $pdo->commit();
            flash('success', 'Proposta enviada. Comprador e vendedor precisam confirmar o acordo.');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível salvar a proposta.');
            self::redirectToChat((int) $negotiationId);
        }

        self::sendEmailSafe($emailTarget, 'Nova proposta — Re.Source', 'Você recebeu uma proposta',
            'Acesse a conversa para revisar valores, prazo e responsabilidade pelo frete.', (int) $negotiationId);
        self::redirectToChat((int) $negotiationId);
    }

    public static function accept(): void
    {
        self::requirePost();
        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        global $pdo;
        $companyId = self::companyId();
        $agreement = false;
        $emailTargets = [];

        try {
            $pdo->beginTransaction();
            $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId, true);
            if (!in_array($negotiation['status'], ['proposal_sent', 'buyer_accepted', 'seller_accepted'], true)) {
                throw new DomainException('Esta proposta não está disponível para aceite.');
            }
            $proposal = self::latestProposal($pdo, (int) $negotiationId, true);
            if (!$proposal || $proposal['status'] !== 'pending') {
                throw new DomainException('Proposta pendente não encontrada.');
            }

            $isBuyer = (int) $negotiation['buyer_company_id'] === $companyId;
            $field = $isBuyer ? 'buyer_accepted_at' : 'seller_accepted_at';
            if (!empty($proposal[$field])) {
                throw new DomainException('Sua empresa já confirmou esta proposta.');
            }
            $pdo->prepare("UPDATE proposals SET {$field} = NOW() WHERE id = ?")->execute([$proposal['id']]);

            $buyerAccepted = $isBuyer || !empty($proposal['buyer_accepted_at']);
            $sellerAccepted = !$isBuyer || !empty($proposal['seller_accepted_at']);
            $otherCompany = self::otherCompany($negotiation, $companyId);

            if ($buyerAccepted && $sellerAccepted) {
                $agreement = true;
                $protocol = 'RES-' . date('Ymd') . '-' . (int) $negotiationId . '-' . strtoupper(bin2hex(random_bytes(2)));
                $pdo->prepare("UPDATE proposals SET status = 'accepted', responded_at = NOW() WHERE id = ?")
                    ->execute([$proposal['id']]);
                $pdo->prepare(
                    "UPDATE negotiations SET status = 'accepted', protocol_number = ?, agreement_at = NOW() WHERE id = ?"
                )->execute([$protocol, $negotiationId]);
                self::notify($pdo, (int) $negotiation['buyer_company_id'], 'proposal_accepted', 'Acordo confirmado', 'Comprador e vendedor aceitaram a proposta.', (int) $negotiationId);
                self::notify($pdo, (int) $negotiation['seller_company_id'], 'proposal_accepted', 'Acordo confirmado', 'Comprador e vendedor aceitaram a proposta.', (int) $negotiationId);
                self::systemMessage($pdo, (int) $negotiationId, '✅ Acordo mútuo confirmado. Protocolo: ' . $protocol);
                $emailTargets[] = self::emailTarget($pdo, (int) $negotiation['buyer_company_id']);
                $emailTargets[] = self::emailTarget($pdo, (int) $negotiation['seller_company_id']);
            } else {
                $status = $isBuyer ? 'buyer_accepted' : 'seller_accepted';
                $pdo->prepare('UPDATE negotiations SET status = ? WHERE id = ?')->execute([$status, $negotiationId]);
                self::notify($pdo, $otherCompany, 'proposal_accepted', 'Uma parte aceitou a proposta', 'Falta a confirmação da sua empresa.', (int) $negotiationId);
                self::systemMessage($pdo, (int) $negotiationId, '👍 Uma das empresas confirmou a proposta.');
            }
            $pdo->commit();
            flash('success', $agreement ? 'Acordo mútuo confirmado e protocolo gerado.' : 'Seu aceite foi registrado. Aguardando a outra empresa.');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível aceitar a proposta.');
        }

        if ($agreement) {
            foreach ($emailTargets as $target) {
                self::sendEmailSafe($target, 'Acordo confirmado — Re.Source', 'Acordo mútuo confirmado',
                    'As duas empresas aceitaram a proposta. O próximo passo é escolher o frete.', (int) $negotiationId);
            }
        }
        self::redirectToChat((int) $negotiationId);
    }

    public static function refuse(): void
    {
        self::respondWithReason('refuse');
    }

    public static function cancel(): void
    {
        self::respondWithReason('cancel');
    }

    public static function reopen(): void
    {
        self::requirePost();
        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        if (!$negotiationId) {
            flash('error', 'Negociacao invalida.');
            redirect_to('/conversas');
        }

        global $pdo;
        $companyId = self::companyId();
        try {
            $pdo->beginTransaction();
            $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId, true);
            if ($negotiation['status'] !== 'cancelled') {
                throw new DomainException('Somente negociacoes canceladas podem ser reabertas.');
            }

            $pdo->prepare(
                "UPDATE negotiations
                 SET status = 'open', cancelled_by = NULL, cancel_reason = NULL,
                     protocol_number = NULL, agreement_at = NULL, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?"
            )->execute([$negotiationId]);

            self::systemMessage($pdo, (int) $negotiationId, 'Negociacao reaberta. As empresas podem enviar uma nova proposta.');
            self::notify($pdo, self::otherCompany($negotiation, $companyId), 'negotiation_reopened', 'Negociacao reaberta',
                'A conversa foi reaberta para uma nova proposta.', (int) $negotiationId);

            $pdo->commit();
            flash('success', 'Negociacao reaberta. Voces ja podem enviar uma nova proposta.');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Nao foi possivel reabrir a negociacao.');
        }

        self::redirectToChat((int) $negotiationId);
    }

    private static function respondWithReason(string $action): never
    {
        self::requirePost();
        $negotiationId = filter_input(INPUT_POST, 'negotiation_id', FILTER_VALIDATE_INT);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
            flash('error', 'Informe um motivo entre 10 e 1.000 caracteres.');
            self::redirectToChat((int) $negotiationId);
        }

        global $pdo;
        $companyId = self::companyId();
        try {
            $pdo->beginTransaction();
            $negotiation = self::negotiation($pdo, (int) $negotiationId, $companyId, true);
            $proposal = self::latestProposal($pdo, (int) $negotiationId, true);
            $otherCompany = self::otherCompany($negotiation, $companyId);

            if ($action === 'refuse') {
                if (!$proposal || $proposal['status'] !== 'pending') {
                    throw new DomainException('Não existe proposta pendente para recusar.');
                }
                $pdo->prepare(
                    "UPDATE proposals SET status = 'refused', refused_by_company_id = ?,
                     refusal_reason = ?, responded_at = NOW() WHERE id = ?"
                )->execute([$companyId, $reason, $proposal['id']]);
                $pdo->prepare("UPDATE negotiations SET status = 'open' WHERE id = ?")->execute([$negotiationId]);
                self::notify($pdo, $otherCompany, 'proposal_refused', 'Proposta recusada', $reason, (int) $negotiationId);
                self::systemMessage($pdo, (int) $negotiationId, '❌ Proposta recusada. Motivo: ' . $reason);
                $success = 'Proposta recusada. A conversa continua aberta para uma nova proposta.';
            } else {
                if (!in_array($negotiation['status'], ['open', 'proposal_sent', 'buyer_accepted', 'seller_accepted', 'accepted'], true)) {
                    throw new DomainException('A negociação não pode mais ser cancelada por aqui.');
                }
                if ($proposal && in_array($proposal['status'], ['pending', 'accepted'], true)) {
                    $pdo->prepare(
                        "UPDATE proposals SET status = 'cancelled', cancelled_by_company_id = ?, cancel_reason = ? WHERE id = ?"
                    )->execute([$companyId, $reason, $proposal['id']]);
                }
                $pdo->prepare(
                    "UPDATE negotiations SET status = 'cancelled', cancelled_by = ?, cancel_reason = ? WHERE id = ?"
                )->execute([$companyId, $reason, $negotiationId]);
                $pdo->prepare("UPDATE listings SET status = 'active' WHERE id = ? AND status = 'negotiating'")
                    ->execute([$negotiation['listing_id']]);
                self::notify($pdo, $otherCompany, 'negotiation_cancelled', 'Negociação cancelada', $reason, (int) $negotiationId);
                self::systemMessage($pdo, (int) $negotiationId, '🚫 Negociação cancelada. Motivo: ' . $reason);
                $success = 'Negociação cancelada.';
            }
            $pdo->commit();
            flash('success', $success);
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $error instanceof DomainException ? $error->getMessage() : 'Não foi possível concluir a ação.');
        }
        self::redirectToChat((int) $negotiationId);
    }
}
