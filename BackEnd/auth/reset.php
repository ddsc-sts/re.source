<?php
// BackEnd/auth/reset.php — API pura (sem HTML)
ob_start();
session_start();
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json; charset=utf-8');

// ── GET: valida se o token ainda é válido ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim($_GET['token'] ?? '');

    if ($token === '') {
        echo json_encode(['success' => false, 'message' => 'Token não informado.']);
        exit;
    }

    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare("
            SELECT id FROM password_resets
            WHERE token_hash = :hash
              AND used_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);

        if ($stmt->fetch()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente mais tarde.']);
    }

    exit;
}

// ── POST: redefine a senha ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = trim($_POST['token']            ?? '');
    $senha    = trim($_POST['password']         ?? '');
    $confirma = trim($_POST['password_confirm'] ?? '');

    // Validações básicas
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
        // Verifica token
        $stmt = $pdo->prepare("
            SELECT id, user_id FROM password_resets
            WHERE token_hash = :hash
              AND used_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado.']);
            exit;
        }

        // Atualiza a senha
        $novoHash = password_hash($senha, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
            ->execute([':hash' => $novoHash, ':id' => $reset['user_id']]);

        // Marca token como usado
        $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id")
            ->execute([':id' => $reset['id']]);

        // Invalida todas as sessões ativas do usuário
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

// ── Qualquer outro método ──────────────────────────────────────────────────
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido.']);