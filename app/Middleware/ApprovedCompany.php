<?php

class ApprovedCompany
{
    /** Exige empresa aprovada para recursos operacionais do marketplace. */
    public static function required(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
        if (!$companyId) {
            flash('warning', 'Faça login para continuar.');
            redirect_to('/login');
        }

        global $pdo;
        $stmt = $pdo->prepare('SELECT status FROM companies WHERE id = ? LIMIT 1');
        $stmt->execute([$companyId]);
        $statusValue = $stmt->fetchColumn();

        if ($statusValue === false) {
            $_SESSION = [];
            session_destroy();
            session_start();
            flash('error', 'Sua sessão não é mais válida. Faça login novamente.');
            redirect_to('/login');
        }

        $status = (string) $statusValue;
        $_SESSION['user']['company_status'] = $status;

        if (in_array($status, ['pending', 'changes_requested'], true)) {
            flash(
                'warning',
                $status === 'changes_requested'
                    ? 'Revise os dados solicitados e reenvie o cadastro para análise.'
                    : 'Este recurso será liberado após a aprovação da empresa.'
            );
            redirect_to('/aguardando-aprovacao');
        }

        if ($status !== 'active') {
            $_SESSION = [];
            session_destroy();
            session_start();
            flash('error', 'Empresa suspensa ou inativa.');
            redirect_to('/login');
        }
    }
}
