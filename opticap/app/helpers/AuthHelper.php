<?php
class AuthHelper {
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function checkAuth() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }
    }
    
    public static function hasRole($requiredRole) {
        if (!self::isLoggedIn()) return false;
        
        $userRole = $_SESSION['user_role'] ?? '';
        $rolesHierarchy = [
            'admin' => 4,
            'supervisor' => 3,
            'proceso' => 2,
            'usuario' => 1
        ];
        
        $userLevel = $rolesHierarchy[$userRole] ?? 0;
        $requiredLevel = $rolesHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    public static function requireRole($requiredRole) {
        self::checkAuth();
        
        if (!self::hasRole($requiredRole)) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit();
        }
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>