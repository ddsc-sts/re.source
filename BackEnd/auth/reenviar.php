<?php
// BackEnd/auth/reenviar.php — gera novo código e reenvia via Mailtrap SMTP

ob_start();
session_start();
require_once __DIR__ . "/../config/mailer.php";

$isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

function jsonOk(string $msg): void {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'msg' => $msg]);
    exit;
}

function jsonErro(string $msg): void {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'erro' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$isXhr) {
    header("Location: /cadastro.php");
    exit;
}

$pendente = &$_SESSION['cadastro_pendente'];
if (!$pendente) jsonErro("Sessão expirada. Refaça o cadastro.");

// Cooldown de 60s
$ultimoEnvio = $pendente['ultimo_envio'] ?? 0;
if ((time() - $ultimoEnvio) < 60) {
    $falta = 60 - (time() - $ultimoEnvio);
    jsonErro("Aguarde {$falta}s antes de reenviar.");
}

// Novo código
$novoCodigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$pendente['codigo']       = $novoCodigo;
$pendente['expires_at']   = time() + 3600;
$pendente['ultimo_envio'] = time();

$enviou = enviarEmailCodigo($pendente['email'], $pendente['nome'], $novoCodigo);
if (!$enviou) jsonErro("Falha ao enviar o e-mail. Tente novamente.");

jsonOk("Novo código enviado para {$pendente['email']}.");