<?php
class AuthController {
    private $usuarioModel;
    
    public function __construct() {
        $this->usuarioModel = new Usuario();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            
            $usuario = $this->usuarioModel->obtenerPorEmail($email);
            
            if ($usuario && AuthHelper::verifyPassword($password, $usuario['password'])) {
                if (!$usuario['activo']) {
                    $_SESSION['error'] = 'Tu cuenta está desactivada';
                    header('Location: ' . BASE_URL . 'auth/login');
                    exit();
                }
                
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_nombre'] = $usuario['nombre'];
                $_SESSION['user_email'] = $usuario['email'];
                $_SESSION['user_role'] = $usuario['rol'];
                $_SESSION['user_area'] = $usuario['id_area'];
                $_SESSION['primer_login'] = $usuario['primer_login'];
                
                // Log de acceso
                $this->usuarioModel->registrarLog($usuario['id'], 'LOGIN', 'Inicio de sesión exitoso');
                
                if ($usuario['primer_login']) {
                    header('Location: ' . BASE_URL . 'auth/cambiarPassword');
                } else {
                    header('Location: ' . BASE_URL . 'dashboard');
                }
                exit();
            } else {
                $_SESSION['error'] = 'Credenciales incorrectas';
                header('Location: ' . BASE_URL . 'auth/login');
                exit();
            }
        }
        
        require_once 'app/views/auth/login.php';
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->usuarioModel->registrarLog($_SESSION['user_id'], 'LOGOUT', 'Cierre de sesión');
        }
        
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login');
        exit();
    }
    
    public function cambiarPassword() {
        AuthHelper::checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            $usuario = $this->usuarioModel->obtenerPorId($_SESSION['user_id']);
            
            if (!AuthHelper::verifyPassword($currentPassword, $usuario['password'])) {
                $_SESSION['error'] = 'La contraseña actual es incorrecta';
            } elseif ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Las nuevas contraseñas no coinciden';
            } elseif (strlen($newPassword) < 6) {
                $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres';
            } else {
                $hashedPassword = AuthHelper::hashPassword($newPassword);
                if ($this->usuarioModel->actualizarPassword($_SESSION['user_id'], $hashedPassword)) {
                    $this->usuarioModel->marcarPrimerLoginCompletado($_SESSION['user_id']);
                    $_SESSION['primer_login'] = false;
                    $_SESSION['success'] = 'Contraseña actualizada correctamente';
                    header('Location: ' . BASE_URL . 'dashboard');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error al actualizar la contraseña';
                }
            }
        }
        
        require_once 'app/views/auth/cambiar_password.php';
    }
    
    public function recuperarPassword() {
        // Implementar recuperación de contraseña
    }
}
?>