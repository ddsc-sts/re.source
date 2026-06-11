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
$theme    = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'system';
$language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'pt-BR';

// 2. TRATAMENTO DE CHECKBOXES/SWITCHES (Se não marcar, o PHP recebe como null, então forçamos 0)
$notify_proposals = isset($_POST['notify_proposals']) ? 1 : 0;
$notify_chat      = isset($_POST['notify_chat']) ? 1 : 0;
$notify_marketing = isset($_POST['notify_marketing']) ? 1 : 0;

try {
    /* 3. ATUALIZAÇÃO NO BANCO DE DADOS
       Nota: Aqui assumimos que esses campos existem na sua tabela 'companies' 
       ou em uma tabela relacionada chamada 'company_preferences'. 
       Abaixo faremos a query atualizando diretamente na tabela 'companies'.
    */
    $sql = "UPDATE companies SET 
                theme = ?, 
                language = ?, 
                notify_proposals = ?, 
                notify_chat = ?, 
                notify_marketing = ? 
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $theme,
        $language,
        $notify_proposals,
        $notify_chat,
        $notify_marketing,
        $company_id
    ]);

    // Define a mensagem de sucesso na sessão para exibir na tela
    $_SESSION['success'] = "Preferências do sistema atualizadas com sucesso!";
    
    // Opcional: Atualizar a sessão atual do usuário se você usar o tema dinamicamente via PHP
    $_SESSION['user_theme'] = $theme;

} catch (PDOException $e) {
    // Caso sua tabela ainda não tenha essas colunas, o sistema avisa sem quebrar
    $_SESSION['error'] = "Erro ao salvar preferências: " . $e->getMessage();
}

// Redireciona de volta para a página de configurações
header("Location: configuracoes.php");
exit();