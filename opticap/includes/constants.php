<?php
/**
 * Constantes globales del sistema OptiCAP
 */

// Versión del sistema
define('OPTICAP_VERSION', '1.0.0');
define('OPTICAP_RELEASE_DATE', '2024-01-01');

// Entornos de ejecución
define('ENV_PRODUCTION', 'production');
define('ENV_STAGING', 'staging');
define('ENV_DEVELOPMENT', 'development');

// Determinar entorno actual
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 
        getenv('OPTICAP_ENV') ?: 
        (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ? ENV_DEVELOPMENT : ENV_PRODUCTION)
    );
}

// Niveles de log
define('LOG_ERROR', 'ERROR');
define('LOG_WARNING', 'WARNING');
define('LOG_INFO', 'INFO');
define('LOG_DEBUG', 'DEBUG');

// Roles de usuario
define('ROLE_ADMIN', 'admin');
define('ROLE_SUPERVISOR', 'supervisor');
define('ROLE_PROCESS', 'proceso');
define('ROLE_USER', 'usuario');

// Estados de requerimientos
define('REQ_PENDING', 'pendiente');
define('REQ_IN_PROGRESS', 'en_proceso');
define('REQ_COMPLETED', 'completado');
define('REQ_CANCELLED', 'cancelado');

// Estados de actividades
define('ACT_PENDING', 'pendiente');
define('ACT_IN_PROGRESS', 'en_proceso');
define('ACT_COMPLETED', 'completado');
define('ACT_OVERDUE', 'atrasado');

// Límites del sistema
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos en segundos
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
define('PASSWORD_MIN_LENGTH', 6);
define('UPLOAD_MAX_SIZE', 10485760); // 10MB en bytes
define('PAGINATION_LIMIT', 10);

// Formatos de fecha y hora
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('DATE_FORMAT_DB', 'Y-m-d');
define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');

// Paths del sistema
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(__FILE__) . DS . '..') . DS);
define('APP_PATH', ROOT_PATH . 'app' . DS);
define('VIEWS_PATH', APP_PATH . 'views' . DS);
define('UPLOADS_PATH', ROOT_PATH . 'assets' . DS . 'uploads' . DS);
define('LOGS_PATH', ROOT_PATH . 'logs' . DS);
define('TEMP_PATH', ROOT_PATH . 'temp' . DS);

// URLs del sistema
define('BASE_URL', (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
    '://' . $_SERVER['HTTP_HOST'] . 
    str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']) . '/'
));

// Configuración de email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'notificaciones@opticap.com');
define('SMTP_PASS', 'password');
define('SMTP_FROM', 'notificaciones@opticap.com');
define('SMTP_FROM_NAME', 'Sistema OptiCAP');

// Tipos de archivo permitidos
define('ALLOWED_FILE_TYPES', [
    'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'
]);

// Configuración de reportes
define('REPORT_MAX_ROWS', 1000);
define('CHART_COLORS', [
    '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
    '#6c757d', '#343a40', '#fd7e14', '#e83e8c', '#6f42c1'
]);

// Mensajes del sistema
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

// Códigos de error
define('ERR_DB_CONNECTION', 1001);
define('ERR_AUTH_REQUIRED', 1002);
define('ERR_PERMISSION_DENIED', 1003);
define('ERR_VALIDATION_FAILED', 1004);
define('ERR_FILE_UPLOAD', 1005);
define('ERR_NOT_FOUND', 1006);
define('ERR_SYSTEM', 1007);

// Configuración de cache
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hora
define('CACHE_DIR', TEMP_PATH . 'cache' . DS);

// Configuración de backup
define('BACKUP_ENABLED', true);
define('BACKUP_MAX_FILES', 30);
define('BACKUP_DIR', ROOT_PATH . 'backups' . DS);

// Validar que los directorios necesarios existan
$required_dirs = [LOGS_PATH, UPLOADS_PATH, TEMP_PATH, CACHE_DIR, BACKUP_DIR];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Configuración específica por entorno
switch (ENVIRONMENT) {
    case ENV_DEVELOPMENT:
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        define('DEBUG_MODE', true);
        break;
        
    case ENV_STAGING:
        error_reporting(E_ALL & ~E_NOTICE);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        define('DEBUG_MODE', true);
        break;
        
    case ENV_PRODUCTION:
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        define('DEBUG_MODE', false);
        break;
}

// Función para obtener constantes de forma segura
function get_constant($name, $default = null) {
    return defined($name) ? constant($name) : $default;
}

// Función para verificar si una constante está definida
function constant_exists($name) {
    return defined($name);
}

// Función para obtener todas las constantes del sistema
function get_system_constants() {
    $constants = get_defined_constants(true);
    return isset($constants['user']) ? $constants['user'] : [];
}

// Función para validar un rol de usuario
function is_valid_role($role) {
    $valid_roles = [ROLE_ADMIN, ROLE_SUPERVISOR, ROLE_PROCESS, ROLE_USER];
    return in_array($role, $valid_roles);
}

// Función para validar un estado de requerimiento
function is_valid_requirement_status($status) {
    $valid_statuses = [REQ_PENDING, REQ_IN_PROGRESS, REQ_COMPLETED, REQ_CANCELLED];
    return in_array($status, $valid_statuses);
}

// Función para validar un estado de actividad
function is_valid_activity_status($status) {
    $valid_statuses = [ACT_PENDING, ACT_IN_PROGRESS, ACT_COMPLETED, ACT_OVERDUE];
    return in_array($status, $valid_statuses);
}

// Función para obtener el nombre legible de un rol
function get_role_name($role) {
    $names = [
        ROLE_ADMIN => 'Administrador',
        ROLE_SUPERVISOR => 'Supervisor',
        ROLE_PROCESS => 'Usuario Proceso',
        ROLE_USER => 'Usuario'
    ];
    return isset($names[$role]) ? $names[$role] : 'Desconocido';
}

// Función para obtener el nombre legible de un estado
function get_status_name($status) {
    $names = [
        REQ_PENDING => 'Pendiente',
        REQ_IN_PROGRESS => 'En Proceso',
        REQ_COMPLETED => 'Completado',
        REQ_CANCELLED => 'Cancelado',
        ACT_PENDING => 'Pendiente',
        ACT_IN_PROGRESS => 'En Proceso',
        ACT_COMPLETED => 'Completado',
        ACT_OVERDUE => 'Atrasado'
    ];
    return isset($names[$status]) ? $names[$status] : 'Desconocido';
}
?>