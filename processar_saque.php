<?php
// processar_saque.php

session_start();
require_once __DIR__ . "/BackEnd/config/conexao.php"; 

$company_id = $_SESSION['user']['company_id'] ?? null;

if (!$company_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: estatisticas.php");
    exit;
}

$valor_saque = (float) $_POST['valor_saque'];
$chave_pix = trim($_POST['chave_pix']);

// 1. Recalcula o saldo de forma segura no servidor
try {
    $stmtTotalVendas = $pdo->prepare("SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations WHERE seller_company_id = ? AND status = 'concluded'");
    $stmtTotalVendas->execute([$company_id]);
    $total_ganho = (float) $stmtTotalVendas->fetchColumn();

    $stmtTotalSaques = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE company_id = ? AND status IN ('pending', 'completed')");
    $stmtTotalSaques->execute([$company_id]);
    $total_sacado = (float) $stmtTotalSaques->fetchColumn();

    $saldo_disponivel = $total_ganho - $total_sacado;

    // 2. Validações
    if ($valor_saque <= 0) {
        $_SESSION['saque_msg'] = "O valor do saque deve ser maior que zero.";
        $_SESSION['saque_tipo'] = "error";
    } elseif ($valor_saque > $saldo_disponivel) {
        $_SESSION['saque_msg'] = "Tentativa de saque bloqueada: Saldo insuficiente.";
        $_SESSION['saque_tipo'] = "error";
    } elseif (empty($chave_pix)) {
        $_SESSION['saque_msg'] = "A chave PIX é obrigatória.";
        $_SESSION['saque_tipo'] = "error";
    } else {
        // 3. Tudo certo! Registra o saque no banco
        $stmtInsert = $pdo->prepare("INSERT INTO withdrawals (company_id, amount, pix_key, status) VALUES (?, ?, ?, 'pending')");
        $stmtInsert->execute([$company_id, $valor_saque, $chave_pix]);

        $_SESSION['saque_msg'] = "Saque de R$ " . number_format($valor_saque, 2, ',', '.') . " solicitado com sucesso! O valor será transferido em breve.";
        $_SESSION['saque_tipo'] = "success";
    }

} catch (PDOException $e) {
    $_SESSION['saque_msg'] = "Erro de sistema ao processar saque: " . $e->getMessage();
    $_SESSION['saque_tipo'] = "error";
}

header("Location: estatisticas.php");
exit;