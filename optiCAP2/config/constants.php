<?php
// Constantes del sistema
define('SITE_NAME', 'OptiCAP2');
define('SITE_URL', 'http://localhost/opticap2');
define('SITE_PATH', dirname(dirname(__FILE__)));

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'opticap2');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de sesión
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
define('SESSION_NAME', 'opticap2_session');

// Configuración de archivos
define('MAX_FILE_SIZE', 10485760); // 10MB en bytes
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);
define('UPLOAD_PATH', SITE_PATH . '/public/uploads/');

// Configuración de email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'sistema@opticap2.com');
define('SMTP_PASS', 'password');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// Roles del sistema
define('ROL_ADMINISTRADOR', 1);
define('ROL_SUPERVISOR', 2);
define('ROL_SUPER_USUARIO', 3);
define('ROL_USUARIO', 4);

// Estados de requerimientos
define('ESTADO_PENDIENTE', 'pendiente');
define('ESTADO_EN_PROCESO', 'en_proceso');
define('ESTADO_COMPLETADO', 'completado');
define('ESTADO_CANCELADO', 'cancelado');

// Estados de actividades
define('ACTIVIDAD_PENDIENTE', 'pendiente');
define('ACTIVIDAD_EN_PROCESO', 'en_proceso');
define('ACTIVIDAD_FINALIZADO', 'finalizado');
define('ACTIVIDAD_RECHAZADO', 'rechazado');
define('ACTIVIDAD_NO_APLICA', 'no_aplica');

// Estados de incidencias
define('INCIDENCIA_PENDIENTE', 'pendiente');
define('INCIDENCIA_EN_PROCESO', 'en_proceso');
define('INCIDENCIA_RESUELTO', 'resuelto');

// Prioridades de incidencias
define('PRIORIDAD_BAJA', 'baja');
define('PRIORIDAD_MEDIA', 'media');
define('PRIORIDAD_ALTA', 'alta');

// Tipos de proceso
define('PROCESO_BIENES', 1);
define('PROCESO_SERVICIOS', 2);

// Configuración de seguridad
define('MAX_LOGIN_ATTEMPTS', 4);
define('LOCKOUT_TIME', 1800); // 30 minutos en segundos
define('PASSWORD_MIN_LENGTH', 6);

// Configuración de notificaciones
define('NOTIFICATION_DAYS_BEFORE_DUE', 3);
define('EMAIL_ENABLED', true);

// Modo de desarrollo
define('DEBUG_MODE', true);
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR

// Versión del sistema
define('SYSTEM_VERSION', '2.0.0');
define('LAST_UPDATE', '2024-01-01');

// Rutas de la aplicación
define('APP_PATH', SITE_PATH . '/app');
define('CONTROLLERS_PATH', APP_PATH . '/controllers');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEWS_PATH', APP_PATH . '/views');
define('CORE_PATH', APP_PATH . '/core');
define('PUBLIC_PATH', SITE_PATH . '/public');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

// Configuración de reportes
define('REPORT_MAX_ROWS', 10000);
define('EXPORT_TIMEOUT', 300); // 5 minutos para exportaciones grandes

// Límites del sistema
define('MAX_USERS_PER_PAGE', 50);
define('MAX_REQUERIMIENTOS_PER_PAGE', 100);
define('MAX_ACTIVIDADES_PER_PAGE', 200);
?>