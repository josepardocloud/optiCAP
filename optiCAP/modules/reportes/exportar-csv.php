<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener parámetros
$reporte = $_GET['reporte'] ?? 'completo';

// Obtener estadísticas generales (CONSULTA MEJORADA)
$query_estadisticas = "SELECT 
    COUNT(*) as total_requerimientos,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
    AVG(TIMESTAMPDIFF(DAY, fecha_creacion, COALESCE(
        (SELECT MAX(fecha_fin) FROM seguimiento_actividades WHERE requerimiento_id = requerimientos.id AND estado = 'completado'),
        NOW()
    ))) as tiempo_promedio
    FROM requerimientos";
$stmt_estadisticas = $db->prepare($query_estadisticas);
$stmt_estadisticas->execute();
$estadisticas = $stmt_estadisticas->fetch(PDO::FETCH_ASSOC);

// Obtener estadísticas por área (CONSULTA MEJORADA)
$query_areas = "SELECT a.nombre, 
    COUNT(r.id) as total,
    SUM(CASE WHEN r.estado = 'completado' THEN 1 ELSE 0 END) as completados,
    AVG(CASE WHEN r.estado = 'completado' THEN 
        TIMESTAMPDIFF(DAY, r.fecha_creacion, 
            (SELECT MAX(fecha_fin) FROM seguimiento_actividades WHERE requerimiento_id = r.id AND estado = 'completado')
        ) ELSE NULL END) as tiempo_promedio
    FROM areas a 
    LEFT JOIN requerimientos r ON a.id = r.area_id 
    WHERE a.activo = 1 
    GROUP BY a.id, a.nombre 
    ORDER BY total DESC";
$stmt_areas = $db->prepare($query_areas);
$stmt_areas->execute();
$estadisticas_areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas por proceso (CONSULTA MEJORADA)
$query_procesos = "SELECT p.nombre, p.tipo,
    COUNT(r.id) as total,
    SUM(CASE WHEN r.estado = 'completado' THEN 1 ELSE 0 END) as completados,
    AVG(CASE WHEN r.estado = 'completado' THEN 
        TIMESTAMPDIFF(DAY, r.fecha_creacion, 
            (SELECT MAX(fecha_fin) FROM seguimiento_actividades WHERE requerimiento_id = r.id AND estado = 'completado')
        ) ELSE NULL END) as tiempo_promedio
    FROM procesos p 
    LEFT JOIN requerimientos r ON p.id = r.proceso_id 
    WHERE p.activo = 1 
    GROUP BY p.id, p.nombre, p.tipo 
    ORDER BY total DESC";
$stmt_procesos = $db->prepare($query_procesos);
$stmt_procesos->execute();
$estadisticas_procesos = $stmt_procesos->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas de SLA (NUEVO)
$query_sla_cumplimiento = "SELECT 
    COUNT(*) as total_actividades_completadas,
    SUM(CASE 
        WHEN a.sla_objetivo IS NOT NULL AND 
             TIMESTAMPDIFF(DAY, sa.fecha_inicio, sa.fecha_fin) <= a.sla_objetivo 
        THEN 1 ELSE 0 
    END) as actividades_cumplen_sla,
    AVG(CASE 
        WHEN a.sla_objetivo IS NOT NULL AND sa.fecha_inicio IS NOT NULL AND sa.fecha_fin IS NOT NULL
        THEN TIMESTAMPDIFF(DAY, sa.fecha_inicio, sa.fecha_fin) 
        ELSE NULL 
    END) as tiempo_real_promedio,
    AVG(a.sla_objetivo) as sla_promedio_estimado
    FROM seguimiento_actividades sa
    INNER JOIN actividades a ON sa.actividad_id = a.id
    WHERE sa.estado = 'completado'
    AND sa.fecha_inicio IS NOT NULL 
    AND sa.fecha_fin IS NOT NULL
    AND a.sla_objetivo IS NOT NULL";

$stmt_sla = $db->prepare($query_sla_cumplimiento);
$stmt_sla->execute();
$sla_estadisticas = $stmt_sla->fetch(PDO::FETCH_ASSOC);

