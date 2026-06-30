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

        // Verifica se há dados de usuário ou company_id na sessão
        if (empty($_SESSION['user']) && empty($_SESSION['company_id'])) {
            header('Location: /re.source/login?aviso=' . urlencode('Faça login para continuar.'));
            exit;
        }
    }
}
