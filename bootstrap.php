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

$appBasePath = '/' . trim((string) env('APP_BASE_PATH', '/re.source'), '/');
if ($appBasePath === '/') {
    $appBasePath = '';
}
define('APP_BASE_PATH', $appBasePath);

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

/** Monta uma URL interna sem espalhar o subdiretorio da aplicacao pelo codigo. */
function app_url(string $path = ''): string {
    $path = '/' . ltrim($path, '/');
    return APP_BASE_PATH . ($path === '/' ? '/' : $path);
}

/** Monta URL de asset publico com versao baseada na data do arquivo. */
function asset_url(string $path = ''): string {
    $path = '/' . ltrim($path, '/');
    $url = app_url('/public' . $path);
    $file = PUBLIC_PATH . str_replace('/', DIRECTORY_SEPARATOR, $path);

    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }

    return $url;
}

/** Le uma configuracao administrativa persistida em system_settings. */
function app_setting(string $key, ?string $default = null): ?string {
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        try {
            global $pdo;
            if ($pdo instanceof PDO) {
                $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
                }
            }
        } catch (Throwable $e) {
            error_log('Falha ao carregar system_settings: ' . $e->getMessage());
        }
    }

    return $settings[$key] ?? $default;
}

/** Redireciona para uma rota interna e encerra a requisicao. */
function redirect_to(string $path, int $status = 302): never {
    header('Location: ' . app_url($path), true, $status);
    exit;
}

/** Registra uma mensagem que sera exibida uma unica vez apos o redirect. */
function flash(string $type, string $message): void {
    $allowedTypes = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'info';
    }

    $_SESSION['_flash'][] = [
        'type' => $type,
        'message' => trim($message),
    ];
}

/** Retira todas as mensagens flash da sessao. */
function pull_flashes(): array {
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($messages) ? $messages : [];
}

// Função helper para carregar views
function view($viewName, $data = []) {
    extract($data);
    require_once VIEW_PATH . '/' . $viewName . '.php';
}
