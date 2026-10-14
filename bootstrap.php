<?php
// bootstrap.php - Centraliza todas as configurações

// Inicia a sessão antes de carregar outros arquivos.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define o caminho raiz do projeto
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');

// Carrega configurações locais.
require_once CONFIG_PATH . '/env.php';
loadEnv(ROOT_PATH . '/.env');

define('APP_PATH', ROOT_PATH . '/app');
define('CONTROLLER_PATH', APP_PATH . '/Controllers');
define('VIEW_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Define o caminho-base público da aplicação.
// XAMPP: APP_BASE_PATH=/re.source
// Docker: APP_BASE_PATH=
$appBasePath = '/' . trim((string) env('APP_BASE_PATH', '/re.source'), '/');

if ($appBasePath === '/') {
    $appBasePath = '';
}

define('APP_BASE_PATH', $appBasePath);

// Carrega a conexão com o banco de dados
require_once CONFIG_PATH . '/conexao.php';

/** Retorna o token CSRF da sessão, criando-o quando necessário. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/** Gera o campo oculto usado nos formulários POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/** Valida o token CSRF enviado na requisição atual. */
function csrf_validate(?string $token = null): bool
{
    $token ??= (string) ($_POST['csrf_token'] ?? '');

    return $token !== ''
        && isset($_SESSION['_csrf_token'])
        && hash_equals((string) $_SESSION['_csrf_token'], $token);
}

/** Monta uma URL interna respeitando o caminho-base do ambiente. */
function app_url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');

    return APP_BASE_PATH . ($path === '/' ? '/' : $path);
}

/**
 * Monta a URL de um arquivo público.
 */
function asset_url(string $path = ''): string
{
    $path = ltrim($path, '/');

    // Monta a URL pública sem acrescentar /public.
    $url = app_url($path);

    // Obtém o caminho físico do arquivo.
    $file = PUBLIC_PATH
        . DIRECTORY_SEPARATOR
        . str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $path
        );

    // Adiciona uma versão para atualizar o cache quando o arquivo mudar.
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }

    return $url;
}

/** Lê uma configuração administrativa persistida em system_settings. */
function app_setting(string $key, ?string $default = null): ?string
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];

        try {
            global $pdo;

            if ($pdo instanceof PDO) {
                $stmt = $pdo->query(
                    'SELECT setting_key, setting_value FROM system_settings'
                );

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $settings[(string) $row['setting_key']]
                        = (string) $row['setting_value'];
                }
            }
        } catch (Throwable $e) {
            error_log(
                'Falha ao carregar system_settings: '
                . $e->getMessage()
            );
        }
    }

    return $settings[$key] ?? $default;
}

/** Redireciona para uma rota interna e encerra a requisição. */
function redirect_to(string $path, int $status = 302): never
{
    header('Location: ' . app_url($path), true, $status);
    exit;
}

/** Registra uma mensagem que será exibida uma única vez após o redirect. */
function flash(string $type, string $message): void
{
    $allowedTypes = ['success', 'error', 'warning', 'info'];

    if (!in_array($type, $allowedTypes, true)) {
        $type = 'info';
    }

    $_SESSION['_flash'][] = [
        'type' => $type,
        'message' => trim($message),
    ];
}

/** Retira todas as mensagens flash da sessão. */
function pull_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($messages) ? $messages : [];
}

/** Função helper para carregar views. */
function view($viewName, $data = [])
{
    extract($data);
    require_once VIEW_PATH . '/' . $viewName . '.php';
}
