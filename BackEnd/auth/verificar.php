<?php
// BackEnd/auth/verificar.php — valida código de 6 dígitos e cadastra no banco

ob_start();
session_start();
require_once __DIR__ . "/../config/conexao.php";

$isXhr = ($_SERVER['HTTP_X_REQUESTED_With'] ?? '') === 'XMLHttpRequest'
      || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

$raiz = rtrim(str_replace('BackEnd/auth/verificar.php', '', $_SERVER['SCRIPT_NAME']), '/');

function responderErro(string $msg): void {
    global $isXhr, $raiz;
    ob_clean();
    if ($isXhr) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => $msg]);
    } else {
        header("Location: {$raiz}/pendente.php?erro=" . urlencode($msg));
    }
    exit;
}

function responderSucesso(string $url): void {
    global $isXhr;
    ob_clean();
    if ($isXhr) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'redirect' => $url]);
    } else {
        header("Location: $url");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$raiz}/cadastro.php");
    exit;
}

// Coleta o código — aceita tanto POST['codigo'] quanto JSON body
$codigoDigitado = trim($_POST['codigo'] ?? '');
if ($codigoDigitado === '') {
    // tenta ler do body JSON (fallback)
    $body = json_decode(file_get_contents('php://input'), true);
    $codigoDigitado = trim($body['codigo'] ?? '');
}

$pendente = $_SESSION['cadastro_pendente'] ?? null;

if (!$pendente) {
    responderErro("Sessão expirada. Faça o cadastro novamente.");
}

if (time() > $pendente['expires_at']) {
    unset($_SESSION['cadastro_pendente']);
    responderErro("Código expirado. Faça o cadastro novamente.");
}

if ($codigoDigitado === '') {
    responderErro("Nenhum código recebido. Tente novamente.");
}

if ($codigoDigitado !== $pendente['codigo']) {
    responderErro("Código incorreto. Verifique e tente novamente.");
}

// ── Tudo certo — cadastra no banco ────────────────────────────
$pdo->beginTransaction();
try {

    $pdo->prepare(
        "INSERT INTO addresses (zip_code, street, number, district, city, state)
         VALUES ('','','','','',?)"
    )->execute([$pendente['estado']]);
    $addressId = $pdo->lastInsertId();

    $nomeCompleto = $pendente['nome'] . ' ' . $pendente['sobrenome'];

    $pdo->prepare(
        "INSERT INTO companies (cnpj, razao_social, email, phone, address_id, plan_id, responsible_name, email_verified_at)
         VALUES (?,?,?,?,?,1,?,NOW())"
    )->execute([
        $pendente['cnpj'],
        $pendente['razao'],
        $pendente['email'],
        $pendente['telefone'],
        $addressId,
        $nomeCompleto,
    ]);
    $companyId = $pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO users (company_id, name, email, password_hash, role)
         VALUES (?,?,?,?,'admin_company')"
    )->execute([
        $companyId,
        $nomeCompleto,
        $pendente['email'],
        $pendente['password_hash'],
    ]);

    $pdo->commit();

} catch (\Throwable $e) {
    $pdo->rollBack();
    if ($e->getCode() === '23000') {
        unset($_SESSION['cadastro_pendente']);
        responderSucesso("{$raiz}/login.php?aviso=" . urlencode("Esta conta já está ativa. Faça login."));
    }
    responderErro("Erro interno ao salvar. Tente novamente.");
}

unset($_SESSION['cadastro_pendente']);
responderSucesso("{$raiz}/login.php?sucesso=" . urlencode("Conta criada com sucesso! Faça login."));