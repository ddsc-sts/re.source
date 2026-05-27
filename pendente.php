<?php
session_start();

// Se não há cadastro pendente na sessão, volta para o cadastro
if (empty($_SESSION['cadastro_pendente']['email'])) {
    $raiz = rtrim(str_replace('pendente.php', '', $_SERVER['SCRIPT_NAME']), '/');
    header("Location: " . $raiz . "/cadastro.php");
    exit;
}

include __DIR__ . '/FrontEnd/templates/pendente.html';