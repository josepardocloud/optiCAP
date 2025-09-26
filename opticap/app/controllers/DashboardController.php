<?php
class DashboardController {
    private $requerimientoModel;
    private $usuarioModel;
    private $reporteModel;
    
    public function __construct() {
        AuthHelper::checkAuth();
        $this->requerimientoModel = new Requerimiento();
        $this->usuarioModel = new Usuario();
        $this->reporteModel = new Reporte();
    }
    
    public function index() {
        $usuarioId = $_SESSION['user_id'];
        $rol = $_SESSION['user_role'];
        $areaId = $_SESSION['user_area'];
        
        $datos = [
            'totalRequerimientos' => $this->requerimientoModel->contarPorUsuarioArea($usuarioId, $areaId, $rol),
            'requerimientosPendientes' => $this->requerimientoModel->contarPorEstadoUsuario('pendiente', $usuarioId, $areaId, $rol),
            'requerimientosProceso' => $this->requerimientoModel->contarPorEstadoUsuario('en_proceso', $usuarioId, $areaId, $rol),
            'requerimientosCompletados' => $this->requerimientoModel->contarPorEstadoUsuario('completado', $usuarioId, $areaId, $rol),
            'requerimientosRecientes' => $this->requerimientoModel->obtenerRecientes($usuarioId, $areaId, $rol, 5),
            'slaData' => $this->reporteModel->obtenerSLAGeneral($areaId, $rol),
            'actividadesPendientes' => $this->requerimientoModel->obtenerActividadesPendientes($usuarioId)
        ];
        
        require_once 'app/views/dashboard/index.php';
    }
    
    public function supervisor() {
        AuthHelper::requireRole('supervisor');
        
        $datos = [
            'totalRequerimientos' => $this->requerimientoModel->contarTotales(),
            'requerimientosPorArea' => $this->reporteModel->obtenerRequerimientosPorArea(),
            'slaPorArea' => $this->reporteModel->obtenerSLAPorArea(),
            'actividadesAtrasadas' => $this->requerimientoModel->obtenerActividadesAtrasadas(),
            'estadisticasMensuales' => $this->reporteModel->obtenerEstadisticasMensuales()
        ];
        
        require_once 'app/views/dashboard/supervisor.php';
    }
}
?>