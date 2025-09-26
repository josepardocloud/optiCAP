<?php
session_start();

// Configuración de entorno
define('ENVIRONMENT', 'development');
if (ENVIRONMENT == 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Constantes del sistema
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/opticap/');
define('ROOT_PATH', realpath(dirname(__FILE__)) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'assets/uploads/');

// Cargar autoloader
require_once 'app/config/config.php';
require_once 'app/helpers/AuthHelper.php';
require_once 'app/models/Database.php';

// Manejo de rutas
$url = isset($_GET['url']) ? $_GET['url'] : 'dashboard';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

// Routing básico
$controllerName = isset($urlParts[0]) ? ucfirst($urlParts[0]) . 'Controller' : 'DashboardController';
$methodName = isset($urlParts[1]) ? $urlParts[1] : 'index';
$params = array_slice($urlParts, 2);

// Verificar si el controlador existe
$controllerFile = 'app/controllers/' . $controllerName . '.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerName();
    
    if (method_exists($controller, $methodName)) {
        call_user_func_array([$controller, $methodName], $params);
    } else {
        require_once 'app/views/errors/404.php';
    }
} else {
    require_once 'app/views/errors/404.php';
}
?>