<?php
class RequerimientoController {
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
    
    public function listar() {
        $user = $this->auth->getUser();
        $filtros = $_GET ?? [];
        
        // Aplicar filtros según rol
        if ($user['rol_nombre'] === 'Usuario') {
            $filtros['area_id'] = $user['area_id'];
        }
        
        $requerimientos = $this->requerimientoModel->listar($filtros);
        $tiposProceso = $this->getTiposProceso();
        $areas = $this->getAreas();
        
        $data = [
            'pageTitle' => 'Gestión de Requerimientos',
            'currentPage' => 'requerimientos',
            'user' => $user,
            'requerimientos' => $requerimientos,
            'tiposProceso' => $tiposProceso,
            'areas' => $areas,
            'filtros' => $filtros
        ];
        
        $this->renderView('requerimientos/listar', $data);
    }
    
    public function crear() {
        $user = $this->auth->getUser();
        
        // Verificar permisos para crear requerimientos
        if (!in_array($user['rol_nombre'], ['Super Usuario', 'Usuario'])) {
            $_SESSION['error'] = 'No tiene permisos para crear requerimientos';
            header('Location: ' . SITE_URL . '/requerimientos');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = $this->validarDatosRequerimiento($_POST);
                
                // Asignar área automáticamente según rol
                if ($user['rol_nombre'] === 'Usuario') {
                    $data['area_id'] = $user['area_id'];
                }
                
                $data['usuario_solicitante_id'] = $user['id'];
                
                $requerimientoId = $this->requerimientoModel->crear($data);
                
                $_SESSION['success'] = "Requerimiento creado exitosamente con código: " . 
                    $this->requerimientoModel->obtenerCodigo($requerimientoId);
                
                header('Location: ' . SITE_URL . '/requerimientos/detalle/' . $requerimientoId);
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $tiposProceso = $this->getTiposProceso();
        $areas = $this->getAreas();
        
        $data = [
            'pageTitle' => 'Crear Nuevo Requerimiento',
            'currentPage' => 'requerimientos',
            'user' => $user,
            'tiposProceso' => $tiposProceso,
            'areas' => $areas,
            'error' => $error ?? null
        ];
        
        $this->renderView('requerimientos/crear', $data);
    }
    
    public function detalle($id) {
        $user = $this->auth->getUser();
        
        $requerimiento = $this->requerimientoModel->obtenerPorId($id);
        
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
        
        $actividades = $this->requerimientoModel->obtenerActividades($id);
        $evidencias = $this->requerimientoModel->obtenerEvidencias($id);
        $historial = $this->requerimientoModel->obtenerHistorial($id);
        
        $data = [
            'pageTitle' => 'Detalle de Requerimiento: ' . $requerimiento['codigo'],
            'currentPage' => 'requerimientos',
            'user' => $user,
            'requerimiento' => $requerimiento,
            'actividades' => $actividades,
            'evidencias' => $evidencias,
            'historial' => $historial
        ];
        
        $this->renderView('requerimientos/detalle', $data);
    }
    
    public function imprimir($id) {
        $user = $this->auth->getUser();
        
        $requerimiento = $this->requerimientoModel->obtenerPorId($id);
        
        if (!$requerimiento) {
            $_SESSION['error'] = 'Requerimiento no encontrado';
            header('Location: ' . SITE_URL . '/requerimientos');
            exit;
        }
        
        // Verificar permisos de visualización
        if (!$this->tieneAccesoRequerimiento($requerimiento, $user)) {
            $_SESSION['error'] = 'No tiene permisos para imprimir este requerimiento';
            header('Location: ' . SITE_URL . '/requerimientos');
            exit;
        }
        
        $actividades = $this->requerimientoModel->obtenerActividades($id);
        $evidencias = $this->requerimientoModel->obtenerEvidencias($id);
        
        $data = [
            'pageTitle' => 'Imprimir Requerimiento: ' . $requerimiento['codigo'],
            'user' => $user,
            'requerimiento' => $requerimiento,
            'actividades' => $actividades,
            'evidencias' => $evidencias
        ];
        
        $this->renderView('requerimientos/imprimir', $data, false);
    }
    
    private function validarDatosRequerimiento($postData) {
        $required = ['tipo_proceso_id', 'motivo'];
        
        foreach ($required as $field) {
            if (empty($postData[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
        
        return [
            'tipo_proceso_id' => intval($postData['tipo_proceso_id']),
            'area_id' => intval($postData['area_id']),
            'motivo' => trim($postData['motivo'])
        ];
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
    
    private function getTiposProceso() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT * FROM tipos_proceso WHERE estado = 'activo'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getAreas() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT * FROM areas WHERE estado = 'activo'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function renderView($view, $data = [], $withLayout = true) {
        extract($data);
        
        if ($withLayout) {
            require_once APP_PATH . "/views/layouts/header.php";
        }
        
        require_once APP_PATH . "/views/$view.php";
        
        if ($withLayout) {
            require_once APP_PATH . "/views/layouts/footer.php";
        }
    }
}
?>