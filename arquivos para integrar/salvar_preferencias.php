<?php
// salvar_preferencias.php
// CORREÇÃO: $_SESSION['company_id'] → $_SESSION['user']['company_id']

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php";
require_once __DIR__ . "/BackEnd/config/auth_check.php"; // define $company_id, redireciona se não logado

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: configuracoes.php");
    exit();
}

$theme             = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'system';
$notify_proposals  = isset($_POST['notify_proposals']) ? 1 : 0;
$notify_chat       = isset($_POST['notify_chat'])      ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        UPDATE companies SET 
            theme = ?, 
            notify_proposals = ?, 
            notify_chat = ?
        WHERE id = ?
    ");
    $stmt->execute([$theme, $notify_proposals, $notify_chat, $company_id]);

    $_SESSION['success']    = "Preferências salvas com sucesso!";
    $_SESSION['user_theme'] = $theme;

} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao salvar preferências: " . $e->getMessage();
}

header("Location: configuracoes.php");
exit();