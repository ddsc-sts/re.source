<?php

// BackEnd/auth/reenviar.php — reenvia o e-mail de confirmação

require_once __DIR__ . "/../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /cadastro.php");
    exit;
}

$email = strtolower(trim($_POST["email"] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: /cadastro.php?erro=" . urlencode("E-mail inválido."));
    exit;
}

// Busca o usuário pelo e-mail (ainda não verificado)
$stmt = $pdo->prepare("SELECT id, name, email_verified_at FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Resposta genérica — não revela se o e-mail existe (segurança)
$msgPendente = "/pendente.php?email=" . urlencode($email);

if (!$user) {
    header("Location: $msgPendente");
    exit;
}

// Se já verificou, manda direto pro login
if ($user['email_verified_at'] !== null) {
    header("Location: /login.php?info=" . urlencode("Sua conta já está ativa. Faça login."));
    exit;
}

// Remove tokens antigos deste usuário
$pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$user['id']]);

// Gera novo token
$token     = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

$pdo->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?,?,?)")
    ->execute([$user['id'], $token, $expiresAt]);

// Monta e envia o e-mail
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'];

$link = $baseUrl . "/BackEnd/auth/verificar.php?token=" . $token;

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Re.Source <noreply@re.source.com.br>\r\n";

$corpo = "
<html><body style='font-family:Inter,sans-serif;padding:2rem;'>
  <h2>Reenvio de confirmação</h2>
  <p>Olá, {$user['name']}! Clique abaixo para confirmar seu e-mail:</p>
  <a href='$link' style='background:#157347;color:#fff;padding:.9rem 2rem;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;'>
    ✅ Confirmar meu e-mail
  </a>
  <p style='color:#aaa;font-size:.85rem;margin-top:1.5rem;'>Link válido por 24 horas.</p>
</body></html>
";

mail($email, "Novo link de confirmação — Re.Source", $corpo, $headers);

header("Location: $msgPendente");
exit;