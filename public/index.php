<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PrintWise\Core\Router;

// Iniciar sessão
session_start();

// Carregar configurações
$config = require_once __DIR__ . '/../config/config.php';

// Inicializar o Router
$router = new Router();

// Definir rotas públicas
$router->get('/', 'HomeController@index');
$router->get('/about', 'HomeController@about');
$router->get('/contact', 'HomeController@contact');

// Rotas de autenticação
$router->get('/auth/login', 'AuthController@loginForm');
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/logout', 'AuthController@logout');

// Rotas do painel de administração
$router->get('/admin', 'AdminController@index');
$router->get('/admin/dashboard', 'AdminController@dashboard');

// Rotas de clientes
$router->get('/admin/clients', 'ClientController@index');
$router->get('/admin/clients/new', 'ClientController@create');
$router->post('/admin/clients/store', 'ClientController@store');
$router->get('/admin/clients/edit/{id}', 'ClientController@edit');
$router->post('/admin/clients/update/{id}', 'ClientController@update');
$router->post('/admin/clients/delete/{id}', 'ClientController@delete');

// Rotas de empresas
$router->get('/admin/companies', 'CompanyController@index');
$router->get('/admin/companies/new', 'CompanyController@create');
$router->post('/admin/companies/store', 'CompanyController@store');
$router->get('/admin/companies/edit/{id}', 'CompanyController@edit');
$router->post('/admin/companies/update/{id}', 'CompanyController@update');
$router->post('/admin/companies/delete/{id}', 'CompanyController@delete');

// Rotas de orçamentos
$router->get('/admin/quotes', 'QuoteController@index');
$router->get('/admin/quotes/new', 'QuoteController@create');
$router->post('/admin/quotes/store', 'QuoteController@store');
$router->get('/admin/quotes/edit/{id}', 'QuoteController@edit');
$router->post('/admin/quotes/update/{id}', 'QuoteController@update');
$router->get('/admin/quotes/view/{id}', 'QuoteController@show');
$router->post('/admin/quotes/delete/{id}', 'QuoteController@delete');

// Rota pública para aprovação de orçamentos
$router->get('/quotes/view/{hash}', 'QuoteController@publicView');
$router->post('/quotes/approve/{hash}', 'QuoteController@approve');
$router->post('/quotes/reject/{hash}', 'QuoteController@reject');

// Rotas para controle de desperdício
$router->get('/admin/waste', 'WasteController@index');
$router->get('/admin/waste/new/{quoteId}', 'WasteController@create');
$router->post('/admin/waste/store', 'WasteController@store');

// Rota 404 para página não encontrada
$router->notFound('ErrorController@notFound');

// Resolver a rota atual
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remover o caminho base da URI se necessário
$basePath = parse_url($config['app_url'], PHP_URL_PATH) ?? '';
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Adicionar barra inicial se não existir
if ($uri[0] !== '/') {
    $uri = '/' . $uri;
}

try {
    // Resolver a rota
    $route = $router->resolve($method, $uri);
    
    // Obter o controller e action
    $controllerName = '\\PrintWise\\Controllers\\' . $route['controller'];
    $action = $route['action'];
    $params = $route['params'];
    
    // Instanciar o controller
    $controller = new $controllerName();
    
    // Chamar o método com os parâmetros extraídos
    $response = $controller->$action(...$params);
    
    // Imprimir a resposta
    echo $response;
} catch (Exception $e) {
    if ($config['debug']) {
        echo '<h1>Erro</h1>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        echo 'Ocorreu um erro inesperado.';
    }
}