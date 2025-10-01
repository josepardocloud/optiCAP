<?php
class ActividadController {
    private $auth;
    private $db;
    private $requerimientoModel;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Database();
        $this->requerimientoModel = new Requerimiento();
        $this->checkAccess();
    }
    
    private function checkAccess() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login');
            exit;
        }
    }
    
    public function editar($requerimientoActividadId) {
        $user = $this->auth->getUser();
        
        // Obtener información de la actividad
        $actividad = $this->obtenerActividadCompleta($requerimientoActividadId);
        
        if (!$actividad) {
            $_SESSION['error'] = 'Actividad no encontrada';
            header('Location: ' . SITE_URL . '/requerimientos');
            exit;
        }
        
        // Verificar permisos
        if (!$this->tienePermisoEditarActividad($actividad, $user)) {
            $_SESSION['error'] = 'No tiene permisos para editar esta actividad';
            header('Location: ' . SITE_URL . '/requerimientos/detalle/' . $actividad['requerimiento_id']);
            exit;
        }
        
        // Verificar secuencia
        if (!$this->validarSecuencia($actividad)) {
            $_SESSION['error'] = 'Debe completar la actividad anterior antes de editar esta';
            header('Location: ' . SITE_URL . '/requerimientos/detalle/' . $actividad['requerimiento_id']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = $this->procesarDatosActividad($_POST, $actividad);
                
                $this->requerimientoModel->actualizarActividad(
                    $actividad['requerimiento_id'], 
                    $actividad['numero_paso'], 
                    $data
                );
                
                // Procesar evidencias si se enviaron
                if (!empty($_FILES['evidencias'])) {
                    $this->procesarEvidencias($requerimientoActividadId, $_FILES['evidencias']);
                }
                
                $_SESSION['success'] = 'Actividad actualizada correctamente';
                header('Location: ' . SITE_URL . '/requerimientos/detalle/' . $actividad['requerimiento_id']);
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $requerimiento = $this->requerimientoModel->obtenerPorId($actividad['requerimiento_id']);
        $evidencias = $this->obtenerEvidenciasActividad($requerimientoActividadId);
        
        $data = [
            'pageTitle' => 'Editar Actividad: ' . $actividad['nombre'],
            'currentPage' => 'requerimientos',
            'user' => $user,
            'actividad' => $actividad,
            'requerimiento' => $requerimiento,
            'evidencias' => $evidencias,
            'error' => $error ?? null
        ];
        
        $this->renderView('actividades/editar', $data);
    }
    
    public function timeline($requerimientoId) {
        $user = $this->auth->getUser();
        
        $requerimiento = $this->requerimientoModel->obtenerPorId($requerimientoId);
        
        if (!$requerimiento) {
            $_SESSION['error'] = 'Requerimiento no encontrado';
            header('Location: ' . SITE_URL . '/requerimientos');
            exit;
        }
        
        // Verificar permisos de visualización
        if (!$this->tieneAccesoRequerimiento($requerimiento, $user)) {
            $_SESSION['error'] = 'No tiene permisos para ver este requerimiento';
            header('Location: ' . SITE_URL . '/requerimientos');
            exit;
        }
        
        $actividades = $this->requerimientoModel->obtenerActividades($requerimientoId);
        
        $data = [
            'pageTitle' => 'Línea de Tiempo - ' . $requerimiento['codigo'],
            'currentPage' => 'requerimientos',
            'user' => $user,
            'requerimiento' => $requerimiento,
            'actividades' => $actividades
        ];
        
        $this->renderView('actividades/timeline', $data);
    }
    
    private function obtenerActividadCompleta($requerimientoActividadId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT ra.*, a.numero_paso, a.nombre, a.descripcion, a.requisitos_obligatorios,
                   r.id as requerimiento_id, r.codigo as requerimiento_codigo,
                   r.estado_general as requerimiento_estado,
                   u.nombre as usuario_asignado_nombre
            FROM requerimiento_actividades ra
            JOIN actividades a ON ra.actividad_id = a.id
            JOIN requerimientos r ON ra.requerimiento_id = r.id
            LEFT JOIN usuarios u ON ra.usuario_asignado_id = u.id
            WHERE ra.id = ?
        ");
        $stmt->execute([$requerimientoActividadId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function tienePermisoEditarActividad($actividad, $user) {
        // Administradores no pueden editar actividades
        if ($user['rol_nombre'] === 'Administrador') {
            return false;
        }
        
        // Supervisores solo lectura
        if ($user['rol_nombre'] === 'Supervisor') {
            return false;
        }
        
        // Usuarios normales solo pueden editar actividades de su área
        if ($user['rol_nombre'] === 'Usuario') {
            $requerimiento = $this->requerimientoModel->obtenerPorId($actividad['requerimiento_id']);
            return $requerimiento['area_id'] == $user['area_id'];
        }
        
        // Super Usuarios necesitan permisos granulares
        if ($user['rol_nombre'] === 'Super Usuario') {
            return $this->tienePermisoGranular($user['id'], $actividad['actividad_id']);
        }
        
        return false;
    }
    
    private function tienePermisoGranular($usuarioId, $actividadId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM permisos_granulares 
            WHERE usuario_id = ? AND actividad_id = ? AND estado = 'activo'
        ");
        $stmt->execute([$usuarioId, $actividadId]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function validarSecuencia($actividad) {
        // La actividad 01 siempre está disponible
        if ($actividad['numero_paso'] == 1) {
            return true;
        }
        
        // Verificar si la actividad anterior está finalizada
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT ra.estado 
            FROM requerimiento_actividades ra
            JOIN actividades a ON ra.actividad_id = a.id
            WHERE ra.requerimiento_id = ? AND a.numero_paso = ?
        ");
        $stmt->execute([$actividad['requerimiento_id'], $actividad['numero_paso'] - 1]);
        $estadoAnterior = $stmt->fetchColumn();
        
        return $estadoAnterior === 'finalizado' || $estadoAnterior === 'no_aplica';
    }
    
    private function procesarDatosActividad($postData, $actividad) {
        $data = [
            'estado' => $postData['estado'],
            'observaciones' => trim($postData['observaciones'] ?? '')
        ];
        
        // Procesar requisitos obligatorios
        $requisitos = json_decode($actividad['requisitos_obligatorios'], true);
        $requisitosCumplidos = [];
        
        if (!empty($requisitos)) {
            foreach ($requisitos as $requisito) {
                $requisitosCumplidos[$requisito] = isset($postData['requisitos'][$requisito]);
            }
        }
        
        $data['requisitos_cumplidos'] = $requisitosCumplidos;
        
        return $data;
    }
    
    private function procesarEvidencias($requerimientoActividadId, $archivos) {
        $uploader = new Upload();
        
        foreach ($archivos['name'] as $key => $name) {
            if ($archivos['error'][$key] === UPLOAD_ERR_OK) {
                $fileData = [
                    'name' => $archivos['name'][$key],
                    'type' => $archivos['type'][$key],
                    'tmp_name' => $archivos['tmp_name'][$key],
                    'error' => $archivos['error'][$key],
                    'size' => $archivos['size'][$key]
                ];
                
                $uploader->subirEvidencia($requerimientoActividadId, $fileData);
            }
        }
    }
    
    private function obtenerEvidenciasActividad($requerimientoActividadId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM evidencias 
            WHERE requerimiento_actividad_id = ? 
            ORDER BY creado_en DESC
        ");
        $stmt->execute([$requerimientoActividadId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function tieneAccesoRequerimiento($requerimiento, $user) {
        switch ($user['rol_nombre']) {
            case 'Administrador':
            case 'Supervisor':
            case 'Super Usuario':
                return true;
            case 'Usuario':
                return $requerimiento['area_id'] == $user['area_id'];
            default:
                return false;
        }
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/$view.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>