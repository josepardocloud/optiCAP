<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener parámetros de filtro
$reporte = $_GET['reporte'] ?? 'completo';
$detalle = $_GET['detalle'] ?? 'detallado';

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

// Obtener actividades más lentas (NUEVO)
$query_actividades_lentas = "SELECT * FROM (
    SELECT a.nombre as actividad, p.nombre as proceso,
        AVG(TIMESTAMPDIFF(DAY, sa.fecha_inicio, sa.fecha_fin)) as tiempo_promedio,
        a.tiempo_dias as tiempo_estimado,
        COUNT(sa.id) as total_ejecuciones
    FROM actividades a 
    INNER JOIN procesos p ON a.proceso_id = p.id 
    INNER JOIN seguimiento_actividades sa ON a.id = sa.actividad_id 
    WHERE sa.estado = 'completado' 
    AND sa.fecha_inicio IS NOT NULL 
    AND sa.fecha_fin IS NOT NULL 
    GROUP BY a.id, a.nombre, p.nombre, a.tiempo_dias 
) as subquery
WHERE tiempo_promedio > tiempo_estimado
ORDER BY (tiempo_promedio - tiempo_estimado) DESC 
LIMIT 10";
$stmt_actividades_lentas = $db->prepare($query_actividades_lentas);
$stmt_actividades_lentas->execute();
$actividades_lentas = $stmt_actividades_lentas->fetchAll(PDO::FETCH_ASSOC);

// Obtener procesos con mejor cumplimiento SLA (NUEVO)
$query_procesos_sla = "SELECT 
    p.nombre as proceso_nombre,
    p.tipo,
    COUNT(*) as total_actividades,
    SUM(CASE 
        WHEN TIMESTAMPDIFF(DAY, sa.fecha_inicio, sa.fecha_fin) <= a.sla_objetivo 
        THEN 1 ELSE 0 
    END) as actividades_cumplen_sla,
    ROUND(
        (SUM(CASE 
            WHEN TIMESTAMPDIFF(DAY, sa.fecha_inicio, sa.fecha_fin) <= a.sla_objetivo 
            THEN 1 ELSE 0 
        END) / COUNT(*)) * 100, 1
    ) as porcentaje_cumplimiento,
    AVG(TIMESTAMPDIFF(DAY, sa.fecha_inicio, sa.fecha_fin)) as tiempo_real_promedio,
    AVG(a.sla_objetivo) as sla_estimado_promedio
    FROM seguimiento_actividades sa
    INNER JOIN actividades a ON sa.actividad_id = a.id
    INNER JOIN procesos p ON a.proceso_id = p.id
    WHERE sa.estado = 'completado'
    AND sa.fecha_inicio IS NOT NULL 
    AND sa.fecha_fin IS NOT NULL
    AND a.sla_objetivo IS NOT NULL
    GROUP BY p.id, p.nombre, p.tipo
    HAVING total_actividades >= 5
    ORDER BY porcentaje_cumplimiento DESC
    LIMIT 10";

