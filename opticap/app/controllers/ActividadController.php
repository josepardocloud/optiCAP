<?php
class ActividadController {
    private $actividadModel;
    
    public function __construct() {
        AuthHelper::requireRole('admin');
        $this->actividadModel = new Actividad();
    }
    
    public function index() {
        $actividades = $this->actividadModel->obtenerTodas();
        
        $datos = [
            'actividades' => $actividades
        ];
        
        require_once 'app/views/actividades/listar.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre' => filter_var($_POST['nombre'], FILTER_SANITIZE_STRING),
                'descripcion' => filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING),
                'tiempo_limite' => (int)$_POST['tiempo_limite'],
                'orden' => (int)$_POST['orden']
            ];
            
            if (empty($datos['nombre']) || $datos['tiempo_limite'] <= 0) {
                $_SESSION['error'] = 'Todos los campos son obligatorios y el tiempo debe ser mayor a 0';
            } else {
                if ($this->actividadModel->crear($datos)) {
                    $this->registrarLog('CREAR_ACTIVIDAD', "Actividad {$datos['nombre']} creada");
                    $_SESSION['success'] = 'Actividad creada exitosamente';
                    header('Location: ' . BASE_URL . 'actividades');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error al crear la actividad';
                }
            }
        }
        
        // Obtener el siguiente orden disponible
        $siguienteOrden = $this->actividadModel->obtenerMaximoOrden() + 1;
        
        $datos = [
            'siguienteOrden' => $siguienteOrden
        ];
        
        require_once 'app/views/actividades/crear.php';
    }
    
    public function editar($id) {
        $actividad = $this->actividadModel->obtenerPorId($id);
        if (!$actividad) {
            require_once 'app/views/errors/404.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre' => filter_var($_POST['nombre'], FILTER_SANITIZE_STRING),
                'descripcion' => filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING),
                'tiempo_limite' => (int)$_POST['tiempo_limite'],
                'orden' => (int)$_POST['orden'],
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            
            if ($this->actividadModel->actualizar($id, $datos)) {
                $this->registrarLog('EDITAR_ACTIVIDAD', "Actividad {$id} actualizada");
                $_SESSION['success'] = 'Actividad actualizada exitosamente';
                header('Location: ' . BASE_URL . 'actividades');
                exit();
            } else {
                $_SESSION['error'] = 'Error al actualizar la actividad';
            }
        }
        
        $datos = [
            'actividad' => $actividad
        ];
        
        require_once 'app/views/actividades/editar.php';
    }
    
    public function reordenar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ordenes = $_POST['orden'] ?? [];
            
            foreach ($ordenes as $actividadId => $nuevoOrden) {
                $this->actividadModel->actualizarOrden($actividadId, $nuevoOrden);
            }
            
            $this->registrarLog('REORDENAR_ACTIVIDADES', 'Actividades reordenadas');
            $_SESSION['success'] = 'Órden de actividades actualizado exitosamente';
            header('Location: ' . BASE_URL . 'actividades');
            exit();
        }
    }
    
    private function registrarLog($accion, $descripcion) {
        $usuarioModel = new Usuario();
        $usuarioModel->registrarLog($_SESSION['user_id'], $accion, $descripcion);
    }
}
?>