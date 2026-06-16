<?php
// logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Limpa todas as variáveis de sessão
$_SESSION = array();

// 2. Destrói o cookie da sessão no navegador se ele existir
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

// 3. Destrói a sessão no servidor
session_destroy();

// 4. Redireciona para a página de login (ajuste o caminho se necessário)
header("Location: login.php");
exit();