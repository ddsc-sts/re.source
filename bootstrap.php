<?php
// bootstrap.php - Centraliza todas as configurações

// Define o caminho raiz do projeto
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('APP_PATH', ROOT_PATH . '/app');
define('CONTROLLER_PATH', APP_PATH . '/Controllers');
define('VIEW_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Carrega a conexão com o banco de dados
require_once CONFIG_PATH . '/conexao.php';

// Inicia a sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função helper para carregar views
function view($viewName, $data = []) {
    extract($data);
    require_once VIEW_PATH . '/' . $viewName . '.php';
}