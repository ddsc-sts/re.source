<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/config/env.php';
loadEnv(dirname(__DIR__) . '/.env');

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$user = (string) env('DB_USERNAME', 'root');
$pass = (string) env('DB_PASSWORD', '');
$database = 'resource_validation_' . bin2hex(random_bytes(4));
$server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

try {
    $server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $schema = file_get_contents(dirname(__DIR__) . '/database/seeders/re.sourcebanco.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS resource.*?USE resource;/s', "USE `{$database}`;", (string) $schema, 1);
    $pdo->exec($schema);

    $assertions = [
        'tables' => [(int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn(), 30],
        'company_review_columns' => [(int) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='companies' AND column_name IN ('approved_at','approved_by_user_id','review_notes','reviewed_at','reviewed_by_user_id')")->fetchColumn(), 5],
        'chat_indexes' => [(int) $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND ((table_name='negotiations' AND index_name='idx_neg_unique_pair') OR (table_name='messages' AND index_name='idx_msg_polling'))")->fetchColumn(), 5],
        'proposal_columns' => [(int) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='proposals' AND column_name IN ('responsible_for_freight','buyer_accepted_at','seller_accepted_at','refused_by_company_id','refusal_reason','cancelled_by_company_id','cancel_reason')")->fetchColumn(), 7],
        'withdrawal_columns' => [(int) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='withdrawals' AND column_name IN ('method','request_token','reserved_at','reviewed_by_user_id')")->fetchColumn(), 4],
        'freight_history' => [(int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='freight_status_history'")->fetchColumn(), 1],
        'financial_fk' => [(int) $pdo->query("SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND constraint_name='fk_financial_withdrawal'")->fetchColumn(), 1],
    ];
    foreach ($assertions as $name => [$actual, $expected]) {
        if ($actual !== $expected) throw new RuntimeException("{$name}: esperado {$expected}, obtido {$actual}");
        fwrite(STDOUT, "[OK] {$name}: {$actual}\n");
    }
    $migrations = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
    fwrite(STDOUT, '[OK] migrations catalogadas: ' . count($migrations) . "\n");
} finally {
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
}
