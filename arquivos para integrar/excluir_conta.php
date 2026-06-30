<?php
// excluir_conta.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php"; 

// Garante que a requisição veio do formulário via POST por segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: configuracoes.php");
    exit();
}

// CORREÇÃO AQUI: Puxa o ID de dentro da array ['user'] conforme o diagnóstico mostrou
$company_id = $_SESSION['user']['company_id'] ?? null;

if (!$company_id) {
    header("Location: login.php");
    exit();
}

try {
    // Inicia uma transação no banco de dados para garantir consistência
    $pdo->beginTransaction();

    // 1. Atualiza o status da empresa para inativo e preenche a data de desativação
    $stmtCompany = $pdo->prepare("UPDATE companies SET status = 'inactive', deactivated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmtCompany->execute([$company_id]);

    // 2. Desativa todos os usuários (operadores e administradores) vinculados a esta empresa
    $stmtUsers = $pdo->prepare("UPDATE users SET is_active = 0, deleted_at = CURRENT_TIMESTAMP WHERE company_id = ?");
    $stmtUsers->execute([$company_id]);

    // 3. Modifica os anúncios desta empresa para 'paused' para saírem do marketplace imediatamente
    $stmtListings = $pdo->prepare("UPDATE listings SET status = 'paused', deleted_at = CURRENT_TIMESTAMP WHERE company_id = ?");
    $stmtListings->execute([$company_id]);

    // 4. Grava a ação no log de auditoria se a tabela existir
    try {
        $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (company_id, action, severity, ip_address, user_agent) VALUES (?, 'ACCOUNT_DEACTIVATED_BY_USER', 'critical', ?, ?)");
        $stmtAudit->execute([
            $company_id, 
            $_SERVER['REMOTE_ADDR'] ?? null, 
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e_audit) {
        // Se a tabela de logs não existir ou falhar, ignora silenciosamente para não travar a exclusão principal
    }

    // Confirma todas as alterações no banco de dados de uma só vez
    $pdo->commit();

    // 5. FLUXO DE LOGOUT: Destrói as variáveis de sessão para deslogar a máquina atual
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }

    session_destroy();

    // Redireciona o usuário de volta para a tela de login com aviso de sucesso
    header("Location: login.php?account=deleted");
    exit();

} catch (PDOException $e) {
    // Caso algo dê errado, cancela as alterações para não quebrar a integridade do banco
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    die("Erro crítico ao processar a exclusão da conta institucional: " . $e->getMessage());
}