<?php
// salvar_preferencias.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php"; 

// Proteção para garantir o ID da empresa logada
$company_id = $_SESSION['company_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: configuracoes.php");
    exit();
}

// 1. CAPTURA E SANITIZAÇÃO DOS SELECTS
$theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'system';

// 2. TRATAMENTO DE CHECKBOXES/SWITCHES (Se não marcar, o PHP recebe como null, então forçamos 0)
$notify_proposals = isset($_POST['notify_proposals']) ? 1 : 0;
$notify_chat      = isset($_POST['notify_chat']) ? 1 : 0;

try {
    /* 3. ATUALIZAÇÃO NO BANCO DE DADOS
       Atualizando apenas os campos que restaram nas configurações.
    */
    $sql = "UPDATE companies SET 
                theme = ?, 
                notify_proposals = ?, 
                notify_chat = ?
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $theme,
        $notify_proposals,
        $notify_chat,
        $company_id
    ]);

    // Define a mensagem de sucesso na sessão para exibir na tela
    $_SESSION['success'] = "Preferências do sistema updated!";
    $_SESSION['user_theme'] = $theme;

} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao salvar preferências: " . $e->getMessage();
}

// Redireciona de volta para a página de configurações
header("Location: configuracoes.php");
exit();