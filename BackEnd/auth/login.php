<?php
// BackEnd/auth/login.php — processa POST do formulário de login

ob_start();
session_start();
require_once __DIR__ . "/../config/conexao.php";

header('Content-Type: application/json; charset=utf-8');

// Detecta raiz do projeto automaticamente
$raiz = rtrim(str_replace('BackEnd/auth/login.php', '', $_SERVER['SCRIPT_NAME']), '/');

$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['password'] ?? '';

if (empty($email) || empty($senha)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.company_id,
            u.name,
            u.email,
            u.password_hash,
            u.role,
            u.is_active,
            c.status AS company_status
        FROM users u
        INNER JOIN companies c ON c.id = u.company_id
        WHERE u.email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Usuário não encontrado
    if (!$user) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
        exit;
    }

    // Usuário desativado
    if (!$user['is_active']) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Usuário desativado. Entre em contato com o suporte.']);
        exit;
    }

    // Empresa suspensa ou inativa
    if ($user['company_status'] !== 'active') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Empresa suspensa ou inativa. Entre em contato com o suporte.']);
        exit;
    }

    // Senha incorreta
    if (!password_verify($senha, $user['password_hash'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
        exit;
    }

    // ── Tudo certo — inicia sessão ────────────────────────────
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'         => $user['id'],
        'company_id' => $user['company_id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
    ];

    // Token remember me
    $token     = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $pdo->prepare("
        INSERT INTO user_sessions (user_id, token_hash, ip_address, user_agent, expires_at)
        VALUES (:user_id, :token_hash, :ip, :ua, DATE_ADD(NOW(), INTERVAL 30 DAY))
    ")->execute([
        ':user_id'    => $user['id'],
        ':token_hash' => $tokenHash,
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        ':ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    setcookie('remember_token', $token, [
        'expires'  => time() + (60 * 60 * 24 * 30),
        'path'     => '/',
        'httponly' => true,
        'secure'   => false,
        'samesite' => 'Lax',
    ]);

    // Atualiza último login
    $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id")
        ->execute([':id' => $user['id']]);

    ob_clean();
    echo json_encode([
        'success'  => true,
        'message'  => 'Login realizado com sucesso.',
        'redirect' => $raiz . '/base.php',
    ]);

} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor. Tente novamente.',
    ]);
}