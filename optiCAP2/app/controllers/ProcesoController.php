<?php
class ProcesoController {
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Database();
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
        $procesos = $this->obtenerProcesosCompletos();
        
        $data = [
            'pageTitle' => 'Gestión de Procesos',
            'currentPage' => 'procesos',
            'user' => $this->auth->getUser(),
            'procesos' => $procesos
        ];
        
        $this->renderView('procesos/listar', $data);
    }
    
    public function editar($id) {
        $proceso = $this->obtenerProcesoPorId($id);
        
        if (!$proceso) {
            $_SESSION['error'] = 'Proceso no encontrado';
            header('Location: ' . SITE_URL . '/procesos');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->actualizarProceso($id, $_POST);
                $_SESSION['success'] = "Proceso actualizado correctamente";
                header('Location: ' . SITE_URL . '/procesos');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $tiposProceso = $this->obtenerTiposProceso();
        
        $data = [
            'pageTitle' => 'Editar Proceso: ' . $proceso['nombre'],
            'currentPage' => 'procesos',
            'user' => $this->auth->getUser(),
            'proceso' => $proceso,
            'tiposProceso' => $tiposProceso,
            'error' => $error ?? null
        ];
        
        $this->renderView('procesos/editar', $data);
    }
    
    public function actividades($id) {
        $proceso = $this->obtenerProcesoPorId($id);
        
        if (!$proceso) {
            $_SESSION['error'] = 'Proceso no encontrado';
            header('Location: ' . SITE_URL . '/procesos');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->actualizarActividades($id, $_POST);
                $_SESSION['success'] = "Actividades actualizadas correctamente";
                header('Location: ' . SITE_URL . '/procesos/actividades/' . $id);
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $actividades = $this->obtenerActividadesProceso($id);
        
        $data = [
            'pageTitle' => 'Configurar Actividades: ' . $proceso['nombre'],
            'currentPage' => 'procesos',
            'user' => $this->auth->getUser(),
            'proceso' => $proceso,
            'actividades' => $actividades,
            'error' => $error ?? null
        ];
        
        $this->renderView('procesos/actividades', $data);
    }
    
    private function obtenerProcesosCompletos() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT p.*, tp.nombre as tipo_proceso_nombre, tp.codigo as tipo_proceso_codigo,
                   (SELECT COUNT(*) FROM actividades a WHERE a.proceso_id = p.id AND a.estado = 'activo') as total_actividades
            FROM procesos p
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE p.estado = 'activo'
            ORDER BY tp.nombre, p.nombre
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenerProcesoPorId($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, tp.nombre as tipo_proceso_nombre, tp.codigo as tipo_proceso_codigo
            FROM procesos p
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function obtenerTiposProceso() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT * FROM tipos_proceso WHERE estado = 'activo'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function actualizarProceso($id, $data) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE procesos 
            SET nombre = ?, descripcion = ?, duracion_estimada = ?, estado = ?, actualizado_en = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([
            trim($data['nombre']),
            trim($data['descripcion']),
            intval($data['duracion_estimada']),
            $data['estado'],
            $id
        ]);
    }
    
    private function obtenerActividadesProceso($procesoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM actividades 
            WHERE proceso_id = ? 
            ORDER BY orden
        ");
        $stmt->execute([$procesoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function actualizarActividades($procesoId, $data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            if (!empty($data['actividades'])) {
                $stmt = $pdo->prepare("
                    UPDATE actividades 
                    SET nombre = ?, descripcion = ?, requisitos_obligatorios = ?, duracion_estimada = ?, orden = ? 
                    WHERE id = ? AND proceso_id = ?
                ");
                
                foreach ($data['actividades'] as $actividadId => $actividadData) {
                    $requisitos = isset($actividadData['requisitos']) ? 
                        json_encode($actividadData['requisitos']) : '[]';
                    
                    $stmt->execute([
                        trim($actividadData['nombre']),
                        trim($actividadData['descripcion']),
                        $requisitos,
                        intval($actividadData['duracion_estimada']),
                        intval($actividadData['orden']),
                        $actividadId,
                        $procesoId
                    ]);
                }
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
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