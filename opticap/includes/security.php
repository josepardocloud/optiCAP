<?php
/**
 * Medidas de seguridad para el sistema OptiCAP
 */

// Prevenir acceso directo a archivos
if (!defined('BASE_URL')) {
    die('Acceso directo no permitido');
}

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Función para limpiar datos de entrada
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}

// Validación de CSRF token
function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            log_message('SECURITY', 'Intento de acceso sin token CSRF', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]);
            return false;
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            log_message('SECURITY', 'Token CSRF inválido', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'expected' => $_SESSION['csrf_token'],
                'received' => $_POST['csrf_token']
            ]);
            return false;
        }
    }
    return true;
}

// Protección contra fuerza bruta
function check_brute_force($username, $max_attempts = 5, $lockout_time = 900) { // 15 minutos
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();
    
    // Limpiar intentos antiguos
    $stmt = $GLOBALS['pdo']->prepare("
        DELETE FROM login_attempts 
        WHERE attempt_time < ?
    ");
    $stmt->execute([$now - $lockout_time]);
    
    // Contar intentos recientes
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE ip_address = ? AND attempt_time > ?
    ");
    $stmt->execute([$ip, $now - $lockout_time]);
    $result = $stmt->fetch();
    
    if ($result['attempts'] >= $max_attempts) {
        log_message('SECURITY', 'Intento de fuerza bruta detectado', [
            'ip' => $ip,
            'username' => $username,
            'attempts' => $result['attempts']
        ]);
        return true;
    }
    
    return false;
}

// Registrar intento de login fallido
function record_failed_attempt($username) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $GLOBALS['pdo']->prepare("
        INSERT INTO login_attempts (ip_address, username, attempt_time, user_agent) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$ip, $username, $now, $user_agent]);
}

// Validación de archivos subidos
function validate_uploaded_file($file) {
    $errors = [];
    
    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Error al subir el archivo: " . $file['error'];
        return $errors;
    }
    
    // Verificar tamaño
    if ($file['size'] > Config::MAX_FILE_SIZE) {
        $errors[] = "El archivo excede el tamaño máximo permitido";
    }
    
    // Verificar tipo MIME
    $allowed_mimes = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_mimes)) {
        $errors[] = "Tipo de archivo no permitido";
    }
    
    // Verificar extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, Config::ALLOWED_FILE_TYPES)) {
        $errors[] = "Extensión de archivo no permitida";
    }
    
    // Verificar contenido (para imágenes)
    if (strpos($mime, 'image/') === 0) {
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            $errors[] = "El archivo no es una imagen válida";
        }
    }
    
    return $errors;
}

// Sanitización de nombres de archivo
function sanitize_filename($filename) {
    // Remover caracteres peligrosos
    $filename = preg_replace('/[^a-zA-Z0-9\.\_\-]/', '_', $filename);
    // Limitar longitud
    $filename = substr($filename, 0, 255);
    // Prevenir path traversal
    $filename = basename($filename);
    
    return $filename;
}

// Validación de email
function validate_email_security($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Prevenir injection de headers
    if (preg_match('/[\r\n]/', $email)) {
        return false;
    }
    
    // Verificar dominio
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, "MX")) {
        return false;
    }
    
    return true;
}

// Escape para consultas SQL (usar prepared statements es mejor)
function sql_escape($string) {
    global $pdo;
    return $pdo ? $pdo->quote($string) : addslashes($string);
}

// Validación de URL
function validate_url($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Verificar protocolos permitidos
    $allowed_protocols = ['http', 'https'];
    $protocol = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($protocol, $allowed_protocols)) {
        return false;
    }
    
    return true;
}

// Prevenir clickjacking
function prevent_clickjacking() {
    header('X-Frame-Options: DENY');
    header('Content-Security-Policy: frame-ancestors \'none\'');
}

// Prevenir MIME sniffing
function prevent_mime_sniffing() {
    header('X-Content-Type-Options: nosniff');
}

// HSTS header
function enable_hsts() {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// CSP header
function set_content_security_policy() {
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
        "img-src 'self' data: https:",
        "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
        "connect-src 'self'",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'"
    ];
    
    header("Content-Security-Policy: " . implode('; ', $csp));
}

// Validación de datos JSON
function validate_json($json_string) {
    json_decode($json_string);
    return json_last_error() === JSON_ERROR_NONE;
}

// Limpieza de datos JSON
function sanitize_json($data) {
    if (is_array($data)) {
        return array_map('sanitize_json', $data);
    } elseif (is_string($data)) {
        return clean_input($data);
    } elseif (is_object($data)) {
        $vars = get_object_vars($data);
        foreach ($vars as $key => $value) {
            $data->$key = sanitize_json($value);
        }
        return $data;
    }
    return $data;
}

// Detección de bots maliciosos
function is_malicious_bot() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $malicious_bots = [
        'bot', 'crawler', 'spider', 'scan', 'hack', 'exploit',
        'sqlmap', 'nikto', 'metasploit', 'burp', 'zap'
    ];
    
    foreach ($malicious_bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            return true;
        }
    }
    
    return false;
}

// Rate limiting básico
function check_rate_limit($identifier, $max_requests = 100, $time_window = 3600) {
    $key = 'rate_limit_' . md5($identifier);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'start_time' => $now
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    if ($now - $data['start_time'] > $time_window) {
        // Reiniciar ventana de tiempo
        $_SESSION[$key] = [
            'count' => 1,
            'start_time' => $now
        ];
        return true;
    }
    
    if ($data['count'] >= $max_requests) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// Log de actividades de seguridad
function log_security_event($event, $details = []) {
    $default_details = [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user_id'] ?? 'guest'
    ];
    
    $log_data = array_merge($default_details, $details);
    
    log_message('SECURITY', $event, $log_data);
}

// Inicializar medidas de seguridad
function init_security() {
    prevent_clickjacking();
    prevent_mime_sniffing();
    enable_hsts();
    set_content_security_policy();
    
    // Validar CSRF para requests POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
            http_response_code(403);
            die('Token de seguridad inválido');
        }
    }
    
    // Detectar bots maliciosos
    if (is_malicious_bot()) {
        log_security_event('Bot malicioso detectado');
        http_response_code(403);
        die('Acceso no permitido');
    }
    
    // Rate limiting para login
    if (strpos($_SERVER['REQUEST_URI'], 'auth/login') !== false) {
        if (!check_rate_limit('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) { // 5 intentos en 5 minutos
            http_response_code(429);
            die('Demasiados intentos de login. Por favor espere.');
        }
    }
}

// Ejecutar medidas de seguridad al inicio
init_security();
?>