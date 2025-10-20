<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener datos para el reporte
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

// Obtener estadísticas por área
$query_areas = "SELECT a.nombre, 
    COUNT(r.id) as total,
    SUM(CASE WHEN r.estado = 'completado' THEN 1 ELSE 0 END) as completados
    FROM areas a 
    LEFT JOIN requerimientos r ON a.id = r.area_id 
    WHERE a.activo = 1 
    GROUP BY a.id, a.nombre 
    ORDER BY total DESC";
$stmt_areas = $db->prepare($query_areas);
$stmt_areas->execute();
$estadisticas_areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para forzar descarga como PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_opticap_' . date('Y-m-d') . '.pdf"');

// HTML optimizado para impresión PDF
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte OptiCAP</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; font-family: "DejaVu Sans", Arial, sans-serif; }
            .no-print { display: none; }
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            color: #333;
            font-size: 12px;
        }
        .header { 
            text-align: center; 
            color: #2c3e50; 
            border-bottom: 2px solid #34495e; 
            padding-bottom: 10px; 
            margin-bottom: 20px; 
        }
        .section { 
            margin: 15px 0; 
            page-break-inside: avoid;
        }
        .section-title { 
            background-color: #34495e; 
            color: white; 
            padding: 8px; 
            font-weight: bold; 
            border-radius: 3px; 
            margin-bottom: 10px;
            font-size: 14px;
        }
        .kpi-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .kpi-box { 
            border: 1px solid #e0e0e0; 
            border-radius: 5px; 
            padding: 12px; 
            flex: 1;
            min-width: 120px;
            text-align: center;
            background: #f9f9f9;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
            font-size: 11px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 6px; 
            text-align: left; 
        }
        th { 
            background-color: #34495e; 
            color: white; 
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer { 
            text-align: center; 
            margin-top: 30px; 
            color: #7f8c8d; 
            font-size: 10px; 
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        h1 { 
            color: #2c3e50; 
            font-size: 20px; 
            margin: 5px 0; 
        }
        h2 { 
            font-size: 16px; 
            margin: 3px 0; 
        }
        h3 { 
            font-size: 11px; 
            margin: 2px 0; 
            color: #7f8c8d; 
        }
        .print-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 0;
        }
        .print-button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="print-button" onclick="window.print()">🖨️ Imprimir PDF</button>
        <p>Presione el botón para imprimir como PDF, o use Ctrl+P</p>
    </div>

    <div class="header">
        <h1>REPORTE OPTICAP</h1>
        <p><strong>Sistema de Gestión de Requerimientos</strong></p>
        <p>Generado el: ' . date('d/m/Y H:i') . '</p>
    </div>

    <div class="section">
        <div class="section-title">ESTADÍSTICAS GENERALES</div>
        <div class="kpi-container">
            <div class="kpi-box">
                <h3>Total Requerimientos</h3>
                <h2 style="color: #3498db;">' . $estadisticas['total_requerimientos'] . '</h2>
            </div>
            <div class="kpi-box">
                <h3>Completados</h3>
                <h2 style="color: #27ae60;">' . $estadisticas['completados'] . '</h2>
            </div>
            <div class="kpi-box">
                <h3>En Proceso</h3>
                <h2 style="color: #f39c12;">' . $estadisticas['en_proceso'] . '</h2>
            </div>
            <div class="kpi-box">
                <h3>Pendientes</h3>
                <h2 style="color: #e74c3c;">' . $estadisticas['pendientes'] . '</h2>
            </div>
            <div class="kpi-box">
                <h3>Cancelados</h3>
                <h2 style="color: #95a5a6;">' . $estadisticas['cancelados'] . '</h2>
            </div>
            <div class="kpi-box">
                <h3>Tiempo Promedio</h3>
                <h2 style="color: #9b59b6;">' . round($estadisticas['tiempo_promedio'], 1) . ' días</h2>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">ESTADÍSTICAS POR ÁREA</div>
        <table>
            <tr>
                <th width="40%">Área</th>
                <th width="15%">Total</th>
                <th width="15%">Completados</th>
                <th width="15%">En Proceso</th>
                <th width="15%">Tasa de Completación</th>
            </tr>';

foreach ($estadisticas_areas as $area) {
    $tasa = $area['total'] > 0 ? round(($area['completados'] / $area['total']) * 100, 1) : 0;
    echo '<tr>
            <td>' . htmlspecialchars($area['nombre']) . '</td>
            <td>' . $area['total'] . '</td>
            <td>' . $area['completados'] . '</td>
            <td>' . ($area['total'] - $area['completados']) . '</td>
            <td>' . $tasa . '%</td>
          </tr>';
}

echo '
        </table>
    </div>

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema OptiCAP</p>
        <p>© ' . date('Y') . ' OptiCAP - Todos los derechos reservados</p>
    </div>

    <script>
        // Auto-imprimir si se desea
        setTimeout(function() {
            // Descomenta la siguiente línea para auto-imprimir
            // window.print();
        }, 1000);
    </script>
</body>
</html>';

exit;
?>