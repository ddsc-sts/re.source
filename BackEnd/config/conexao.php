<?php

$host   = "localhost";
$usuario = "root";
$senha  = "";
$banco  = "resource";
$port   = "3306";

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE,          PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Erro na conexão: " . $e->getMessage());

}