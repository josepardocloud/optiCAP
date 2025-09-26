<?php
class RequerimientoController {
    private $requerimientoModel;
    private $areaModel;
    private $actividadModel;
    private $usuarioModel;
    
    public function __construct() {
        AuthHelper::checkAuth();
        $this->requerimientoModel = new Requerimiento();
        $this->areaModel = new Area();
        $this->actividadModel = new Actividad();
        $this->usuarioModel = new Usuario();
    }
    
    public function index() {
        $usuarioId = $_SESSION['user_id'];
        $rol = $_SESSION['user_role'];
        $areaId = $_SESSION['user_area'];
        
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        
        $requerimientos = $this->requerimientoModel->obtenerPorUsuarioArea($usuarioId, $areaId, $rol, $offset, $porPagina);
        $totalRequerimientos = $this->requerimientoModel->contarPorUsuarioArea($usuarioId, $areaId, $rol);
        $totalPaginas = ceil($totalRequerimientos / $porPagina);
        
        $datos = [
            'requerimientos' => $requerimientos,
            'paginaActual' => $pagina,
            'totalPaginas' => $totalPaginas,
            'areas' => $this->areaModel->obtenerTodas()
        ];
        
        require_once 'app/views/requerimientos/listar.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'titulo' => filter_var($_POST['titulo'], FILTER_SANITIZE_STRING),
                'descripcion' => filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING),
                'id_area_solicitante' => $_SESSION['user_area'],
                'id_usuario_solicitante' => $_SESSION['user_id']
            ];
            
            if (empty($datos['titulo']) || empty($datos['descripcion'])) {
                $_SESSION['error'] = 'Todos los campos son obligatorios';
            } else {
                $requerimientoId = $this->requerimientoModel->crear($datos);
                if ($requerimientoId) {
                    $this->usuarioModel->registrarLog($_SESSION['user_id'], 'CREAR_REQUERIMIENTO', "Requerimiento {$requerimientoId} creado");
                    $_SESSION['success'] = 'Requerimiento creado exitosamente';
                    header('Location: ' . BASE_URL . 'requerimientos/detalle/' . $requerimientoId);
                    exit();
                } else {
                    $_SESSION['error'] = 'Error al crear el requerimiento';
                }
            }
        }
        
        $datos = [
            'areas' => $this->areaModel->obtenerTodas()
        ];
        
        require_once 'app/views/requerimientos/crear.php';
    }
    
    public function detalle($id) {
        $requerimiento = $this->requerimientoModel->obtenerPorId($id);
        if (!$requerimiento) {
            require_once 'app/views/errors/404.php';
            return;
        }
        
        // Verificar permisos de visualización
        if (!AuthHelper::hasRole('admin') && !AuthHelper::hasRole('supervisor') && 
            $requerimiento['id_area_solicitante'] != $_SESSION['user_area']) {
            header('Location: ' . BASE_URL . 'requerimientos');
            exit();
        }
        
        $seguimiento = $this->requerimientoModel->obtenerSeguimiento($id);
        $usuariosArea = $this->usuarioModel->obtenerPorArea($requerimiento['id_area_solicitante']);
        
        $datos = [
            'requerimiento' => $requerimiento,
            'seguimiento' => $seguimiento,
            'usuariosArea' => $usuariosArea
        ];
        
        require_once 'app/views/requerimientos/detalle.php';
    }
    
    public function editar($id) {
        $requerimiento = $this->requerimientoModel->obtenerPorId($id);
        if (!$requerimiento) {
            require_once 'app/views/errors/404.php';
            return;
        }
        
        // Verificar permisos de edición
        if ($requerimiento['id_usuario_solicitante'] != $_SESSION['user_id'] && !AuthHelper::hasRole('admin')) {
            header('Location: ' . BASE_URL . 'requerimientos/detalle/' . $id);
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'titulo' => filter_var($_POST['titulo'], FILTER_SANITIZE_STRING),
                'descripcion' => filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING)
            ];
            
            if ($this->requerimientoModel->actualizar($id, $datos)) {
                $this->usuarioModel->registrarLog($_SESSION['user_id'], 'EDITAR_REQUERIMIENTO', "Requerimiento {$id} actualizado");
                $_SESSION['success'] = 'Requerimiento actualizado exitosamente';
                header('Location: ' . BASE_URL . 'requerimientos/detalle/' . $id);
                exit();
            } else {
                $_SESSION['error'] = 'Error al actualizar el requerimiento';
            }
        }
        
        $datos = [
            'requerimiento' => $requerimiento
        ];
        
        require_once 'app/views/requerimientos/editar.php';
    }
    
    public function avanzarActividad($requerimientoId, $seguimientoId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $estado = $_POST['estado'];
            $observaciones = filter_var($_POST['observaciones'], FILTER_SANITIZE_STRING);
            
            // Subir evidencias si existen
            $evidencias = [];
            if (!empty($_FILES['evidencias']['name'][0])) {
                $uploadHelper = new UploadHelper();
                $evidencias = $uploadHelper->subirMultiplesArchivos($_FILES['evidencias'], 'evidencias');
            }
            
            if ($this->requerimientoModel->actualizarSeguimiento($seguimientoId, [
                'estado' => $estado,
                'observaciones' => $observaciones,
                'evidencias' => json_encode($evidencias),
                'fecha_fin_real' => date('Y-m-d')
            ])) {
                $this->usuarioModel->registrarLog($_SESSION['user_id'], 'AVANZAR_ACTIVIDAD', "Actividad {$seguimientoId} avanzada a {$estado}");
                $_SESSION['success'] = 'Actividad actualizada exitosamente';
            } else {
                $_SESSION['error'] = 'Error al actualizar la actividad';
            }
            
            header('Location: ' . BASE_URL . 'requerimientos/detalle/' . $requerimientoId);
            exit();
        }
    }
    
    public function asignarUsuario($seguimientoId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuarioId = $_POST['usuario_id'];
            $requerimientoId = $_POST['requerimiento_id'];
            
            if ($this->requerimientoModel->asignarUsuarioSeguimiento($seguimientoId, $usuarioId)) {
                $this->usuarioModel->registrarLog($_SESSION['user_id'], 'ASIGNAR_USUARIO', "Usuario {$usuarioId} asignado a actividad {$seguimientoId}");
                $_SESSION['success'] = 'Usuario asignado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al asignar el usuario';
            }
            
            header('Location: ' . BASE_URL . 'requerimientos/detalle/' . $requerimientoId);
            exit();
        }
    }
}
?>