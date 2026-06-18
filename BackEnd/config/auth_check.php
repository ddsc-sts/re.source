<?php
// BackEnd/config/auth_check.php
// Inclua este arquivo no topo de QUALQUER página protegida.
// Ele garante que o usuário está logado e expõe as variáveis $user_id e $company_id.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']['company_id'])) {
    header("Location: /RE.SOURCE/login.php");
    exit();
}

// Atalhos prontos para usar em qualquer página
$user_id    = (int) $_SESSION['user']['id'];
$company_id = (int) $_SESSION['user']['company_id'];