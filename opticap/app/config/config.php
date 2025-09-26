<?php
// Configuración de la aplicación
class Config {
    // Base de datos
    const DB_HOST = 'localhost';
    const DB_NAME = 'opticap_db';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';
    
    // Aplicación
    const APP_NAME = 'OptiCAP';
    const APP_VERSION = '1.0';
    const APP_URL = 'http://localhost/opticap/';
    
    // Seguridad
    const SESSION_TIMEOUT = 3600; // 1 hora
    const PASSWORD_RESET_TIMEOUT = 1800; // 30 minutos
    
    // Uploads
    const MAX_FILE_SIZE = 10485760; // 10MB
    const ALLOWED_FILE_TYPES = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    
    // Email
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USER = 'notificaciones@opticap.com';
    const SMTP_PASS = 'password';
    const SMTP_FROM = 'notificaciones@opticap.com';
}

// Conexión a base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=" . Config::DB_CHARSET,
        Config::DB_USER,
        Config::DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error de conexión a la base de datos");
}
?>