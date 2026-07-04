<?php
// public/index.php — ÚNICO ponto de entrada da aplicação

require_once __DIR__ . '/../bootstrap.php';

// Autoloader tolerante a maiúsculas/minúsculas
// (ex: Listingcontroller.php, Searchcontroller.php não seguem o padrão PascalCase)
spl_autoload_register(function ($class) {
    $dirs = [CONTROLLER_PATH, APP_PATH . '/Middleware', APP_PATH . '/Services'];

    foreach ($dirs as $dir) {
        $exact = $dir . '/' . $class . '.php';
        if (file_exists($exact)) {
            require_once $exact;
            return;
        }

        // fallback case-insensitive
        foreach (glob($dir . '/*.php') as $file) {
            if (strcasecmp(basename($file, '.php'), $class) === 0) {
                require_once $file;
                return;
            }
        }
    }
});

// Carrega o mapa de rotas
$routes = require __DIR__ . '/../routes/web.php';

// Pega o path da URL, sem query string e sem barra final (exceto raiz)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove o prefixo do subdiretório (XAMPP local: /re.source)
$base = APP_BASE_PATH;
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}

$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

if (!isset($routes[$uri])) {
    http_response_code(404);
    echo '404 - Página não encontrada';
    exit;
}

$route = $routes[$uri];

// Middleware (ex: AdminAuth::required())
if (!empty($route['middleware'])) {
    foreach ($route['middleware'] as $middleware) {
        [$middlewareClass, $middlewareMethod] = $middleware;
        $middlewareClass::$middlewareMethod();
    }
}

[$controllerClass, $controllerMethod] = $route['action'];

// Permite rotas com parâmetros simples, ex: /anuncio com ?id= continua via $_GET
$controllerClass::$controllerMethod();
