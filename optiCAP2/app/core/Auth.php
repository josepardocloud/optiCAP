<?php
class Auth {
    private $db;
    private $session;
    
    public function __construct() {
        $this->db = new Database();
        $this->session = new Session();
    }
    
    public function login($email, $password) {
        // Verificar intentos fallidos
        if ($this->isAccountLocked($email)) {
            throw new Exception('Cuenta bloqueada. Contacte al administrador.');
        }
        
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre, a.nombre as area_nombre 
            FROM usuarios u 
            LEFT JOIN roles r ON u.rol_id = r.id 
            LEFT JOIN areas a ON u.area_id = a.id 
            WHERE u.email = ? AND u.estado = 'activo'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso - resetear intentos
            $this->resetFailedAttempts($email);
            
            // Guardar en sesión
            $this->session->set('user', [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol_id' => $user['rol_id'],
                'rol_nombre' => $user['rol_nombre'],
                'area_id' => $user['area_id'],
                'area_nombre' => $user['area_nombre'],
                'login_time' => time()
            ]);
            
            // Registrar login exitoso
            $this->logAccess($user['id'], 'login_exitoso');
            
            return true;
        } else {
            // Login fallido
            $this->recordFailedAttempt($email);
            $this->logAccess($user ? $user['id'] : null, 'login_fallido', $email);
            return false;
        }
    }
    
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logAccess($this->getUserId(), 'logout');
        }
        $this->session->destroy();
    }
    
    public function isLoggedIn() {
        return $this->session->has('user');
    }
    
    public function getUser() {
        return $this->session->get('user');
    }
    
    public function getUserId() {
        $user = $this->getUser();
        return $user['id'] ?? null;
    }
    
    public function getUserRole() {
        $user = $this->getUser();
        return $user['rol_id'] ?? null;
    }
    
    public function getUserArea() {
        $user = $this->getUser();
        return $user['area_id'] ?? null;
    }
    
    public function hasPermission($permission) {
        // Implementar lógica de permisos granulares
        $user = $this->getUser();
        if (!$user) return false;
        
        // Verificar permisos según rol
        return $this->checkRolePermission($user['rol_id'], $permission);
    }
    
    private function checkRolePermission($roleId, $permission) {
        // Consultar permisos en base de datos
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM permisos_roles 
            WHERE rol_id = ? AND permiso = ?
        ");
        $stmt->execute([$roleId, $permission]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function isAccountLocked($email) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT intentos_fallidos, ultimo_intento 
            FROM usuarios 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['intentos_fallidos'] >= 4) {
            // Verificar si ha pasado más de 30 minutos
            $lockTime = strtotime($user['ultimo_intento']);
            return (time() - $lockTime) < 1800; // 30 minutos
        }
        
        return false;
    }
    
    private function recordFailedAttempt($email) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = intentos_fallidos + 1, 
                ultimo_intento = NOW() 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        // Notificar administrador si es el 4to intento
        $this->checkAndNotifyLock($email);
    }
    
    private function resetFailedAttempts($email) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = 0, ultimo_intento = NULL 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
    }
    
    private function checkAndNotifyLock($email) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT intentos_fallidos FROM usuarios WHERE email = ?
        ");
        $stmt->execute([$email]);
        $attempts = $stmt->fetchColumn();
        
        if ($attempts >= 4) {
            // Notificar al administrador
            $this->notifyAdminAccountLocked($email);
        }
    }
    
    private function notifyAdminAccountLocked($email) {
        // Implementar notificación por email
        $notificacion = new Notificacion();
        $notificacion->enviarNotificacionAdmin(
            "Cuenta bloqueada - $email",
            "La cuenta $email ha sido bloqueada por 4 intentos fallidos de login."
        );
    }
    
    private function logAccess($userId, $action, $email = null) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_accesos (usuario_id, email, accion, ip, user_agent, fecha) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $email,
            $action,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    }
}
?>