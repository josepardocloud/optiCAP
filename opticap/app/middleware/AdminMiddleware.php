<?php
/**
 * Middleware para acceso de administrador
 */

class AdminMiddleware {
    
    public static function handle() {
        // Primero verificar autenticación básica
        AuthMiddleware::handle('admin');
        
        // Verificaciones adicionales específicas para admin
        if (!self::isUserActive()) {
            self::handleInactiveUser();
        }
        
        if (!self::checkAdminPrivileges()) {
            self::handleInsufficientPrivileges();
        }
        
        // Log de acceso administrativo
        self::logAdminAccess();
        
        return true;
    }
    
    private static function isUserActive() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorId($_SESSION['user_id']);
        
        return $usuario && $usuario['activo'];
    }
    
    private static function checkAdminPrivileges() {
        // Verificar si el usuario tiene privilegios de administrador
        $userRole = $_SESSION['user_role'] ?? '';
        
        if ($userRole !== 'admin') {
            return false;
        }
        
        // Verificaciones adicionales podrían ir aquí
        // Por ejemplo, verificar en una tabla de permisos especiales
        
        return true;
    }
    
    private static function handleInactiveUser() {
        session_destroy();
        $_SESSION['error'] = 'Su cuenta está desactivada';
        header('Location: ' . BASE_URL . 'auth/login');
        exit();
    }
    
    private static function handleInsufficientPrivileges() {
        http_response_code(403);
        
        // Log de intento de acceso no autorizado
        $usuarioModel = new Usuario();
        $usuarioModel->registrarLog(
            $_SESSION['user_id'] ?? 0,
            'ACCESS_DENIED',
            'Intento de acceso administrativo sin privilegios suficientes'
        );
        
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Privilegios insuficientes para acceso administrativo'
            ]);
        } else {
            // Mostrar página de error personalizada
            $errorController = new ErrorController();
            $errorController->showError(403, 'Privilegios insuficientes');
        }
        exit();
    }
    
    private static function logAdminAccess() {
        $usuarioModel = new Usuario();
        $usuarioModel->registrarLog(
            $_SESSION['user_id'],
            'ADMIN_ACCESS',
            'Acceso a panel administrativo: ' . $_SERVER['REQUEST_URI']
        );
    }
}
?>