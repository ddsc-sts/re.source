<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script funciona somente no terminal.\n");
    exit(1);
}

require dirname(__DIR__) . '/config/env.php';
loadEnv(dirname(__DIR__) . '/.env');

$options = getopt('', ['confirm:', 'database::']);
$confirmation = (string) ($options['confirm'] ?? '');
$database = (string) ($options['database'] ?? 'resource_demo');

if ($confirmation !== 'CRIAR_RESOURCE_DEMO') {
    fwrite(STDERR, "Confirmacao ausente. Use --confirm=CRIAR_RESOURCE_DEMO.\n");
    exit(1);
}
if ((string) env('APP_ENV', 'local') === 'production') {
    fwrite(STDERR, "Operacao recusada em APP_ENV=production.\n");
    exit(1);
}
if (!preg_match('/^[a-z0-9_]+_demo$/i', $database)) {
    fwrite(STDERR, "O banco precisa terminar com _demo para proteger o banco principal.\n");
    exit(1);
}

$adminPassword = (string) env('DEMO_ADMIN_PASSWORD', '');
$companyPassword = (string) env('DEMO_COMPANY_PASSWORD', '');
if (strlen($adminPassword) < 10 || strlen($companyPassword) < 10) {
    fwrite(STDERR, "Defina DEMO_ADMIN_PASSWORD e DEMO_COMPANY_PASSWORD com pelo menos 10 caracteres no .env.\n");
    exit(1);
}

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$username = (string) env('DB_USERNAME', 'root');
$password = (string) env('DB_PASSWORD', '');
$server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

fwrite(STDOUT, "Preparando banco isolado {$database}...\n");
$server->exec("DROP DATABASE IF EXISTS `{$database}`");
$server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

try {
    $schema = file_get_contents(dirname(__DIR__) . '/database/seeders/re.sourcebanco.sql');
    if ($schema === false) throw new RuntimeException('Schema consolidado nao encontrado.');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS resource.*?USE resource;/s', "USE `{$database}`;", $schema, 1);
    $pdo->exec($schema);

    foreach (['create_admin.sql', 'empresa_demo.sql', 'produto.sql', 'saldo_demo.sql'] as $file) {
        $sql = file_get_contents(dirname(__DIR__) . '/database/inserts/' . $file);
        if ($sql === false) throw new RuntimeException("Insert {$file} nao encontrado.");
        $pdo->exec($sql);
    }

    $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?')
        ->execute([password_hash($adminPassword, PASSWORD_DEFAULT), 'admin@resource.com.br']);
    $companyHash = password_hash($companyPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE role = 'admin_company'")->execute([$companyHash]);

    $tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    $users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $listings = (int) $pdo->query('SELECT COUNT(*) FROM listings')->fetchColumn();
    if ($tables !== 30 || $users < 6 || $listings < 6) {
        throw new RuntimeException("Validacao falhou: tabelas={$tables}, usuarios={$users}, anuncios={$listings}.");
    }
    fwrite(STDOUT, "Banco {$database} pronto: {$tables} tabelas, {$users} usuarios, {$listings} anuncios.\n");
    fwrite(STDOUT, "No .env deste computador, use DB_DATABASE={$database}.\n");
} catch (Throwable $error) {
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
    fwrite(STDERR, "Falha; banco incompleto removido: {$error->getMessage()}\n");
    exit(1);
}
