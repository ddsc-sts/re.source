<?php
// BackEnd/auth/recover.php — gera token de recuperação e envia e-mail

ob_start();
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/mailer.php';

header('Content-Type: application/json; charset=utf-8');

$raiz = rtrim(str_replace('BackEnd/auth/recover.php', '', $_SERVER['SCRIPT_NAME']), '/');

function responder(bool $ok, string $msg): void {
    ob_clean();
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método não permitido.');
}

$email = strtolower(trim($_POST['email'] ?? ''));

if ($email === '') {
    responder(false, 'Informe seu e-mail.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, 'E-mail inválido.');
}

try {

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        responder(true, 'Se este e-mail estiver cadastrado, você receberá o link em breve.');
    }

    // Invalida tokens anteriores
    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL")
        ->execute([':uid' => $user['id']]);

    // Gera token seguro
    $token     = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $pdo->prepare("
        INSERT INTO password_resets (user_id, token_hash, expires_at)
        VALUES (:uid, :hash, :exp)
    ")->execute([
        ':uid'  => $user['id'],
        ':hash' => $tokenHash,
        ':exp'  => $expiresAt,
    ]);

    // ✅ CORRIGIDO: aponta para BackEnd/auth/reset.php (onde o arquivo realmente existe)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $link   = "{$scheme}://{$host}{$raiz}/BackEnd/auth/reset.php?token={$token}";

    $enviado = enviarEmailRecuperacao($email, $user['name'], $link);

    if (!$enviado) {
        responder(false, 'Falha ao enviar o e-mail. Tente novamente.');
    }

    responder(true, 'Se este e-mail estiver cadastrado, você receberá o link em breve.');

} catch (PDOException $e) {
    responder(false, 'Erro interno. Tente novamente.');
}