<?php

require_once "../config/conexao.php";

if($_SERVER["REQUEST_METHOD"] !== "POST"){

    die("Método inválido");

}

$nome = trim($_POST["nome"]);
$sobrenome = trim($_POST["sobrenome"]);
$email = trim($_POST["email"]);
$senha = $_POST["senha"];
$telefone = trim($_POST["telefone"]);
$estado = trim($_POST["estado"]);
$cnpj = trim($_POST["cnpj"]);
$razao = trim($_POST["razao"]);

# EMAIL CORPORATIVO

$dominiosBloqueados = [

    "gmail.com",
    "hotmail.com",
    "yahoo.com",
    "outlook.com"

];

$dominio = explode("@", $email)[1];

if(in_array($dominio, $dominiosBloqueados)){

    die("Use e-mail corporativo");

}

# VERIFICAR CNPJ EXISTENTE

$sql = $pdo->prepare("
    SELECT id
    FROM companies
    WHERE cnpj = ?
");

$sql->execute([$cnpj]);

if($sql->rowCount() > 0){

    die("CNPJ já cadastrado");

}

# HASH SENHA

$passwordHash = password_hash(
    $senha,
    PASSWORD_BCRYPT
);

# TRANSACTION

$pdo->beginTransaction();

try{

    # EMPRESA

    $sql = $pdo->prepare("
        INSERT INTO companies
        (
            cnpj,
            razao_social,
            email,
            telefone,
            estado
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $sql->execute([

        $cnpj,
        $razao,
        $email,
        $telefone,
        $estado

    ]);

    $companyId = $pdo->lastInsertId();

    # USER

    $sql = $pdo->prepare("
        INSERT INTO users
        (
            company_id,
            nome,
            sobrenome,
            email,
            password_hash
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $sql->execute([

        $companyId,
        $nome,
        $sobrenome,
        $email,
        $passwordHash

    ]);

    $pdo->commit();

    echo "Conta criada com sucesso";

}catch(Exception $e){

    $pdo->rollBack();

    die(
        "Erro: " .
        $e->getMessage()
    );

}