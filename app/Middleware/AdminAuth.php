<?php

class AdminAuth
{
    private const ALLOWED_ROLES = ['admin', 'staff'];

    /**
     * Permissões por role.
     * admin → acesso total
     * staff → sem financeiro, sem configurações, sem ações sobre empresas/suporte
     */
    private const PERMISSIONS = [
        'admin' => [
            'view_financial',
            'view_settings',
            'view_reports',
            'company_approve',
            'company_suspend',
            'company_delete',
            'listing_approve',
            'listing_delete',
            'support_manage',
        ],
        'staff' => [
            'view_reports',
            'listing_approve',
        ],
    ];

    /**
     * Protege a rota — redireciona para login se não autenticado
     * ou exibe 403 se não tiver role interna.
     */
    public static function required(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            $base = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/');
            header('Location: ' . $base . '/../login.php?aviso=' . urlencode('Faça login para acessar o painel.'));
            exit;
        }

        if (!in_array($user['role'] ?? '', self::ALLOWED_ROLES, true)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="pt-BR"><body style="font-family:sans-serif;padding:2rem">
                    <h2>Acesso negado</h2>
                    <p>Você não tem permissão para acessar esta área.</p>
                    <a href="../base.php">← Voltar à plataforma</a>
                  </body></html>';
            exit;
        }
    }

    /** Verifica se o usuário logado possui uma permissão específica. */
    public static function can(string $permission): bool
    {
        $role = $_SESSION['user']['role'] ?? '';
        return in_array($permission, self::PERMISSIONS[$role] ?? [], true);
    }

    /** Atalho: é admin? */
    public static function isAdmin(): bool
    {
        return ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    /** Atalho: é staff? */
    public static function isStaff(): bool
    {
        return ($_SESSION['user']['role'] ?? '') === 'staff';
    }

    /** Dados do usuário logado. */
    public static function user(): array
    {
        return $_SESSION['user'] ?? [];
    }
}