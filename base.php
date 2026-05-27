<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /RE.SOURCE/login.php');
    exit;
}

include 'FrontEnd/templates/index.html';
?>