<?php
// BackEnd/auth/sessao-info.php — retorna dados da sessão de cadastro pendente

ob_start();
session_start();

header('Content-Type: application/json; charset=utf-8');

$pendente = $_SESSION['cadastro_pendente'] ?? null;

if (!$pendente) {
    ob_clean();
    echo json_encode(['ok' => false]);
    exit;
}

ob_clean();
echo json_encode([
    'ok'    => true,
    'email' => $pendente['email'] ?? '',
]);