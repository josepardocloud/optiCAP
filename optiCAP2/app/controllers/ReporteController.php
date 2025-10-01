<?php
class ReporteController {
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
    
    public function dashboard() {
        $user = $this->auth->getUser();
        
        $data = [
            'pageTitle' => 'Reportes y Análisis',
            'currentPage' => 'reportes',
            'user' => $user
        ];
        
        $this->renderView('reportes/dashboard', $data);
    }
    
    public function exportar() {
        $user = $this->auth->getUser();
        $filtros = $_GET ?? [];
        
        // Aplicar restricciones por rol
        if ($user['rol_nombre'] === 'Usuario') {
            $filtros['area_id'] = $user['area_id'];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $tipoReporte = $_POST['tipo_reporte'];
                $formato = $_POST['formato'];
                
                $reporte = $this->generarReporte($tipoReporte, $filtros);
                $this->exportarReporte($reporte, $formato, $tipoReporte);
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ' . SITE_URL . '/reportes/exportar');
                exit;
            }
        }
        
        $tiposReporte = $this->getTiposReporte($user);
        
        $data = [
            'pageTitle' => 'Exportar Reportes',
            'currentPage' => 'reportes',
            'user' => $user,
            'tiposReporte' => $tiposReporte,
            'filtros' => $filtros
        ];
        
