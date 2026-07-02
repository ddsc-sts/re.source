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
            header('Location: /re.source/login?aviso=' . urlencode('Faça login para continuar.'));
            exit;
        }

        global $pdo;
        $stmt = $pdo->prepare('SELECT status FROM companies WHERE id = ? LIMIT 1');
        $stmt->execute([$companyId]);
        $statusValue = $stmt->fetchColumn();

        if ($statusValue === false) {
            $_SESSION = [];
            session_destroy();
            header('Location: /re.source/login?aviso=' . urlencode('Sua sessão não é mais válida. Faça login novamente.'));
            exit;
        }

        $status = (string) $statusValue;
        $_SESSION['user']['company_status'] = $status;

        if ($status === 'pending') {
            header('Location: /re.source/aguardando-aprovacao?aviso=' . urlencode('Este recurso será liberado após a aprovação da empresa.'));
            exit;
        }

        if ($status !== 'active') {
            $_SESSION = [];
            session_destroy();
            header('Location: /re.source/login?aviso=' . urlencode('Empresa suspensa ou inativa.'));
            exit;
        }
    }
}
