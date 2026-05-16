<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "resource";
$port = "3306";

try {

    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$banco;charset=utf8",
        $usuario,
        $senha
    );

    // Mostrar erros do banco
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retornar resultados como array associativo
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "Conexão realizada com sucesso";

} catch(PDOException $e){

    die("Erro na conexão: " . $e->getMessage());
}

?>