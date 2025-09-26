<?php
class AreaController {
    private $areaModel;
    
    public function __construct() {
        AuthHelper::requireRole('admin');
        $this->areaModel = new Area();
    }
    
    public function index() {
        $areas = $this->areaModel->obtenerTodas();
        
        $datos = [
            'areas' => $areas
        ];
        
        require_once 'app/views/areas/listar.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre' => filter_var($_POST['nombre'], FILTER_SANITIZE_STRING),
                'descripcion' => filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING)
            ];
            
            if (empty($datos['nombre'])) {
                $_SESSION['error'] = 'El nombre del área es obligatorio';
            } else {
                if ($this->areaModel->crear($datos)) {
                    $this->registrarLog('CREAR_AREA', "Área {$datos['nombre']} creada");
                    $_SESSION['success'] = 'Área creada exitosamente';
                    header('Location: ' . BASE_URL . 'areas');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error al crear el área';
                }
            }
        }
        
        require_once 'app/views/areas/crear.php';
    }
    
    public function editar($id) {
        $area = $this->areaModel->obtenerPorId($id);
        if (!$area) {
            require_once 'app/views/errors/404.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre' => filter_var($_POST['nombre'], FILTER_SANITIZE_STRING),
                'descripcion' => filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING),
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            
            if ($this->areaModel->actualizar($id, $datos)) {
                $this->registrarLog('EDITAR_AREA', "Área {$id} actualizada");
                $_SESSION['success'] = 'Área actualizada exitosamente';
                header('Location: ' . BASE_URL . 'areas');
                exit();
            } else {
                $_SESSION['error'] = 'Error al actualizar el área';
            }
        }
        
        $datos = [
            'area' => $area
        ];
        
        require_once 'app/views/areas/editar.php';
    }
    
    private function registrarLog($accion, $descripcion) {
        $usuarioModel = new Usuario();
        $usuarioModel->registrarLog($_SESSION['user_id'], $accion, $descripcion);
    }
}
?>