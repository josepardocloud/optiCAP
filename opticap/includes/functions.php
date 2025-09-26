<?php
/**
 * Funciones auxiliares globales para el sistema OptiCAP
 */

/**
 * Sanitiza un string para prevenir XSS
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida un email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Formatea una fecha para mostrar al usuario
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Formatea una fecha y hora para mostrar al usuario
 */
function format_datetime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($datetime));
}

/**
 * Calcula la diferencia en días entre dos fechas
 */
function date_diff_days($start, $end) {
    $start = new DateTime($start);
    $end = new DateTime($end);
    return $start->diff($end)->days;
}

/**
 * Calcula días hábiles entre dos fechas (excluye fines de semana)
 */
function business_days($start, $end) {
    $start = new DateTime($start);
    $end = new DateTime($end);
    $end->modify('+1 day'); // Incluir el día final
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $days = 0;
    foreach ($period as $date) {
        if ($date->format('N') < 6) { // 1-5 (lunes a viernes)
            $days++;
        }
    }
    
    return $days;
}

/**
 * Genera un código único basado en un prefijo y timestamp
 */
function generate_unique_code($prefix = 'OPT') {
    return $prefix . date('Ymd') . substr(uniqid(), -5);
}

/**
 * Verifica si un archivo tiene una extensión permitida
 */
function is_allowed_file_type($filename, $allowed_types) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowed_types);
}

/**
 * Convierte bytes a formato legible (KB, MB, GB)
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Obtiene el client IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Registra un mensaje en el log del sistema
 */
function log_message($level, $message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $level = strtoupper($level);
    $context_str = !empty($context) ? json_encode($context) : '';
    
    $log_entry = "[$timestamp] $level: $message $context_str" . PHP_EOL;
    
    $log_file = ROOT_PATH . 'logs/app.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Redirige a una URL
 */
function redirect($url, $status_code = 303) {
    header('Location: ' . $url, true, $status_code);
    exit();
}

/**
 * Obtiene el valor de un array de forma segura
 */
function array_get($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Convierte un array a options para un select HTML
 */
function array_to_options($array, $selected = null, $use_keys = true) {
    $options = '';
    
    foreach ($array as $key => $value) {
        $opt_value = $use_keys ? $key : $value;
        $is_selected = ($selected == $opt_value) ? 'selected' : '';
        $options .= "<option value=\"$opt_value\" $is_selected>$value</option>";
    }
    
    return $options;
}

/**
 * Sanitiza output para JavaScript
 */
function js_escape($string) {
    return str_replace(
        ["'", '"', "\n", "\r", "\t", "<", ">", "&"],
        ["\\'", '\\"', "\\n", "\\r", "\\t", "\\x3C", "\\x3E", "\\x26"],
        $string
    );
}

/**
 * Genera un token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtiene la URL actual
 */
function current_url() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

/**
 * Pluraliza una palabra basada en un count
 */
function pluralize($count, $singular, $plural = null) {
    if ($plural === null) {
        $plural = $singular . 's';
    }
    return $count == 1 ? $singular : $plural;
}

/**
 * Trunca un texto a una longitud específica
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Convierte un string a slug URL-friendly
 */
function slugify($text) {
    // Reemplaza caracteres no alfanuméricos con guiones
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Convierte a minúsculas
    $text = strtolower($text);
    // Remueve caracteres no deseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Remueve guiones múltiples
    $text = preg_replace('~-+~', '-', $text);
    // Recorta guiones del inicio y final
    $text = trim($text, '-');
    
    return $text;
}
?>