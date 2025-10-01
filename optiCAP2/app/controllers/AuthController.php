<?php
class AuthController {
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Database();
    }
    
    public function login() {
        // Si ya está logueado, redirigir al dashboard
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            
            try {
                if ($this->auth->login($email, $password)) {
                    $this->redirectToDashboard();
                } else {
                    $error = "Credenciales incorrectas";
                    $this->renderView('auth/login', ['error' => $error]);
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
                $this->renderView('auth/login', ['error' => $error]);
            }
        } else {
            $this->renderView('auth/login');
        }
    }
    
    public function logout() {
        $this->auth->logout();
        header('Location: ' . SITE_URL . '/login');
        exit;
    }
    
    public function recuperarPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            
            // Verificar si el email existe
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ? AND estado = 'activo'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generar token de recuperación
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET token_recuperacion = ?, token_expiracion = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$token, $expira, $user['id']]);
                
                // Enviar email de recuperación
                $this->enviarEmailRecuperacion($user, $token);
                
                $success = "Se ha enviado un enlace de recuperación a su email";
                $this->renderView('auth/recuperar', ['success' => $success]);
            } else {
                $error = "Email no encontrado";
                $this->renderView('auth/recuperar', ['error' => $error]);
            }
        } else {
            $this->renderView('auth/recuperar');
        }
    }
    
    private function redirectToDashboard() {
        $user = $this->auth->getUser();
        $rol = $user['rol_nombre'] ?? '';
        
        switch ($rol) {
            case 'Administrador':
                header('Location: ' . SITE_URL . '/dashboard/admin');
                break;
            case 'Supervisor':
                header('Location: ' . SITE_URL . '/dashboard/supervisor');
                break;
            case 'Super Usuario':
                header('Location: ' . SITE_URL . '/dashboard/superusuario');
                break;
            default:
                header('Location: ' . SITE_URL . '/dashboard/usuario');
        }
        exit;
    }
    
    private function enviarEmailRecuperacion($user, $token) {
        $enlace = SITE_URL . "/reset-password?token=$token";
        
        $asunto = "Recuperación de Contraseña - " . SITE_NAME;
        $mensaje = "
            Hola {$user['nombre']},
            
            Has solicitado recuperar tu contraseña en " . SITE_NAME . ".
            
            Para establecer una nueva contraseña, haz clic en el siguiente enlace:
            $enlace
            
            Este enlace expirará en 1 hora.
            
            Si no solicitaste este cambio, ignora este mensaje.
            
            Saludos,
            Equipo " . SITE_NAME . "
        ";
        
        // Usar PHPMailer para enviar el email
        $notificacion = new Notificacion();
        $notificacion->enviarEmail($user['email'], $asunto, $mensaje);
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/$view.php";
    }
}
?>