<?php

class FinanceService
{
    public static function balances(PDO $pdo, int $companyId): array
    {
        $stmt = $pdo->prepare('SELECT balance FROM companies WHERE id = ?');
        $stmt->execute([$companyId]);
        $available = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) reserved,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) withdrawn
            FROM withdrawals WHERE company_id = ?");
        $stmt->execute([$companyId]);
        $withdrawals = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations
            WHERE seller_company_id = ? AND status IN ('accepted','awaiting_freight','shipping','delivered')");
        $stmt->execute([$companyId]);

        return [
            'available' => max(0, $available),
            'reserved' => (float) ($withdrawals['reserved'] ?? 0),
            'withdrawn' => (float) ($withdrawals['withdrawn'] ?? 0),
            'future' => (float) $stmt->fetchColumn(),
        ];
    }

    public static function requestWithdrawal(PDO $pdo, int $companyId, int $userId, array $data): int
    {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT balance FROM companies WHERE id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$companyId]);
            $balanceBefore = $stmt->fetchColumn();
            if ($balanceBefore === false) throw new DomainException('Empresa não encontrada.');
            $balanceBefore = (float) $balanceBefore;
            $amount = (float) $data['amount'];
            if ($amount > $balanceBefore) throw new DomainException('O valor solicitado excede o saldo disponível.');

            $stmt = $pdo->prepare('SELECT id FROM withdrawals WHERE request_token = ? LIMIT 1');
            $stmt->execute([$data['request_token']]);
            if ($stmt->fetchColumn()) throw new DomainException('Esta solicitação já foi registrada.');

            $balanceAfter = round($balanceBefore - $amount, 2);
            $stmt = $pdo->prepare("INSERT INTO withdrawals
                (company_id, amount, method, pix_key, pix_key_type, bank_code, bank_name, agency,
                 account_number, account_digit, account_type, account_holder_name,
                 account_holder_document, request_note, request_token, balance_before,
                 balance_after, reserved_at, terms_accepted_at, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'pending')");
            $stmt->execute([
                $companyId, $amount, $data['method'], $data['pix_key'], $data['pix_key_type'],
                $data['bank_code'], $data['bank_name'], $data['agency'], $data['account_number'],
                $data['account_digit'], $data['account_type'], $data['holder_name'],
                $data['holder_document'], $data['request_note'], $data['request_token'],
                $balanceBefore, $balanceAfter,
            ]);
            $withdrawalId = (int) $pdo->lastInsertId();

            $pdo->prepare('UPDATE companies SET balance = ? WHERE id = ?')->execute([$balanceAfter, $companyId]);
            $pdo->prepare("INSERT INTO financial_transactions
                (company_id, withdrawal_id, type, amount, status, description)
                VALUES (?, ?, 'withdrawal', ?, 'pending', 'Saque reservado para análise manual')")
                ->execute([$companyId, $withdrawalId, $amount]);
            self::audit($pdo, $userId, $companyId, 'WITHDRAWAL_REQUESTED', 'withdrawal', $withdrawalId,
                ['balance' => $balanceBefore], ['amount' => $amount, 'balance' => $balanceAfter]);
            $pdo->commit();
            return $withdrawalId;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function review(PDO $pdo, int $withdrawalId, int $adminUserId, bool $approve, ?string $reason = null): array
    {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT w.*, c.nome_fantasia, c.razao_social
                FROM withdrawals w INNER JOIN companies c ON c.id = w.company_id
                WHERE w.id = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$withdrawalId]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$withdrawal) throw new DomainException('Solicitação de saque não encontrada.');
            if ($withdrawal['status'] !== 'pending') throw new DomainException('Esta solicitação já foi analisada.');

            if ($approve) {
                $pdo->prepare("UPDATE withdrawals SET status = 'completed', reviewed_at = NOW(),
                    reviewed_by_user_id = ?, admin_note = ? WHERE id = ?")
                    ->execute([$adminUserId, $reason ?: null, $withdrawalId]);
                $pdo->prepare("UPDATE financial_transactions SET status = 'completed',
                    description = 'Saque aprovado manualmente' WHERE withdrawal_id = ?")
                    ->execute([$withdrawalId]);
                $type = 'withdrawal_approved';
                $action = 'WITHDRAWAL_APPROVED';
                $message = 'Seu saque foi aprovado pela equipe administrativa.';
            } else {
                if (mb_strlen(trim((string) $reason)) < 5) throw new DomainException('Informe o motivo da recusa.');
                $stmt = $pdo->prepare('SELECT balance FROM companies WHERE id = ? FOR UPDATE');
                $stmt->execute([$withdrawal['company_id']]);
                $pdo->prepare('UPDATE companies SET balance = balance + ? WHERE id = ?')
                    ->execute([$withdrawal['amount'], $withdrawal['company_id']]);
                $pdo->prepare("UPDATE withdrawals SET status = 'rejected', reviewed_at = NOW(),
                    reviewed_by_user_id = ?, rejection_reason = ? WHERE id = ?")
                    ->execute([$adminUserId, trim((string) $reason), $withdrawalId]);
                $pdo->prepare("UPDATE financial_transactions SET status = 'failed',
                    description = 'Saque recusado; saldo devolvido' WHERE withdrawal_id = ?")
                    ->execute([$withdrawalId]);
                $type = 'withdrawal_rejected';
                $action = 'WITHDRAWAL_REJECTED';
                $message = 'Seu saque foi recusado e o valor reservado voltou ao saldo disponível.';
            }

            $pdo->prepare('INSERT INTO notifications (company_id, type, title, body, data_json) VALUES (?, ?, ?, ?, ?)')
                ->execute([$withdrawal['company_id'], $type, $approve ? 'Saque aprovado' : 'Saque recusado', $message,
                    json_encode(['withdrawal_id' => $withdrawalId], JSON_UNESCAPED_UNICODE)]);
            self::audit($pdo, $adminUserId, (int) $withdrawal['company_id'], $action, 'withdrawal', $withdrawalId,
                ['status' => 'pending'], ['status' => $approve ? 'completed' : 'rejected', 'reason' => $reason]);
            $pdo->commit();
            return $withdrawal;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    private static function audit(PDO $pdo, int $userId, int $companyId, string $action, string $entity, int $entityId, array $old, array $new): void
    {
        $pdo->prepare("INSERT INTO audit_logs
            (user_id, company_id, action, severity, entity_type, entity_id, old_values_json,
             new_values_json, ip_address, user_agent) VALUES (?, ?, ?, 'info', ?, ?, ?, ?, ?, ?)")
            ->execute([$userId ?: null, $companyId, $action, $entity, $entityId,
                json_encode($old, JSON_UNESCAPED_UNICODE), json_encode($new, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    }
}