$stmt_procesos_sla = $db->prepare($query_procesos_sla);
$stmt_procesos_sla->execute();
$procesos_sla = $stmt_procesos_sla->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reportes_opticap_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; font-weight: bold; }
    .header { background-color: #2c3e50; color: white; text-align: center; padding: 15px; }
    .section-header { background-color: #34495e; color: white; padding: 10px; }
    .kpi-number { font-size: 18px; font-weight: bold; }
    .positive { color: #27ae60; }
    .negative { color: #e74c3c; }
    .warning { color: #f39c12; }
</style>";
echo "</head>";
echo "<body>";

// Título del reporte
echo "<table>";
echo "<tr><td colspan='8' class='header'><h2>REPORTE OPTICAP - " . date('d/m/Y') . "</h2></td></tr>";
echo "</table>";

// KPIs Principales (MEJORADO)
echo "<table>";
echo "<tr><td colspan='8' class='section-header'><strong>KPIs PRINCIPALES</strong></td></tr>";
echo "<tr>
        <th>Total Requerimientos</th>
        <th>Completados</th>
        <th>En Proceso</th>
        <th>Pendientes</th>
        <th>Tiempo Promedio</th>
        <th>Cumplimiento SLA</th>
        <th>Eficiencia SLA</th>
      </tr>";
echo "<tr>
        <td class='kpi-number'>{$estadisticas['total_requerimientos']}</td>
        <td class='kpi-number positive'>{$estadisticas['completados']}</td>
        <td class='kpi-number warning'>{$estadisticas['en_proceso']}</td>
        <td class='kpi-number'>{$estadisticas['pendientes']}</td>
        <td class='kpi-number'>" . round($estadisticas['tiempo_promedio'], 1) . " días</td>
        <td class='kpi-number " . ($porcentaje_cumplimiento_sla >= 80 ? 'positive' : 'negative') . "'>{$porcentaje_cumplimiento_sla}%</td>
        <td class='kpi-number'>";
        
if ($sla_estadisticas['sla_promedio_estimado'] > 0) {
    $eficiencia = ($sla_estadisticas['sla_promedio_estimado'] / max($sla_estadisticas['tiempo_real_promedio'], 1)) * 100;
    echo round(min($eficiencia, 100), 1) . "%";
} else {
    echo "0%";
}

echo "</td></tr>";
echo "</table>";

// Análisis de Cumplimiento SLA (NUEVA SECCIÓN)
echo "<table>";
echo "<tr><td colspan='6' class='section-header'><strong>ANÁLISIS DE CUMPLIMIENTO SLA</strong></td></tr>";
echo "<tr>
        <th>Actividades con SLA</th>
        <th>Cumplen SLA</th>
        <th>No Cumplen SLA</th>
        <th>Tasa Cumplimiento</th>
        <th>Tiempo Real Promedio</th>
        <th>SLA Estimado Promedio</th>
      </tr>";
echo "<tr>
        <td>{$sla_estadisticas['total_actividades_completadas']}</td>
        <td class='positive'>{$sla_estadisticas['actividades_cumplen_sla']}</td>
        <td class='negative'>" . ($sla_estadisticas['total_actividades_completadas'] - $sla_estadisticas['actividades_cumplen_sla']) . "</td>
        <td class='" . ($porcentaje_cumplimiento_sla >= 80 ? 'positive' : 'negative') . "'>{$porcentaje_cumplimiento_sla}%</td>
        <td>" . round($sla_estadisticas['tiempo_real_promedio'] ?? 0, 1) . " días</td>
        <td>" . round($sla_estadisticas['sla_promedio_estimado'] ?? 0, 1) . " días</td>
      </tr>";
echo "</table>";

// Procesos con Mejor Cumplimiento SLA (NUEVA SECCIÓN)
echo "<table>";
echo "<tr><td colspan='6' class='section-header'><strong>PROCESOS CON MEJOR CUMPLIMIENTO SLA</strong></td></tr>";
echo "<tr>
        <th>Proceso</th>
        <th>Tipo</th>
        <th>Actividades</th>
        <th>Cumplimiento</th>
        <th>Tiempo Real</th>
        <th>SLA Estimado</th>
      </tr>";
foreach ($procesos_sla as $proceso) {
    $clase_cumplimiento = $proceso['porcentaje_cumplimiento'] >= 80 ? 'positive' : 
                         ($proceso['porcentaje_cumplimiento'] >= 60 ? 'warning' : 'negative');
    
    echo "<tr>
            <td>{$proceso['proceso_nombre']}</td>
            <td>{$proceso['tipo']}</td>
            <td>{$proceso['total_actividades']}</td>
            <td class='{$clase_cumplimiento}'>{$proceso['porcentaje_cumplimiento']}%</td>
            <td>" . round($proceso['tiempo_real_promedio'], 1) . " días</td>
            <td>" . round($proceso['sla_estimado_promedio'], 1) . " días</td>
          </tr>";
}
echo "</table>";

// Actividades con Mayor Retraso (NUEVA SECCIÓN)
echo "<table>";
echo "<tr><td colspan='6' class='section-header'><strong>ACTIVIDADES CON MAYOR RETRASO</strong></td></tr>";
echo "<tr>
        <th>Actividad</th>
        <th>Proceso</th>
        <th>Tiempo Promedio</th>
        <th>Tiempo Estimado</th>
        <th>Diferencia</th>
        <th>Ejecuciones</th>
      </tr>";
foreach ($actividades_lentas as $actividad) {
    $diferencia = $actividad['tiempo_promedio'] - $actividad['tiempo_estimado'];
    $clase_diferencia = $diferencia > 5 ? 'negative' : 'warning';
    
    echo "<tr>
            <td>{$actividad['actividad']}</td>
            <td>{$actividad['proceso']}</td>
            <td>" . round($actividad['tiempo_promedio'], 1) . " días</td>
            <td>{$actividad['tiempo_estimado']} días</td>
            <td class='{$clase_diferencia}'>+" . round($diferencia, 1) . " días</td>
            <td>{$actividad['total_ejecuciones']}</td>
          </tr>";
}
echo "</table>";

// Estadísticas por área (MEJORADO)
echo "<table>";
echo "<tr><td colspan='5' class='section-header'><strong>ESTADÍSTICAS POR ÁREA</strong></td></tr>";
echo "<tr>
        <th>Área</th>
        <th>Total</th>
        <th>Completados</th>
        <th>Tasa de Completación</th>
        <th>Tiempo Promedio</th>
      </tr>";
foreach ($estadisticas_areas as $area) {
    $tasa = $area['total'] > 0 ? round(($area['completados'] / $area['total']) * 100, 1) : 0;
    $clase_tasa = $tasa >= 80 ? 'positive' : ($tasa >= 60 ? 'warning' : 'negative');
    
    echo "<tr>
            <td>{$area['nombre']}</td>
            <td>{$area['total']}</td>
            <td>{$area['completados']}</td>
            <td class='{$clase_tasa}'>{$tasa}%</td>
            <td>" . round($area['tiempo_promedio'] ?? 0, 1) . " días</td>
          </tr>";
}
echo "</table>";

// Estadísticas por proceso (MEJORADO)
echo "<table>";
echo "<tr><td colspan='6' class='section-header'><strong>ESTADÍSTICAS POR PROCESO</strong></td></tr>";
echo "<tr>
        <th>Proceso</th>
        <th>Tipo</th>
        <th>Total</th>
        <th>Completados</th>
        <th>Tasa de Completación</th>
        <th>Tiempo Promedio</th>
      </tr>";
foreach ($estadisticas_procesos as $proceso) {
    $tasa = $proceso['total'] > 0 ? round(($proceso['completados'] / $proceso['total']) * 100, 1) : 0;
    $clase_tasa = $tasa >= 80 ? 'positive' : ($tasa >= 60 ? 'warning' : 'negative');
    
    echo "<tr>
            <td><strong>{$proceso['nombre']}</strong></td>
            <td><span style='background-color: " . ($proceso['tipo'] == 'Bien' ? '#3498db' : '#2ecc71') . "; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;'>{$proceso['tipo']}</span></td>
            <td>{$proceso['total']}</td>
            <td>{$proceso['completados']}</td>
            <td class='{$clase_tasa}'>{$tasa}%</td>
            <td>" . round($proceso['tiempo_promedio'] ?? 0, 1) . " días</td>
          </tr>";
}
echo "</table>";

// Información del reporte
echo "<table>";
echo "<tr><td colspan='2' class='section-header'><strong>INFORMACIÓN DEL REPORTE</strong></td></tr>";
echo "<tr><td><strong>Generado el:</strong></td><td>" . date('d/m/Y H:i:s') . "</td></tr>";
echo "<tr><td><strong>Tipo de Reporte:</strong></td><td>" . ucfirst($reporte) . "</td></tr>";
echo "<tr><td><strong>Nivel de Detalle:</strong></td><td>" . ucfirst($detalle) . "</td></tr>";
echo "</table>";

echo "</body>";
echo "</html>";
exit;
?>