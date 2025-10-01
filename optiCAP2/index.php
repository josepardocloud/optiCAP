<?php
// index.php
session_start();

// Definir rutas absolutas
define('ROOT_PATH', dirname(__FILE__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Cargar configuración
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/database.php';

// Autocargador de clases
spl_autoload_register(function($class) {
    $paths = [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/core/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Manejo de errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr en $errfile línea $errline");
    if (DEBUG_MODE) {
        echo "<div class='alert alert-danger'>Error: $errstr</div>";
    }
});

// Router principal
$router = new Router();

// Rutas de autenticación
$router->add('login', 'AuthController@login');
$router->add('logout', 'AuthController@logout');
$router->add('recuperar-password', 'AuthController@recuperarPassword');

// Rutas del dashboard
$router->add('dashboard', 'DashboardController@index');
$router->add('dashboard/admin', 'DashboardController@admin');
$router->add('dashboard/supervisor', 'DashboardController@supervisor');
$router->add('dashboard/usuario', 'DashboardController@usuario');

// Rutas de requerimientos
$router->add('requerimientos', 'RequerimientoController@listar');
$router->add('requerimientos/crear', 'RequerimientoController@crear');
$router->add('requerimientos/editar/(\d+)', 'RequerimientoController@editar');
$router->add('requerimientos/detalle/(\d+)', 'RequerimientoController@detalle');
$router->add('requerimientos/imprimir/(\d+)', 'RequerimientoController@imprimir');

// Rutas de actividades
$router->add('actividades/editar/(\d+)', 'ActividadController@editar');
$router->add('actividades/timeline/(\d+)', 'ActividadController@timeline');

// Rutas de usuarios
$router->add('usuarios', 'UsuarioController@listar');
$router->add('usuarios/crear', 'UsuarioController@crear');
$router->add('usuarios/editar/(\d+)', 'UsuarioController@editar');
$router->add('usuarios/permisos/(\d+)', 'UsuarioController@permisos');

// Rutas de procesos
$router->add('procesos', 'ProcesoController@listar');
$router->add('procesos/editar/(\d+)', 'ProcesoController@editar');
$router->add('procesos/actividades/(\d+)', 'ProcesoController@actividades');

// Rutas de incidencias
$router->add('incidencias', 'IncidenciaController@listar');
$router->add('incidencias/reportar', 'IncidenciaController@reportar');
$router->add('incidencias/resolver/(\d+)', 'IncidenciaController@resolver');

// Rutas de reportes
$router->add('reportes', 'ReporteController@dashboard');
$router->add('reportes/exportar', 'ReporteController@exportar');
$router->add('reportes/metricas', 'ReporteController@metricas');

// Rutas de configuración
$router->add('configuracion', 'ConfiguracionController@sistema');
$router->add('configuracion/email', 'ConfiguracionController@email');
$router->add('configuracion/sla', 'ConfiguracionController@sla');

// Ruta por defecto
$router->add('', 'AuthController@login');

// Ejecutar router
$url = $_GET['url'] ?? '';
$router->dispatch($url);
?>