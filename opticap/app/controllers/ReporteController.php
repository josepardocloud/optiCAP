<?php
class ReporteController {
    private $reporteModel;
    private $requerimientoModel;
    
    public function __construct() {
        AuthHelper::requireRole('supervisor');
        $this->reporteModel = new Reporte();
        $this->requerimientoModel = new Requerimiento();
    }
    
    public function index() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        $areaId = $_GET['area_id'] ?? null;
        
        $datos = [
            'requerimientos' => $this->reporteModel->obtenerRequerimientosPorFecha($fechaInicio, $fechaFin, $areaId),
            'slaGeneral' => $this->reporteModel->obtenerSLAGeneral($areaId),
            'estadisticasAreas' => $this->reporteModel->obtenerEstadisticasPorArea($fechaInicio, $fechaFin),
            'tiemposPromedio' => $this->reporteModel->obtenerTiemposPromedio(),
            'areas' => (new Area())->obtenerTodas(),
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'area_id' => $areaId
            ]
        ];
        
        require_once 'app/views/reportes/requerimientos.php';
    }
    
    public function sla() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        
        $datos = [
            'slaPorArea' => $this->reporteModel->obtenerSLAPorArea($fechaInicio, $fechaFin),
            'slaPorActividad' => $this->reporteModel->obtenerSLAPorActividad($fechaInicio, $fechaFin),
            'tendenciasSLA' => $this->reporteModel->obtenerTendenciasSLA(),
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]
        ];
        
        require_once 'app/views/reportes/sla.php';
    }
    
    public function desempeno() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01', strtotime('-1 month'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        $datos = [
            'desempenoUsuarios' => $this->reporteModel->obtenerDesempenoUsuarios($fechaInicio, $fechaFin),
            'actividadesAtrasadas' => $this->reporteModel->obtenerActividadesAtrasadas($fechaInicio, $fechaFin),
            'eficienciaProceso' => $this->reporteModel->obtenerEficienciaProceso(),
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]
        ];
        
        require_once 'app/views/reportes/desempeno.php';
    }
    
    public function exportar() {
        $tipo = $_GET['tipo'] ?? 'pdf';
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        $areaId = $_GET['area_id'] ?? null;
        
        $datos = $this->reporteModel->obtenerDatosExportacion($fechaInicio, $fechaFin, $areaId);
        
        switch ($tipo) {
            case 'excel':
                $this->exportarExcel($datos, $fechaInicio, $fechaFin);
                break;
            case 'pdf':
            default:
                $this->exportarPDF($datos, $fechaInicio, $fechaFin);
                break;
        }
    }
    
    private function exportarPDF($datos, $fechaInicio, $fechaFin) {
        // Implementar exportación a PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="reporte_' . date('Ymd') . '.pdf"');
        
        // Aquí iría la generación del PDF (usando Dompdf, TCPDF, etc.)
        echo "PDF Export - To be implemented";
        exit();
    }
    
    private function exportarExcel($datos, $fechaInicio, $fechaFin) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_' . date('Ymd') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr><th colspan='4'>Reporte de Requerimientos</th></tr>";
        echo "<tr><th>Código</th><th>Título</th><th>Área</th><th>Estado</th></tr>";
        
        foreach ($datos as $fila) {
            echo "<tr>";
            echo "<td>{$fila['codigo']}</td>";
            echo "<td>{$fila['titulo']}</td>";
            echo "<td>{$fila['area_nombre']}</td>";
            echo "<td>{$fila['estado']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit();
    }
}
?>