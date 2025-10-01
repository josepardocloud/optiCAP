<?php
class DashboardController {
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
    }
    
    public function index() {
        $user = $this->auth->getUser();
        switch ($user['rol_nombre']) {
            case 'Administrador':
                $this->admin();
                break;
            case 'Supervisor':
                $this->supervisor();
                break;
            case 'Super Usuario':
                $this->superusuario();
                break;
            default:
                $this->usuario();
        }
    }
    
    public function admin() {
        $this->checkRole('Administrador');
        
        $pdo = $this->db->getConnection();
        
        // KPIs para administrador
        $stats = [
            'total_usuarios' => $this->getTotalUsuarios(),
            'usuarios_activos' => $this->getUsuariosActivos(),
            'requerimientos_totales' => $this->getTotalRequerimientos(),
            'requerimientos_pendientes' => $this->getRequerimientosPendientes(),
            'incidencias_pendientes' => $this->getIncidenciasPendientes(),
            'bloqueos_cuenta' => $this->getCuentasBloqueadas()
        ];
        
        // Gráficos de actividad reciente
        $actividadReciente = $this->getActividadReciente();
        $estadisticasSLA = $this->getEstadisticasSLA();
        
        $data = [
            'pageTitle' => 'Dashboard - Administrador',
            'currentPage' => 'dashboard',
            'user' => $this->auth->getUser(),
            'stats' => $stats,
            'actividadReciente' => $actividadReciente,
            'estadisticasSLA' => $estadisticasSLA
        ];
        
        $this->renderView('dashboard/admin', $data);
    }
    
    public function supervisor() {
        $this->checkRole('Supervisor');
        
        $stats = [
            'requerimientos_totales' => $this->getTotalRequerimientos(),
            'requerimientos_completados' => $this->getRequerimientosCompletados(),
            'requerimientos_vencidos' => $this->getRequerimientosVencidos(),
            'cumplimiento_sla' => $this->getCumplimientoSLAGlobal(),
            'saltos_condicionales' => $this->getSaltosCondicionales(),
            'tiempo_promedio' => $this->getTiempoPromedioProceso()
        ];
        
        $metricasAreas = $this->getMetricasPorArea();
        $tendencias = $this->getTendenciasMensuales();
        
        $data = [
            'pageTitle' => 'Dashboard - Supervisor',
            'currentPage' => 'dashboard',
            'user' => $this->auth->getUser(),
            'stats' => $stats,
            'metricasAreas' => $metricasAreas,
            'tendencias' => $tendencias
        ];
        
        $this->renderView('dashboard/supervisor', $data);
    }
    
    public function superusuario() {
        $this->checkRole('Super Usuario');
        
        $userId = $this->auth->getUserId();
        
        $stats = [
            'mis_requerimientos' => $this->getMisRequerimientos($userId),
            'actividades_pendientes' => $this->getActividadesPendientes($userId),
            'proximos_vencer' => $this->getProximosVencer($userId),
            'completados_mes' => $this->getCompletadosMes($userId)
        ];
        
        $actividadesAsignadas = $this->getActividadesAsignadas($userId);
        $requerimientosRecientes = $this->getRequerimientosRecientes();
        
        $data = [
            'pageTitle' => 'Dashboard - Super Usuario',
            'currentPage' => 'dashboard',
            'user' => $this->auth->getUser(),
            'stats' => $stats,
            'actividadesAsignadas' => $actividadesAsignadas,
            'requerimientosRecientes' => $requerimientosRecientes
        ];
        
        $this->renderView('dashboard/superusuario', $data);
    }
    
    public function usuario() {
        $userId = $this->auth->getUserId();
        $areaId = $this->auth->getUserArea();
        
        $stats = [
            'mis_requerimientos' => $this->getMisRequerimientosArea($userId, $areaId),
            'actividades_pendientes' => $this->getActividadesPendientes($userId),
            'proximos_vencer' => $this->getProximosVencer($userId),
            'completados_mes' => $this->getCompletadosMesArea($userId, $areaId)
        ];
        
        $actividadesAsignadas = $this->getActividadesAsignadas($userId);
        $requerimientosArea = $this->getRequerimientosArea($areaId);
        
        $data = [
            'pageTitle' => 'Dashboard - Usuario',
            'currentPage' => 'dashboard',
            'user' => $this->auth->getUser(),
            'stats' => $stats,
            'actividadesAsignadas' => $actividadesAsignadas,
            'requerimientosArea' => $requerimientosArea
        ];
        
        $this->renderView('dashboard/usuario', $data);
    }
    
    private function checkRole($requiredRole) {
        $user = $this->auth->getUser();
        if ($user['rol_nombre'] !== $requiredRole) {
            http_response_code(403);
            $this->renderView('errors/403');
            exit;
        }
    }
    
    // Métodos para obtener estadísticas
    private function getTotalUsuarios() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo'");
        return $stmt->fetchColumn();
    }
    
    private function getUsuariosActivos() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo' AND ultimo_intento IS NOT NULL");
        return $stmt->fetchColumn();
    }
    
    private function getTotalRequerimientos() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM requerimientos");
        return $stmt->fetchColumn();
    }
    
    private function getRequerimientosPendientes() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM requerimientos WHERE estado_general = 'pendiente'");
        return $stmt->fetchColumn();
    }
    
    private function getIncidenciasPendientes() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM incidencias WHERE estado = 'pendiente'");
        return $stmt->fetchColumn();
    }
    
    private function getCuentasBloqueadas() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE intentos_fallidos >= 4");
        return $stmt->fetchColumn();
    }
    
    private function getActividadReciente() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT ar.*, u.nombre as usuario_nombre, r.codigo as requerimiento_codigo
            FROM auditoria_requerimientos ar
            JOIN usuarios u ON ar.usuario_id = u.id
            JOIN requerimientos r ON ar.requerimiento_id = r.id
            ORDER BY ar.fecha DESC
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getEstadisticasSLA() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN fecha_completado <= fecha_limite THEN 1 ELSE 0 END) as cumplidos,
                AVG(TIMESTAMPDIFF(DAY, fecha_creacion, fecha_completado)) as tiempo_promedio
            FROM requerimientos 
            WHERE estado_general = 'completado'
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getMisRequerimientos($userId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM requerimientos WHERE usuario_solicitante_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function getActividadesPendientes($userId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM requerimiento_actividades 
            WHERE usuario_asignado_id = ? AND estado = 'en_proceso'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/$view.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>