        $this->renderView('reportes/exportar', $data);
    }
    
    public function metricas() {
        $user = $this->auth->getUser();
        
        $metricas = $this->obtenerMetricasCompletas($user);
        
        $data = [
            'pageTitle' => 'Métricas y Estadísticas',
            'currentPage' => 'reportes',
            'user' => $user,
            'metricas' => $metricas
        ];
        
        $this->renderView('reportes/metricas', $data);
    }
    
    private function generarReporte($tipoReporte, $filtros) {
        switch ($tipoReporte) {
            case 'requerimientos_estado':
                return $this->generarReporteRequerimientosEstado($filtros);
                
            case 'requerimientos_area':
                return $this->generarReporteRequerimientosArea($filtros);
                
            case 'sla_actividad':
                return $this->generarReporteSLAActividad($filtros);
                
            case 'saltos_condicionales':
                return $this->generarReporteSaltosCondicionales($filtros);
                
            case 'requisitos_incumplidos':
                return $this->generarReporteRequisitosIncumplidos($filtros);
                
            case 'tiempos_promedio':
                return $this->generarReporteTiemposPromedio($filtros);
                
            case 'comparativa_bienes_servicios':
                return $this->generarReporteComparativa($filtros);
                
            default:
                throw new Exception("Tipo de reporte no válido");
        }
    }
    
    private function generarReporteRequerimientosEstado($filtros) {
        $pdo = $this->db->getConnection();
        
        $where = $this->construirWhere($filtros);
        $params = $this->getParams($filtros);
        
        $stmt = $pdo->prepare("
            SELECT 
                estado_general,
                COUNT(*) as total,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM requerimientos $where), 2) as porcentaje
            FROM requerimientos 
            $where
            GROUP BY estado_general 
            ORDER BY total DESC
        ");
        
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'titulo' => 'Requerimientos por Estado',
            'columnas' => ['Estado', 'Total', 'Porcentaje'],
            'datos' => $datos
        ];
    }
    
    private function generarReporteRequerimientosArea($filtros) {
        $pdo = $this->db->getConnection();
        
        $where = $this->construirWhere($filtros, 'r');
        $params = $this->getParams($filtros);
        
        $stmt = $pdo->prepare("
            SELECT 
                a.nombre as area,
                COUNT(*) as total,
                SUM(CASE WHEN r.estado_general = 'completado' THEN 1 ELSE 0 END) as completados,
                ROUND(SUM(CASE WHEN r.estado_general = 'completado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as tasa_exito
            FROM requerimientos r
            JOIN areas a ON r.area_id = a.id
            $where
            GROUP BY a.id, a.nombre 
            ORDER BY total DESC
        ");
        
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'titulo' => 'Requerimientos por Área',
            'columnas' => ['Área', 'Total', 'Completados', 'Tasa de Éxito (%)'],
            'datos' => $datos
        ];
    }
    
    private function generarReporteSLAActividad($filtros) {
        $pdo = $this->db->getConnection();
        
        $where = $this->construirWhere($filtros, 'r');
        $params = $this->getParams($filtros);
        
        $stmt = $pdo->prepare("
            SELECT 
                a.numero_paso,
                a.nombre as actividad,
                COUNT(*) as total,
                AVG(TIMESTAMPDIFF(HOUR, ra.fecha_inicio, ra.fecha_fin)) as tiempo_promedio_horas,
                a.duracion_estimada as tiempo_estimado_horas
            FROM requerimiento_actividades ra
            JOIN actividades a ON ra.actividad_id = a.id
            JOIN requerimientos r ON ra.requerimiento_id = r.id
            WHERE ra.estado = 'finalizado' AND ra.fecha_inicio IS NOT NULL AND ra.fecha_fin IS NOT NULL
            $where
            GROUP BY a.id, a.numero_paso, a.nombre, a.duracion_estimada
            ORDER BY a.numero_paso
        ");
        
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'titulo' => 'Cumplimiento de SLA por Actividad',
            'columnas' => ['Paso', 'Actividad', 'Total', 'Tiempo Promedio (h)', 'Tiempo Estimado (h)'],
            'datos' => $datos
        ];
    }
    
    private function generarReporteSaltosCondicionales($filtros) {
        $pdo = $this->db->getConnection();
        
        $where = $this->construirWhere($filtros);
        $params = $this->getParams($filtros);
        
        $stmt = $pdo->prepare("
            SELECT 
                tp.nombre as tipo_proceso,
                COUNT(*) as total_requerimientos,
                SUM(CASE WHEN r.fecha_salto_condicional IS NOT NULL THEN 1 ELSE 0 END) as con_salto,
                ROUND(SUM(CASE WHEN r.fecha_salto_condicional IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as porcentaje_salto
            FROM requerimientos r
            JOIN tipos_proceso tp ON r.tipo_proceso_id = tp.id
            $where
            GROUP BY tp.id, tp.nombre
            ORDER BY total_requerimientos DESC
        ");
        
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'titulo' => 'Análisis de Saltos Condicionales',
            'columnas' => ['Tipo Proceso', 'Total Req.', 'Con Salto', '% Salto'],
            'datos' => $datos
        ];
    }
    
    private function exportarReporte($reporte, $formato, $nombreArchivo) {
        switch ($formato) {
            case 'pdf':
                $this->exportarPDF($reporte, $nombreArchivo);
                break;
                
            case 'excel':
                $this->exportarExcel($reporte, $nombreArchivo);
                break;
                
            case 'csv':
                $this->exportarCSV($reporte, $nombreArchivo);
                break;
                
            default:
                throw new Exception("Formato de exportación no válido");
        }
    }
    
    private function exportarPDF($reporte, $nombreArchivo) {
        // Usar DomPDF para generar PDF
        $dompdf = new Dompdf\Dompdf();
        
        $html = $this->generarHTMLReporte($reporte);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '_' . date('Y-m-d') . '.pdf"');
        
        echo $dompdf->output();
        exit;
    }
    
    private function exportarExcel($reporte, $nombreArchivo) {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Título
        $sheet->setCellValue('A1', $reporte['titulo']);
        $sheet->mergeCells('A1:' . chr(64 + count($reporte['columnas'])) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        
        // Columnas
        $col = 'A';
        foreach ($reporte['columnas'] as $columna) {
            $sheet->setCellValue($col . '3', $columna);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $col++;
        }
        
        // Datos
        $fila = 4;
        foreach ($reporte['datos'] as $filaDatos) {
            $col = 'A';
            foreach ($filaDatos as $valor) {
                $sheet->setCellValue($col . $fila, $valor);
                $col++;
            }
            $fila++;
        }
        
        // Autoajustar columnas
        foreach (range('A', chr(64 + count($reporte['columnas']))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '_' . date('Y-m-d') . '.xlsx"');
        
        $writer->save('php://output');
        exit;
    }
    
    private function exportarCSV($reporte, $nombreArchivo) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // Columnas
        fputcsv($output, $reporte['columnas']);
        
        // Datos
        foreach ($reporte['datos'] as $fila) {
            fputcsv($output, $fila);
        }
        
        fclose($output);
        exit;
    }
    
    private function generarHTMLReporte($reporte) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title><?php echo $reporte['titulo']; ?></title>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 20px; font-weight: bold; }
                .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f2f2f2; font-weight: bold; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title"><?php echo $reporte['titulo']; ?></div>
                <div>Sistema OptiCAP2 - Generado el: <?php echo date('d/m/Y H:i'); ?></div>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <?php foreach ($reporte['columnas'] as $columna): ?>
                        <th><?php echo $columna; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reporte['datos'] as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $valor): ?>
                        <td><?php echo $valor; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="footer">
                Reporte generado automáticamente por el Sistema OptiCAP2
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function getTiposReporte($user) {
        $tipos = [
            'requerimientos_estado' => 'Requerimientos por Estado',
            'requerimientos_area' => 'Requerimientos por Área',
            'sla_actividad' => 'Cumplimiento SLA por Actividad',
            'saltos_condicionales' => 'Análisis de Saltos Condicionales',
            'requisitos_incumplidos' => 'Requisitos más Incumplidos',
            'tiempos_promedio' => 'Tiempos Promedio por Actividad',
            'comparativa_bienes_servicios' => 'Comparativa Bienes vs Servicios'
        ];
        
        // Usuarios normales solo pueden ver reportes de su área
        if ($user['rol_nombre'] === 'Usuario') {
            unset($tipos['requerimientos_area']);
        }
        
        return $tipos;
    }
    
    private function construirWhere($filtros, $alias = '') {
        $where = '';
        $prefijo = $alias ? $alias . '.' : '';
        
        if (!empty($filtros['fecha_desde'])) {
            $where .= " AND {$prefijo}fecha_creacion >= '" . $filtros['fecha_desde'] . "'";
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $where .= " AND {$prefijo}fecha_creacion <= '" . $filtros['fecha_hasta'] . " 23:59:59'";
        }
        
        if (!empty($filtros['area_id'])) {
            $where .= " AND {$prefijo}area_id = " . intval($filtros['area_id']);
        }
        
        if (!empty($filtros['tipo_proceso_id'])) {
            $where .= " AND {$prefijo}tipo_proceso_id = " . intval($filtros['tipo_proceso_id']);
        }
        
        return $where ? 'WHERE 1=1' . $where : '';
    }
    
    private function getParams($filtros) {
        $params = [];
        // Los parámetros se manejan en las consultas individuales
        return $params;
    }
    
    private function obtenerMetricasCompletas($user) {
        return [
            'general' => $this->obtenerMetricasGenerales($user),
            'sla' => $this->obtenerMetricasSLA($user),
            'actividades' => $this->obtenerMetricasActividades($user),
            'eficiencia' => $this->obtenerMetricasEficiencia($user)
        ];
    }
    
    private function obtenerMetricasGenerales($user) {
        $pdo = $this->db->getConnection();
        
        $where = $this->construirWhere(['area_id' => $user['rol_nombre'] === 'Usuario' ? $user['area_id'] : null]);
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_requerimientos,
                SUM(CASE WHEN estado_general = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado_general = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado_general = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                AVG(progreso) as progreso_promedio
            FROM requerimientos 
            $where
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/$view.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>