<?php

$host    = (string) env('DB_HOST', '127.0.0.1');
$usuario = (string) env('DB_USERNAME', 'root');
$senha   = (string) env('DB_PASSWORD', '');
$banco   = (string) env('DB_DATABASE', 'resource');
$port    = (string) env('DB_PORT', '3406');

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

} catch (PDOException $e) {

    error_log('Falha na conexao com o banco: ' . $e->getMessage());
    $message = env('APP_DEBUG', false)
        ? 'Erro na conexão: ' . $e->getMessage()
        : 'Não foi possível conectar ao banco de dados.';
    die($message);

}
