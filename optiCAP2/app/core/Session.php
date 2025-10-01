<?php
class Session {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public function destroy() {
        session_destroy();
        $_SESSION = [];
    }
    
    public function setFlash($key, $value) {
        $_SESSION['flash'][$key] = $value;
    }
    
    public function getFlash($key, $default = null) {
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
    
    public function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }
}
?>