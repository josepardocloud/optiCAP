<?php
/**
 * Middleware de autenticación
 */

class AuthMiddleware {
    
    public static function handle($requiredRole = null) {
        // Verificar si el usuario está autenticado
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }
        
        // Verificar si es el primer login y redirigir a cambio de contraseña
        if (isset($_SESSION['primer_login']) && $_SESSION['primer_login'] && 
            !strpos($_SERVER['REQUEST_URI'], 'cambiarPassword')) {
            header('Location: ' . BASE_URL . 'auth/cambiarPassword');
            exit();
        }
        
        // Verificar rol si se especifica
        if ($requiredRole && !self::checkRole($requiredRole)) {
            self::handleUnauthorized();
        }
        
        // Verificar timeout de sesión
        self::checkSessionTimeout();
        
        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    private static function checkRole($requiredRole) {
        $userRole = $_SESSION['user_role'] ?? 'usuario';
        
        $rolesHierarchy = [
            'usuario' => 1,
            'proceso' => 2,
            'supervisor' => 3,
            'admin' => 4
        ];
        
        $userLevel = $rolesHierarchy[$userRole] ?? 0;
        $requiredLevel = $rolesHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    private static function checkSessionTimeout() {
        $timeout = Config::SESSION_TIMEOUT;
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        
        if (time() - $lastActivity > $timeout) {
            session_destroy();
            $_SESSION['error'] = 'La sesión ha expirado por inactividad';
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }
    }
    
    private static function handleUnauthorized() {
        http_response_code(403);
        
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No tiene permisos para acceder a este recurso'
            ]);
        } else {
            // Mostrar página de error 403
            $errorController = new ErrorController();
            $errorController->showError(403, 'Acceso no autorizado');
        }
        exit();
    }
}
?>