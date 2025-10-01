<?php
class UsuarioController {
    private $auth;
    private $db;
    private $usuarioModel;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Database();
        $this->usuarioModel = new Usuario();
        $this->checkAccess();
    }
    
    private function checkAccess() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login');
            exit;
        }
        
        // Solo administradores pueden acceder
        if ($this->auth->getUser()['rol_nombre'] !== 'Administrador') {
            http_response_code(403);
            $this->renderView('errors/403');
            exit;
        }
    }
    
    public function listar() {
        $filtros = $_GET ?? [];
        $usuarios = $this->usuarioModel->listar($filtros);
        $roles = $this->getRoles();
        $areas = $this->getAreas();
        
        $data = [
            'pageTitle' => 'Gestión de Usuarios',
            'currentPage' => 'usuarios',
            'user' => $this->auth->getUser(),
            'usuarios' => $usuarios,
            'roles' => $roles,
            'areas' => $areas,
            'filtros' => $filtros
        ];
        
        $this->renderView('usuarios/listar', $data);
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = $this->validarDatosUsuario($_POST);
                $usuarioId = $this->usuarioModel->crear($data);
                
                $_SESSION['success'] = "Usuario creado exitosamente";
                header('Location: ' . SITE_URL . '/usuarios');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $roles = $this->getRoles();
        $areas = $this->getAreas();
        
        $data = [
            'pageTitle' => 'Crear Nuevo Usuario',
            'currentPage' => 'usuarios',
            'user' => $this->auth->getUser(),
            'roles' => $roles,
            'areas' => $areas,
            'error' => $error ?? null
        ];
        
        $this->renderView('usuarios/crear', $data);
    }
    
    public function editar($id) {
        $usuario = $this->usuarioModel->obtenerPorId($id);
        
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            header('Location: ' . SITE_URL . '/usuarios');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = $this->validarDatosUsuario($_POST, false);
                $this->usuarioModel->actualizar($id, $data);
                
                $_SESSION['success'] = "Usuario actualizado exitosamente";
                header('Location: ' . SITE_URL . '/usuarios');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $roles = $this->getRoles();
        $areas = $this->getAreas();
        
        $data = [
            'pageTitle' => 'Editar Usuario: ' . $usuario['nombre'],
            'currentPage' => 'usuarios',
            'user' => $this->auth->getUser(),
            'usuario' => $usuario,
            'roles' => $roles,
            'areas' => $areas,
            'error' => $error ?? null
        ];
        
        $this->renderView('usuarios/editar', $data);
    }
    
    public function permisos($id) {
        $usuario = $this->usuarioModel->obtenerPorId($id);
        
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            header('Location: ' . SITE_URL . '/usuarios');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->procesarPermisos($id, $_POST);
                $_SESSION['success'] = "Permisos actualizados exitosamente";
                header('Location: ' . SITE_URL . '/usuarios');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $permisosActuales = $this->usuarioModel->obtenerPermisosGranulares($id);
        $procesos = $this->getProcesosConActividades();
        
        $data = [
            'pageTitle' => 'Gestión de Permisos: ' . $usuario['nombre'],
            'currentPage' => 'usuarios',
            'user' => $this->auth->getUser(),
            'usuario' => $usuario,
            'permisosActuales' => $permisosActuales,
            'procesos' => $procesos,
            'error' => $error ?? null
        ];
        
        $this->renderView('usuarios/permisos', $data);
    }
    
    private function validarDatosUsuario($postData, $esNuevo = true) {
        $required = ['nombre', 'email', 'rol_id', 'area_id'];
        
        foreach ($required as $field) {
            if (empty($postData[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
        
        $data = [
            'nombre' => trim($postData['nombre']),
            'email' => filter_var(trim($postData['email']), FILTER_SANITIZE_EMAIL),
            'rol_id' => intval($postData['rol_id']),
            'area_id' => intval($postData['area_id']),
            'estado' => $postData['estado'] ?? 'activo'
        ];
        
        if ($esNuevo) {
            if (empty($postData['password'])) {
                throw new Exception("La contraseña es requerida");
            }
            $data['password'] = $postData['password'];
        }
        
        // Validar email único
        if (!$this->validarEmailUnico($data['email'], $esNuevo ? null : $postData['id'])) {
            throw new Exception("El email ya está registrado");
        }
        
        return $data;
    }
    
    private function validarEmailUnico($email, $excluirId = null) {
        $pdo = $this->db->getConnection();
        
        if ($excluirId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excluirId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
        }
        
        return $stmt->fetchColumn() === 0;
    }
    
    private function procesarPermisos($usuarioId, $postData) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Revocar todos los permisos existentes
            $stmt = $pdo->prepare("
                UPDATE permisos_granulares 
                SET estado = 'revocado', revocado_en = NOW() 
                WHERE usuario_id = ?
            ");
            $stmt->execute([$usuarioId]);
            
            // Asignar nuevos permisos
            if (!empty($postData['permisos']) && is_array($postData['permisos'])) {
                foreach ($postData['permisos'] as $permiso) {
                    list($procesoId, $actividadId) = explode('_', $permiso);
                    
                    $this->usuarioModel->asignarPermiso(
                        $usuarioId, 
                        $procesoId, 
                        $actividadId,
                        !empty($postData['fecha_expiracion']) ? $postData['fecha_expiracion'] : null
                    );
                }
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    private function getRoles() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT * FROM roles ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getAreas() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT * FROM areas WHERE estado = 'activo' ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getProcesosConActividades() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT p.*, tp.nombre as tipo_proceso_nombre,
                   (SELECT COUNT(*) FROM actividades a WHERE a.proceso_id = p.id AND a.estado = 'activo') as total_actividades
            FROM procesos p
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE p.estado = 'activo'
            ORDER BY tp.nombre, p.nombre
        ");
        $procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener actividades para cada proceso
        foreach ($procesos as &$proceso) {
            $stmt = $pdo->prepare("
                SELECT * FROM actividades 
                WHERE proceso_id = ? AND estado = 'activo' 
                ORDER BY orden
            ");
            $stmt->execute([$proceso['id']]);
            $proceso['actividades'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $procesos;
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/$view.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>