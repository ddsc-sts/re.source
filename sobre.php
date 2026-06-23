<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/BackEnd/config/conexao.php';
include 'FrontEnd/templates/sobre.html';
?>