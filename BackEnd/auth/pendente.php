<?php
// BackEnd/auth/reenviar.php — gera novo código e reenvia o e-mail de verificação

ob_start();
session_start();
require_once __DIR__ . "/../config/conexao.php";

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

// ── Só aceita POST via XHR ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$isXhr) {
    header("Location: /cadastro.php");
    exit;
}

// ── Sessão ainda existe? ──────────────────────────────────────
$pendente = &$_SESSION['cadastro_pendente'];

if (!$pendente) {
    jsonErro("Sessão expirada. Refaça o cadastro.");
}

// ── Cooldown — evita spam (mínimo 60s entre reenvios) ────────
$ultimoEnvio = $pendente['ultimo_envio'] ?? 0;
$cooldown    = 60;

if ((time() - $ultimoEnvio) < $cooldown) {
    $falta = $cooldown - (time() - $ultimoEnvio);
    jsonErro("Aguarde {$falta}s antes de reenviar.");
}

// ── Gera novo código ──────────────────────────────────────────
$novoCodigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$pendente['codigo']       = $novoCodigo;
$pendente['expires_at']   = time() + 3600;          // 1 hora
$pendente['ultimo_envio'] = time();

// ── Envia e-mail ──────────────────────────────────────────────
$para      = $pendente['email'];
$nome      = $pendente['nome'];
$assunto   = 'Re.Source — Seu novo código de verificação';

$corpo = "Olá, {$nome}!\n\n"
       . "Você solicitou um novo código de verificação.\n\n"
       . "Seu código: {$novoCodigo}\n\n"
       . "O código é válido por 1 hora.\n\n"
       . "Se não foi você, ignore este e-mail.\n\n"
       . "— Equipe Re.Source";

$headers = "From: noreply@re.source.com.br\r\n"
         . "Reply-To: noreply@re.source.com.br\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($para, $assunto, $corpo, $headers);

// Em produção substitua pelo Mailer real, ex:
// $mailer->sendVerificationCode($para, $nome, $novoCodigo);

jsonOk("Novo código enviado para {$para}.");