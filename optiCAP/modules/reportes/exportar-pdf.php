<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

// En una implementación real, aquí se incluiría una librería como TCPDF o Dompdf
// Por ahora, generaremos un HTML simple que se puede imprimir como PDF

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas para el reporte
$query_estadisticas = "SELECT 
    COUNT(*) as total_requerimientos,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados
    FROM requerimientos";
$stmt_estadisticas = $db->prepare($query_estadisticas);
$stmt_estadisticas->execute();
$estadisticas = $stmt_estadisticas->fetch(PDO::FETCH_ASSOC);

// Obtener configuración del sistema
$query_config = "SELECT nombre_sistema FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
$stmt_config = $db->prepare($query_config);
$stmt_config->execute();
$config = $stmt_config->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_opticap_' . date('Y-m-d') . '.pdf"');

// En una implementación real, aquí se generaría el PDF
// Por ahora, mostramos un mensaje
echo "En una implementación completa, aquí se generaría el PDF del reporte.\n\n";
echo "=== REPORTE OPTICAP ===\n";
echo "Fecha: " . date('d/m/Y') . "\n";
echo "Sistema: " . ($config['nombre_sistema'] ?? 'OptiCAP') . "\n\n";
echo "ESTADÍSTICAS GENERALES:\n";
echo "Total Requerimientos: " . $estadisticas['total_requerimientos'] . "\n";
echo "Completados: " . $estadisticas['completados'] . "\n";
echo "En Proceso: " . $estadisticas['en_proceso'] . "\n";
echo "Pendientes: " . $estadisticas['pendientes'] . "\n";
echo "Tasa de Completación: " . ($estadisticas['total_requerimientos'] > 0 ? 
    round(($estadisticas['completados'] / $estadisticas['total_requerimientos']) * 100, 1) : 0) . "%\n";

// Para una implementación real con TCPDF, el código sería similar a:
/*
require_once('tcpdf/tcpdf.php');
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Reporte OptiCAP', 0, 1, 'C');
$pdf->Output('reporte.pdf', 'D');
*/
?>