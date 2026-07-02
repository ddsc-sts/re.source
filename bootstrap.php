<?php
// bootstrap.php - Centraliza todas as configurações

// Define o caminho raiz do projeto
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');

// Carrega configuracoes locais antes de inicializar qualquer servico.
require_once CONFIG_PATH . '/env.php';
loadEnv(ROOT_PATH . '/.env');

define('APP_PATH', ROOT_PATH . '/app');
define('CONTROLLER_PATH', APP_PATH . '/Controllers');
define('VIEW_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Carrega a conexão com o banco de dados
require_once CONFIG_PATH . '/conexao.php';

// Inicia a sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Retorna o token CSRF da sessao, criando-o quando necessario. */
function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/** Gera o campo oculto usado nos formularios POST. */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Valida, sem consumir, o token CSRF enviado na requisicao atual. */
function csrf_validate(?string $token = null): bool {
    $token ??= (string) ($_POST['csrf_token'] ?? '');
    return $token !== ''
        && isset($_SESSION['_csrf_token'])
        && hash_equals((string) $_SESSION['_csrf_token'], $token);
}

// Função helper para carregar views
function view($viewName, $data = []) {
    extract($data);
    require_once VIEW_PATH . '/' . $viewName . '.php';
}
