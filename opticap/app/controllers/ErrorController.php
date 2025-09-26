<?php
/**
 * Controlador para manejo de errores
 */
class ErrorController {
    
    public function showError($code = 404, $message = 'Página no encontrada') {
        http_response_code($code);
        
        $errorMessages = [
            400 => 'Solicitud incorrecta',
            401 => 'No autorizado',
            403 => 'Prohibido',
            404 => 'Página no encontrada',
            500 => 'Error interno del servidor',
            503 => 'Servicio no disponible'
        ];
        
        $errorTitle = $errorMessages[$code] ?? 'Error del sistema';
        
        $data = [
            'error_code' => $code,
            'error_title' => $errorTitle,
            'error_message' => $message,
            'show_login_link' => ($code == 401 || $code == 403) && !isset($_SESSION['user_id'])
        ];
        
        // Log del error
        $this->logError($code, $message);
        
        // Renderizar vista de error
        $this->renderErrorView($data);
    }
    
    public function notFound() {
        $this->showError(404, 'La página que buscas no existe.');
    }
    
    public function unauthorized() {
        $this->showError(401, 'Debes iniciar sesión para acceder a esta página.');
    }
    
    public function forbidden() {
        $this->showError(403, 'No tienes permisos para acceder a este recurso.');
    }
    
    public function internalError() {
        $this->showError(500, 'Ha ocurrido un error interno en el servidor.');
    }
    
    private function logError($code, $message) {
        $logMessage = sprintf(
            "[%s] Error %s: %s - IP: %s - URL: %s - User Agent: %s",
            date('Y-m-d H:i:s'),
            $code,
            $message,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['REQUEST_URI'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );
        
        // Log en archivo
        error_log($logMessage . PHP_EOL, 3, ROOT_PATH . 'logs/error.log');
        
        // Log en base de datos si hay usuario en sesión
        if (isset($_SESSION['user_id'])) {
            try {
                $usuarioModel = new Usuario();
                $usuarioModel->registrarLog(
                    $_SESSION['user_id'],
                    'ERROR_' . $code,
                    $message
                );
            } catch (Exception $e) {
                // Fallback si no se puede loguear en BD
                error_log('Error logging to database: ' . $e->getMessage());
            }
        }
    }
    
    private function renderErrorView($data) {
        // Headers para prevenir caching en páginas de error
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            // Respuesta JSON para APIs
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => $data['error_code'],
                    'message' => $data['error_message'],
                    'title' => $data['error_title']
                ]
            ]);
        } else {
            // Vista HTML para navegadores
            extract($data);
            require_once 'app/views/errors/error.php';
        }
        exit();
    }
    
    /**
     * Manejar excepciones no capturadas
     */
    public static function handleException($exception) {
        $errorController = new self();
        
        // Log detallado de la excepción
        error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
        
        if (ENVIRONMENT === 'development') {
            $message = $exception->getMessage() . " en " . $exception->getFile() . ":" . $exception->getLine();
        } else {
            $message = "Ha ocurrido un error inesperado. Por favor contacte al administrador.";
        }
        
        $errorController->showError(500, $message);
    }
    
    /**
     * Manejar errores de PHP
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorTypes = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        $errorType = $errorTypes[$errno] ?? 'Unknown Error';
        
        $logMessage = sprintf(
            "[%s] PHP %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $errorType,
            $errstr,
            $errfile,
            $errline
        );
        
        error_log($logMessage . PHP_EOL, 3, ROOT_PATH . 'logs/php_errors.log');
        
        // En producción, no mostrar errores al usuario
        if (ENVIRONMENT !== 'development') {
            return true;
        }
        
        // En desarrollo, mostrar errores
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>{$errorType}:</strong> {$errstr} <br>";
        echo "<small>en {$errfile} on line {$errline}</small>";
        echo "</div>";
        
        return true;
    }
    
    /**
     * Manejar shutdown para errores fatales
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorController = new self();
            
            $message = "Error fatal: {$error['message']} en {$error['file']}:{$error['line']}";
            error_log($message, 3, ROOT_PATH . 'logs/fatal_errors.log');
            
            $errorController->showError(500, "Ha ocurrido un error fatal en el servidor.");
        }
    }
}
?>