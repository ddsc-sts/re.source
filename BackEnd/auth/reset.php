<?php
// BackEnd/auth/reset.php
ob_start();
session_start();
require_once __DIR__ . '/../config/conexao.php';

$raiz = rtrim(str_replace('BackEnd/auth/reset.php', '', $_SERVER['SCRIPT_NAME']), '/');

// ─── POST: processa a redefinição de senha ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $token    = trim($_POST['token'] ?? '');
    $senha    = trim($_POST['password'] ?? '');
    $confirma = trim($_POST['password_confirm'] ?? '');

    if ($token === '' || $senha === '') {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
        exit;
    }

    if (strlen($senha) < 8) {
        echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.']);
        exit;
    }

    if ($senha !== $confirma) {
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
        exit;
    }

    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':hash' => $tokenHash]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado.']);
            exit;
        }

        $novoHash = password_hash($senha, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
            ->execute([':hash' => $novoHash, ':id' => $reset['user_id']]);

        $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id")
            ->execute([':id' => $reset['id']]);

        $pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid")
            ->execute([':uid' => $reset['user_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Senha redefinida com sucesso!',
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
    }
    exit;
}

// ─── GET com ?validate=1: só valida token e responde JSON ────────
$token = trim($_GET['token'] ?? '');

if (isset($_GET['validate'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($token === '') {
        echo json_encode(['success' => false, 'message' => 'Token não informado.']);
        exit;
    }

    try {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':hash' => $tokenHash]);
        echo json_encode(['success' => (bool) $stmt->fetch()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno.']);
    }
    exit;
}

// ─── GET normal: serve o HTML ────────────────────────────────────
ob_clean();
$html = file_get_contents(__DIR__ . '/../../FrontEnd/templates/reset.html');
echo $html;