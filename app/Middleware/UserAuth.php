<?php

class UserAuth
{
    /**
     * Protege a rota — redireciona para login se não autenticado.
     * Permite acesso a qualquer usuário logado (admin, staff, company, user, etc).
     */
    public static function required(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Mantém compatibilidade com sessões antigas, mas exige uma empresa válida.
        $companyId = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;
        if (empty($companyId)) {
            flash('warning', 'Faça login para continuar.');
            redirect_to('/login');
        }
    }
}
