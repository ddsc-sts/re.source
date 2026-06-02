<?php

$host    = "127.0.0.1";
$usuario = "root";
$senha   = "";
$banco   = "resource";
$port    = "3406";

echo "<h2>🔍 Diagnóstico de Conexão</h2>";

// 1. Testa se o MySQL está acessível
echo "<h3>1. Testando porta MySQL...</h3>";
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
if ($socket) {
    echo "✅ Porta $port acessível<br>";
    fclose($socket);
} else {
    echo "❌ Porta $port inacessível — Erro $errno: $errstr<br>";
}

// 2. Testa extensão PDO
echo "<h3>2. Verificando extensão PDO MySQL...</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "✅ pdo_mysql carregado<br>";
} else {
    echo "❌ pdo_mysql NÃO está ativo — habilite no php.ini<br>";
}

// 3. Testa conexão PDO
echo "<h3>3. Testando conexão com o banco...</h3>";
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );
    echo "✅ Conexão com banco '$banco' OK!<br>";
} catch (PDOException $e) {
    echo "❌ Falha: " . $e->getMessage() . "<br>";
}

// 4. Testa sem especificar banco (só o servidor)
echo "<h3>4. Testando conexão sem banco...</h3>";
try {
    $pdo2 = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $usuario,
        $senha
    );
    echo "✅ Servidor MySQL acessível<br>";

    // Lista bancos disponíveis
    $bancos = $pdo2->query("SHOW DATABASES")->fetchAll();
    echo "📋 Bancos encontrados: ";
    echo implode(", ", array_column($bancos, 'Database')) . "<br>";

} catch (PDOException $e) {
    echo "❌ Falha: " . $e->getMessage() . "<br>";
}