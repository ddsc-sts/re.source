<?php
// pendente.php — raiz do projeto
$base  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$email = htmlspecialchars($_GET['email'] ?? '');
$erro  = htmlspecialchars($_GET['erro']  ?? '');
include 'FrontEnd/templates/pendente.html';