// Calcular porcentaje de cumplimiento de SLA
$porcentaje_cumplimiento_sla = 0;
if ($sla_estadisticas['total_actividades_completadas'] > 0) {
    $porcentaje_cumplimiento_sla = round(
        ($sla_estadisticas['actividades_cumplen_sla'] / $sla_estadisticas['total_actividades_completadas']) * 100, 
        1
    );
}

// Configurar headers para CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reportes_opticap_' . date('Y-m-d') . '.csv"');

// Crear output
$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Escribir encabezados y datos
fputcsv($output, ['REPORTE OPTICAP - ' . date('d/m/Y')]);
fputcsv($output, ['Tipo de Reporte:', $reporte]);
fputcsv($output, ['Generado el:', date('d/m/Y H:i:s')]);
fputcsv($output, []); // Línea vacía

// KPIs Principales (MEJORADO)
fputcsv($output, ['KPIs PRINCIPALES']);
fputcsv($output, [
    'Total Requerimientos', 
    'Completados', 
    'En Proceso', 
    'Pendientes', 
    'Cancelados', 
    'Tiempo Promedio (días)',
    'Cumplimiento SLA (%)',
    'Eficiencia SLA (%)'
]);
fputcsv($output, [
    $estadisticas['total_requerimientos'],
    $estadisticas['completados'],
    $estadisticas['en_proceso'],
    $estadisticas['pendientes'],
    $estadisticas['cancelados'],
    round($estadisticas['tiempo_promedio'], 1),
    $porcentaje_cumplimiento_sla,
    $sla_estadisticas['sla_promedio_estimado'] > 0 ? 
        round(($sla_estadisticas['sla_promedio_estimado'] / max($sla_estadisticas['tiempo_real_promedio'], 1)) * 100, 1) : 0
]);
fputcsv($output, []); // Línea vacía

// Análisis de Cumplimiento SLA (NUEVA SECCIÓN)
fputcsv($output, ['ANÁLISIS DE CUMPLIMIENTO SLA']);
fputcsv($output, [
    'Actividades con SLA Completadas',
    'Actividades que Cumplen SLA',
    'Actividades que No Cumplen SLA',
    'Tasa de Cumplimiento (%)',
    'Tiempo Real Promedio (días)',
    'SLA Estimado Promedio (días)',
    'Diferencia Promedio (días)'
]);
fputcsv($output, [
    $sla_estadisticas['total_actividades_completadas'],
    $sla_estadisticas['actividades_cumplen_sla'],
    $sla_estadisticas['total_actividades_completadas'] - $sla_estadisticas['actividades_cumplen_sla'],
    $porcentaje_cumplimiento_sla,
    round($sla_estadisticas['tiempo_real_promedio'] ?? 0, 1),
    round($sla_estadisticas['sla_promedio_estimado'] ?? 0, 1),
    round(($sla_estadisticas['tiempo_real_promedio'] ?? 0) - ($sla_estadisticas['sla_promedio_estimado'] ?? 0), 1)
]);
fputcsv($output, []); // Línea vacía

// Estadísticas por área (MEJORADO)
fputcsv($output, ['ESTADÍSTICAS POR ÁREA']);
fputcsv($output, ['Área', 'Total', 'Completados', 'Tasa de Completación (%)', 'Tiempo Promedio (días)']);
foreach ($estadisticas_areas as $area) {
    $tasa = $area['total'] > 0 ? round(($area['completados'] / $area['total']) * 100, 1) : 0;
    fputcsv($output, [
        $area['nombre'],
        $area['total'],
        $area['completados'],
        $tasa,
        round($area['tiempo_promedio'] ?? 0, 1)
    ]);
}
fputcsv($output, []); // Línea vacía

// Estadísticas por proceso (MEJORADO)
fputcsv($output, ['ESTADÍSTICAS POR PROCESO']);
fputcsv($output, ['Proceso', 'Tipo', 'Total', 'Completados', 'Tasa de Completación (%)', 'Tiempo Promedio (días)']);
foreach ($estadisticas_procesos as $proceso) {
    $tasa = $proceso['total'] > 0 ? round(($proceso['completados'] / $proceso['total']) * 100, 1) : 0;
    fputcsv($output, [
        $proceso['nombre'],
        $proceso['tipo'],
        $proceso['total'],
        $proceso['completados'],
        $tasa,
        round($proceso['tiempo_promedio'] ?? 0, 1)
    ]);
}

fclose($output);
exit;
?>