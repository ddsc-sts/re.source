<?php

session_start();

require_once '../config/conexao.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';

if (empty($email) || empty($senha)) {

    echo json_encode([
        'success' => false,
        'message' => 'Preencha todos os os campos.'
    ]);

    exit;
}

try {

    $sql = "
        SELECT 
            users.id,
            users.company_id,
            users.name,
            users.email,
            users.password_hash,
            users.role,
            users.is_active,
            companies.status AS company_status
        FROM users
        INNER JOIN companies 
            ON companies.id = users.company_id
        WHERE users.email = :email
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':email' => $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // usuário não encontrado
    if (!$user) {

        echo json_encode([
            'success' => false,
            'message' => 'E-mail ou senha inválidos.'
        ]);

        exit;
    }

    // usuário desativado
    if (!$user['is_active']) {

        echo json_encode([
            'success' => false,
            'message' => 'Usuário desativado.'
        ]);

        exit;
    }

    // empresa suspensa
    if ($user['company_status'] !== 'active') {

        echo json_encode([
            'success' => false,
            'message' => 'Empresa suspensa.'
        ]);

        exit;
    }

    // verifica senha
    if (!password_verify($senha, $user['password_hash'])) {

        echo json_encode([
            'success' => false,
            'message' => 'E-mail ou senha inválidos.'
        ]);

        exit;
    }

    // segurança
    session_regenerate_id(true);

    // sessão do usuário
    $_SESSION['user'] = [
        'id' => $user['id'],
        'company_id' => $user['company_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];

    // token de sessão segura
    $token = bin2hex(random_bytes(32));

    // hash do token
    $tokenHash = hash('sha256', $token);

    // salva no banco
    $insertSession = $pdo->prepare("
        INSERT INTO user_sessions (
            user_id,
            token_hash,
            ip_address,
            user_agent,
            expires_at
        ) VALUES (
            :user_id,
            :token_hash,
            :ip_address,
            :user_agent,
            DATE_ADD(NOW(), INTERVAL 30 DAY)
        )
    ");

    $insertSession->execute([
        ':user_id' => $user['id'],
        ':token_hash' => $tokenHash,
        ':ip_address' => $_SERVER['REMOTE_ADDR'],
        ':user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);

    // cookie remember me
    setcookie(
        'remember_token',
        $token,
        [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'httponly' => true,
            'secure' => false,
            'samesite' => 'Lax'
        ]
    );

    // atualiza último login
    $updateLogin = $pdo->prepare("
        UPDATE users
        SET last_login_at = NOW()
        WHERE id = :id
    ");

    $updateLogin->execute([
        ':id' => $user['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso.',
        'redirect' => '/re.source/dashboard.php'
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor.',
        'error' => $e->getMessage()
    ]);
}