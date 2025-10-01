<?php
// Configuración general del sistema
define('SITE_NAME', 'OptiCAP2');
define('SITE_URL', 'http://localhost/opticap2');
define('SITE_PATH', dirname(dirname(__FILE__)));

// Configuración de sesión
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configuración de archivos
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Configuración de email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'sistema@opticap2.com');
define('SMTP_PASS', 'password');
define('SMTP_PORT', 587);

// Niveles de usuario
define('ROL_SUPERUSUARIO', 1);
define('ROL_ADMINISTRADOR', 2);
define('ROL_SUPERVISOR', 3);
define('ROL_USUARIO', 4);

// Estados de requerimientos
define('ESTADO_PENDIENTE', 1);
define('ESTADO_EN_PROGRESO', 2);
define('ESTADO_COMPLETADO', 3);
define('ESTADO_CANCELADO', 4);
?>