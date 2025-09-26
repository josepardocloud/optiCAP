<?php
class UsuarioController {
    private $usuarioModel;
    private $areaModel;
    private $permisoModel;
    
    public function __construct() {
        AuthHelper::checkAuth();
        $this->usuarioModel = new Usuario();
        $this->areaModel = new Area();
        $this->permisoModel = new Permiso();
    }
    
    public function index() {
        AuthHelper::requireRole('admin');
        
        $usuarios = $this->usuarioModel->obtenerTodos();
        $areas = $this->areaModel->obtenerTodas();
        
        $datos = [
            'usuarios' => $usuarios,
            'areas' => $areas
        ];
        
        require_once 'app/views/usuarios/listar.php';
    }
    
    public function crear() {
        AuthHelper::requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre' => filter_var($_POST['nombre'], FILTER_SANITIZE_STRING),
                'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
                'password' => AuthHelper::hashPassword('Temp123'), // Contraseña temporal
                'id_area' => $_POST['id_area'],
                'rol' => $_POST['rol']
            ];
            
            // Validar email único
            if ($this->usuarioModel->obtenerPorEmail($datos['email'])) {
                $_SESSION['error'] = 'El email ya está registrado';
            } else {
                if ($this->usuarioModel->crear($datos)) {
                    $this->usuarioModel->registrarLog($_SESSION['user_id'], 'CREAR_USUARIO', "Usuario {$datos['email']} creado");
                    $_SESSION['success'] = 'Usuario creado exitosamente';
                    header('Location: ' . BASE_URL . 'usuarios');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error al crear el usuario';
                }
            }
        }
        
        $datos = [
            'areas' => $this->areaModel->obtenerTodas()
        ];
        
        require_once 'app/views/usuarios/crear.php';
    }
    
    public function editar($id) {
        AuthHelper::requireRole('admin');
        
        $usuario = $this->usuarioModel->obtenerPorId($id);
        if (!$usuario) {
            require_once 'app/views/errors/404.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre' => filter_var($_POST['nombre'], FILTER_SANITIZE_STRING),
                'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
                'id_area' => $_POST['id_area'],
                'rol' => $_POST['rol'],
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            
            // Validar email único (excluyendo el usuario actual)
            $usuarioExistente = $this->usuarioModel->obtenerPorEmail($datos['email']);
            if ($usuarioExistente && $usuarioExistente['id'] != $id) {
                $_SESSION['error'] = 'El email ya está registrado';
            } else {
                if ($this->usuarioModel->actualizar($id, $datos)) {
                    $this->usuarioModel->registrarLog($_SESSION['user_id'], 'EDITAR_USUARIO', "Usuario {$id} actualizado");
                    $_SESSION['success'] = 'Usuario actualizado exitosamente';
                    header('Location: ' . BASE_URL . 'usuarios');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error al actualizar el usuario';
                }
            }
        }
        
        $datos = [
            'usuario' => $usuario,
            'areas' => $this->areaModel->obtenerTodas(),
            'permisos' => $this->permisoModel->obtenerPorUsuario($id)
        ];
        
        require_once 'app/views/usuarios/editar.php';
    }
    
    public function perfil() {
        $usuario = $this->usuarioModel->obtenerPorId($_SESSION['user_id']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre' => filter_var($_POST['nombre'], FILTER_SANITIZE_STRING),
                'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)
            ];
            
            // Validar email único
            $usuarioExistente = $this->usuarioModel->obtenerPorEmail($datos['email']);
            if ($usuarioExistente && $usuarioExistente['id'] != $_SESSION['user_id']) {
                $_SESSION['error'] = 'El email ya está registrado';
            } else {
                if ($this->usuarioModel->actualizar($_SESSION['user_id'], $datos)) {
                    $_SESSION['user_nombre'] = $datos['nombre'];
                    $_SESSION['user_email'] = $datos['email'];
                    $_SESSION['success'] = 'Perfil actualizado exitosamente';
                } else {
                    $_SESSION['error'] = 'Error al actualizar el perfil';
                }
            }
        }
        
        $datos = [
            'usuario' => $usuario
        ];
        
        require_once 'app/views/usuarios/perfil.php';
    }
    
    public function gestionarPermisos($usuarioId) {
        AuthHelper::requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actividades = $_POST['actividades'] ?? [];
            
            // Eliminar permisos existentes
            $this->permisoModel->eliminarPorUsuario($usuarioId);
            
            // Agregar nuevos permisos
            foreach ($actividades as $actividadId => $permisos) {
                $datos = [
                    'id_usuario' => $usuarioId,
                    'id_actividad' => $actividadId,
                    'puede_aprobar' => isset($permisos['aprobar']) ? 1 : 0,
                    'puede_modificar' => isset($permisos['modificar']) ? 1 : 0
                ];
                $this->permisoModel->crear($datos);
            }
            
            $this->usuarioModel->registrarLog($_SESSION['user_id'], 'GESTIONAR_PERMISOS', "Permisos del usuario {$usuarioId} actualizados");
            $_SESSION['success'] = 'Permisos actualizados exitosamente';
            header('Location: ' . BASE_URL . 'usuarios/editar/' . $usuarioId);
            exit();
        }
    }
}
?>