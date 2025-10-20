<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas generales
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

// Obtener estadísticas por proceso
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

// Obtener actividades más lentas
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

// Obtener estadísticas de cumplimiento de SLA
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

// Obtener tendencia de cumplimiento de SLA por mes
$query_tendencia_sla = "SELECT 
    DATE_FORMAT(sa.fecha_fin, '%Y-%m') as mes,
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
    ) as porcentaje_cumplimiento
    FROM seguimiento_actividades sa
    INNER JOIN actividades a ON sa.actividad_id = a.id
    WHERE sa.estado = 'completado'
    AND sa.fecha_inicio IS NOT NULL 
    AND sa.fecha_fin IS NOT NULL
    AND a.sla_objetivo IS NOT NULL
    AND sa.fecha_fin >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(sa.fecha_fin, '%Y-%m')
    ORDER BY mes ASC";

$stmt_tendencia = $db->prepare($query_tendencia_sla);
$stmt_tendencia->execute();
$tendencia_sla = $stmt_tendencia->fetchAll(PDO::FETCH_ASSOC);

// Obtener procesos con mejor y peor cumplimiento de SLA
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Analytics - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .kpi-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .kpi-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .kpi-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }
        .kpi-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .border-primary { border-left: 4px solid #007bff !important; }
        .border-success { border-left: 4px solid #28a745 !important; }
        .border-warning { border-left: 4px solid #ffc107 !important; }
        .border-info { border-left: 4px solid #17a2b8 !important; }
        .border-purple { border-left: 4px solid #6f42c1 !important; }
        .border-orange { border-left: 4px solid #fd7e14 !important; }
        
        /* ESTILOS MEJORADOS PARA BOTONES */
        .export-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn-export-group {
            position: relative;
            display: inline-block;
        }
        
        .btn-export-main {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-export-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-export-main:hover::before {
            left: 100%;
        }
        
        .btn-export-main:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .btn-export-main:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Botón Imprimir Mejorado */
        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-print:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
        
        /* Botón Excel Mejorado */
        .btn-excel {
            background: linear-gradient(135deg, #21c45d 0%, #059669 100%);
            color: white;
        }
        
        .btn-excel:hover {
            background: linear-gradient(135deg, #059669 0%, #21c45d 100%);
            color: white;
        }
        
        /* Botón CSV Mejorado */
        .btn-csv {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .btn-csv:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            color: white;
        }
        
        /* Botón PDF Mejorado */
        .btn-pdf {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-pdf:hover {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
        }
        
        /* Dropdown de Exportación Mejorado */
        .export-dropdown {
            min-width: 200px;
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .export-dropdown .dropdown-item {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .export-dropdown .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .export-dropdown .dropdown-item:hover {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            transform: translateX(5px);
        }
        
        .export-dropdown .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Badge de notificación */
        .export-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Modal de configuración de exportación */
        .export-modal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .export-modal .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        .export-option-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-align: center;
        }
        
        .export-option-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.2);
        }
        
        .export-option-card.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        
        .sla-progress {
            height: 25px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .sla-badge-excelente { background-color: #28a745; }
        .sla-badge-bueno { background-color: #20c997; }
        .sla-badge-regular { background-color: #ffc107; }
        .sla-badge-pobre { background-color: #fd7e14; }
        .sla-badge-critico { background-color: #dc3545; }
        
        /* Loading spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #000 !important; }
            .kpi-card { box-shadow: none !important; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .export-actions {
                justify-content: center;
            }
            
            .btn-export-main {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reportes y Analytics</h1>
                    
                    <!-- SECCIÓN MEJORADA DE BOTONES DE EXPORTACIÓN -->
                    <div class="export-actions no-print">
                        <!-- Botón Principal de Impresión -->
                        <div class="btn-export-group">
                            <button onclick="showPrintOptions()" class="btn-export-main btn-print">
                                <i class="fas fa-print"></i>
                                Imprimir
                                <span class="export-badge">3</span>
                            </button>
                        </div>
                        
                        <!-- Dropdown de Exportación -->
                        <div class="btn-export-group dropdown">
                            <button class="btn-export-main btn-excel dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download"></i>
                                Exportar
                                <i class="fas fa-chevron-down ms-1" style="font-size: 12px;"></i>
                            </button>
                            <ul class="dropdown-menu export-dropdown">
                                <li>
                                    <a class="dropdown-item" href="exportar-excel.php?reporte=completo">
                                        <i class="fas fa-file-excel text-success"></i>
                                        <div>
                                            <strong>Excel Completo</strong>
                                            <small class="d-block text-muted">Todos los datos en formato Excel</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="exportar-excel.php?reporte=sla">
                                        <i class="fas fa-bullseye text-purple"></i>
                                        <div>
                                            <strong>Solo Métricas SLA</strong>
                                            <small class="d-block text-muted">KPIs de cumplimiento</small>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="exportar-csv.php">
                                        <i class="fas fa-file-csv text-primary"></i>
                                        <div>
                                            <strong>CSV para Análisis</strong>
                                            <small class="d-block text-muted">Datos estructurados</small>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#exportModal">
                                        <i class="fas fa-cog text-secondary"></i>
                                        <div>
                                            <strong>Exportación Avanzada</strong>
                                            <small class="d-block text-muted">Configurar parámetros</small>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                       
                    </div>
                </div>

                <!-- Filtros de Reportes -->
                <div class="card mb-4 no-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Filtros de Reportes</h5>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" id="formFiltros">
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_proceso" class="form-label">Tipo de Proceso</label>
                                <select class="form-select" id="tipo_proceso" name="tipo_proceso">
                                    <option value="">Todos</option>
                                    <option value="Bien">Bienes</option>
                                    <option value="Servicio">Servicios</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="area_id" class="form-label">Área</label>
                                <select class="form-select" id="area_id" name="area_id">
                                    <option value="">Todas las áreas</option>
                                    <?php
                                    $query_areas_filtro = "SELECT * FROM areas WHERE activo = 1 ORDER BY nombre";
                                    $stmt_areas_filtro = $db->prepare($query_areas_filtro);
                                    $stmt_areas_filtro->execute();
                                    $areas_filtro = $stmt_areas_filtro->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($areas_filtro as $area):
                                    ?>
                                    <option value="<?php echo $area['id']; ?>"><?php echo $area['nombre']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                                <button type="reset" class="btn btn-outline-secondary">Limpiar Filtros</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- KPIs Principales - INCLUYENDO SLA -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card kpi-card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">TOTAL REQUERIMIENTOS</p>
                                        <h2 class="kpi-number text-primary"><?php echo $estadisticas['total_requerimientos']; ?></h2>
                                    </div>
                                    <div class="kpi-icon text-primary">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card kpi-card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">COMPLETADOS</p>
                                        <h2 class="kpi-number text-success"><?php echo $estadisticas['completados']; ?></h2>
                                        <small class="text-muted">
                                            Tasa: <?php echo $estadisticas['total_requerimientos'] > 0 ? 
                                                round(($estadisticas['completados'] / $estadisticas['total_requerimientos']) * 100, 1) : 0; ?>%
                                        </small>
                                    </div>
                                    <div class="kpi-icon text-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card kpi-card border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">EN PROCESO</p>
                                        <h2 class="kpi-number text-warning"><?php echo $estadisticas['en_proceso']; ?></h2>
                                    </div>
                                    <div class="kpi-icon text-warning">
                                        <i class="fas fa-spinner"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card kpi-card border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">TIEMPO PROMEDIO</p>
                                        <h2 class="kpi-number text-info"><?php echo round($estadisticas['tiempo_promedio'], 1); ?> días</h2>
                                    </div>
                                    <div class="kpi-icon text-info">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- KPI de Cumplimiento de SLA -->
                    <div class="col-md-2">
                        <div class="card kpi-card border-purple">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">CUMPLIMIENTO SLA</p>
                                        <h2 class="kpi-number text-purple"><?php echo $porcentaje_cumplimiento_sla; ?>%</h2>
                                        <small class="text-muted">
                                            <?php echo $sla_estadisticas['actividades_cumplen_sla'] ?? 0; ?>/<?php echo $sla_estadisticas['total_actividades_completadas'] ?? 0; ?> act.
                                        </small>
                                    </div>
                                    <div class="kpi-icon text-purple">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- KPI de Eficiencia SLA -->
                    <div class="col-md-2">
                        <div class="card kpi-card border-orange">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">EFICIENCIA SLA</p>
                                        <h2 class="kpi-number text-orange">
                                            <?php 
                                            if ($sla_estadisticas['sla_promedio_estimado'] > 0) {
                                                $eficiencia = ($sla_estadisticas['sla_promedio_estimado'] / max($sla_estadisticas['tiempo_real_promedio'], 1)) * 100;
                                                echo round(min($eficiencia, 100), 1);
                                            } else {
                                                echo "0";
                                            }
                                            ?>%
                                        </h2>
                                        <small class="text-muted">
                                            Real: <?php echo round($sla_estadisticas['tiempo_real_promedio'] ?? 0, 1); ?>d
                                        </small>
                                    </div>
                                    <div class="kpi-icon text-orange">
                                        <i class="fas fa-tachometer-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos - INCLUYENDO TENDENCIA SLA -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Distribución por Estado</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartEstados"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Tendencia de Cumplimiento SLA</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartTendenciaSLA"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: Análisis de Cumplimiento SLA -->
                <div class="card mb-4">
                    <div class="card-header bg-purple text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bullseye me-2"></i>Análisis de Cumplimiento de SLA
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <h6>Resumen de Cumplimiento</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="text-center p-3 border rounded">
                                                <div class="h4 mb-1 text-primary"><?php echo $sla_estadisticas['total_actividades_completadas'] ?? 0; ?></div>
                                                <small class="text-muted">Actividades con SLA Completadas</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-3 border rounded">
                                                <div class="h4 mb-1 text-success"><?php echo $sla_estadisticas['actividades_cumplen_sla'] ?? 0; ?></div>
                                                <small class="text-muted">Cumplen SLA</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-3 border rounded">
                                                <div class="h4 mb-1 text-danger">
                                                    <?php echo ($sla_estadisticas['total_actividades_completadas'] ?? 0) - ($sla_estadisticas['actividades_cumplen_sla'] ?? 0); ?>
                                                </div>
                                                <small class="text-muted">No Cumplen SLA</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Progreso de Cumplimiento</h6>
                                    <div class="sla-progress bg-light mb-2">
                                        <div class="progress-bar 
                                            <?php
                                            if ($porcentaje_cumplimiento_sla >= 90) echo 'sla-badge-excelente';
                                            elseif ($porcentaje_cumplimiento_sla >= 80) echo 'sla-badge-bueno';
                                            elseif ($porcentaje_cumplimiento_sla >= 70) echo 'sla-badge-regular';
                                            elseif ($porcentaje_cumplimiento_sla >= 60) echo 'sla-badge-pobre';
                                            else echo 'sla-badge-critico';
                                            ?>
                                        " style="width: <?php echo $porcentaje_cumplimiento_sla; ?>%">
                                            <span class="px-2 text-white fw-bold"><?php echo $porcentaje_cumplimiento_sla; ?>%</span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>0%</span>
                                        <span>Meta: 80%</span>
                                        <span>100%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Comparativa de Tiempos</h6>
                                        <div class="mb-3">
                                            <small class="text-muted">SLA Estimado Promedio</small>
                                            <div class="h5 text-info"><?php echo round($sla_estadisticas['sla_promedio_estimado'] ?? 0, 1); ?> días</div>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">Tiempo Real Promedio</small>
                                            <div class="h5 <?php echo ($sla_estadisticas['tiempo_real_promedio'] ?? 0) <= ($sla_estadisticas['sla_promedio_estimado'] ?? 0) ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo round($sla_estadisticas['tiempo_real_promedio'] ?? 0, 1); ?> días
                                            </div>
                                        </div>
                                        <div>
                                            <small class="text-muted">Diferencia</small>
                                            <div class="h5 <?php echo (($sla_estadisticas['tiempo_real_promedio'] ?? 0) - ($sla_estadisticas['sla_promedio_estimado'] ?? 0)) <= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo round(($sla_estadisticas['tiempo_real_promedio'] ?? 0) - ($sla_estadisticas['sla_promedio_estimado'] ?? 0), 1); ?> días
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tablas de Detalles - INCLUYENDO PROCESOS CON MEJOR SLA -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Procesos con Mejor Cumplimiento SLA</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Proceso</th>
                                                <th>Tipo</th>
                                                <th>Cumplimiento</th>
                                                <th>Actividades</th>
                                                <th>Eficiencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($procesos_sla as $proceso): 
                                                $clase_cumplimiento = '';
                                                if ($proceso['porcentaje_cumplimiento'] >= 90) $clase_cumplimiento = 'sla-badge-excelente';
                                                elseif ($proceso['porcentaje_cumplimiento'] >= 80) $clase_cumplimiento = 'sla-badge-bueno';
                                                elseif ($proceso['porcentaje_cumplimiento'] >= 70) $clase_cumplimiento = 'sla-badge-regular';
                                                elseif ($proceso['porcentaje_cumplimiento'] >= 60) $clase_cumplimiento = 'sla-badge-pobre';
                                                else $clase_cumplimiento = 'sla-badge-critico';
                                            ?>
                                            <tr>
                                                <td><?php echo $proceso['proceso_nombre']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $proceso['tipo'] == 'Bien' ? 'info' : 'success'; ?>">
                                                        <?php echo $proceso['tipo']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $clase_cumplimiento; ?> text-white">
                                                        <?php echo $proceso['porcentaje_cumplimiento']; ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo $proceso['total_actividades']; ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo round($proceso['tiempo_real_promedio'], 1); ?>d / 
                                                        <?php echo round($proceso['sla_estimado_promedio'], 1); ?>d
                                                    </small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actividades con Mayor Retraso</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Actividad</th>
                                                <th>Proceso</th>
                                                <th>Promedio</th>
                                                <th>Estimado</th>
                                                <th>Diferencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($actividades_lentas as $actividad): 
                                                $diferencia = $actividad['tiempo_promedio'] - $actividad['tiempo_estimado'];
                                            ?>
                                            <tr>
                                                <td><?php echo $actividad['actividad']; ?></td>
                                                <td><?php echo $actividad['proceso']; ?></td>
                                                <td><?php echo round($actividad['tiempo_promedio'], 1); ?> días</td>
                                                <td><?php echo $actividad['tiempo_estimado']; ?> días</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $diferencia > 5 ? 'danger' : 'warning'; ?>">
                                                        +<?php echo round($diferencia, 1); ?> días
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporte de Procesos -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Estadísticas por Proceso</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Proceso</th>
                                        <th>Tipo</th>
                                        <th>Total</th>
                                        <th>Completados</th>
                                        <th>Tasa</th>
                                        <th>Tiempo Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas_procesos as $proceso): 
                                        $tasa_completacion = $proceso['total'] > 0 ? 
                                            round(($proceso['completados'] / $proceso['total']) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $proceso['nombre']; ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $proceso['tipo'] == 'Bien' ? 'info' : 'success'; ?>">
                                                <?php echo $proceso['tipo']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $proceso['total']; ?></td>
                                        <td><?php echo $proceso['completados']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $tasa_completacion >= 80 ? 'success' : ($tasa_completacion >= 60 ? 'warning' : 'danger'); ?>">
                                                <?php echo $tasa_completacion; ?>%
                                            </span>
                                        </td>
                                        <td><?php echo round($proceso['tiempo_promedio'] ?? 0, 1); ?> días</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Configuración de Exportación Avanzada -->
    <div class="modal fade export-modal" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="fas fa-cog me-2"></i>Configuración de Exportación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Formato de Exportación</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="export-option-card selected" onclick="selectFormat('excel')">
                                        <i class="fas fa-file-excel fa-2x text-success mb-3"></i>
                                        <div class="fw-bold">Excel</div>
                                        <small class="text-muted">.xlsx</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="export-option-card" onclick="selectFormat('csv')">
                                        <i class="fas fa-file-csv fa-2x text-primary mb-3"></i>
                                        <div class="fw-bold">CSV</div>
                                        <small class="text-muted">.csv</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="export-option-card" onclick="selectFormat('pdf')">
                                        <i class="fas fa-file-pdf fa-2x text-danger mb-3"></i>
                                        <div class="fw-bold">PDF</div>
                                        <small class="text-muted">.pdf</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="export-option-card" onclick="selectFormat('json')">
                                        <i class="fas fa-code fa-2x text-warning mb-3"></i>
                                        <div class="fw-bold">JSON</div>
                                        <small class="text-muted">.json</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Opciones de Exportación</h6>
                            <div class="mb-3">
                                <label class="form-label">Rango de Fechas</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="exportFechaDesde">
                                    <span class="input-group-text">a</span>
                                    <input type="date" class="form-control" id="exportFechaHasta">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Incluir Gráficos</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="incluirGraficos" checked>
                                    <label class="form-check-label" for="incluirGraficos">Exportar imágenes de gráficos</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nivel de Detalle</label>
                                <select class="form-select" id="nivelDetalle">
                                    <option value="resumen">Resumen Ejecutivo</option>
                                    <option value="detallado" selected>Detallado</option>
                                    <option value="completo">Completo (Todos los datos)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> La exportación puede tomar algunos segundos dependiendo de la cantidad de datos seleccionada.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="procesarExportacion()">
                        <i class="fas fa-download me-2"></i>Generar Exportación
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Funciones para la exportación mejorada
        let selectedFormat = 'excel';
        
        function selectFormat(format) {
            selectedFormat = format;
            // Remover selección anterior
            document.querySelectorAll('.export-option-card').forEach(card => {
                card.classList.remove('selected');
            });
            // Agregar selección actual
            event.currentTarget.classList.add('selected');
        }
        
        function showPrintOptions() {
            // Crear modal de opciones de impresión
            const printModalHTML = `
                <div class="modal fade" id="printOptionsModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Opciones de Impresión</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="printOption" id="printResumen" checked>
                                    <label class="form-check-label" for="printResumen">
                                        Resumen Ejecutivo
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="printOption" id="printCompleto">
                                    <label class="form-check-label" for="printCompleto">
                                        Reporte Completo
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="printOption" id="printSLA">
                                    <label class="form-check-label" for="printSLA">
                                        Solo Métricas SLA
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="printGraficos" checked>
                                    <label class="form-check-label" for="printGraficos">
                                        Incluir Gráficos
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="ejecutarImpresion()">
                                    <i class="fas fa-print me-2"></i>Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar modal al DOM
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = printModalHTML;
            document.body.appendChild(modalContainer);
            
            // Mostrar modal
            const printModal = new bootstrap.Modal(document.getElementById('printOptionsModal'));
            printModal.show();
            
            // Limpiar después de cerrar
            document.getElementById('printOptionsModal').addEventListener('hidden.bs.modal', function() {
                modalContainer.remove();
            });
        }
        
        function ejecutarImpresion() {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('printOptionsModal')).hide();
            
            // Aplicar opciones de impresión (en una implementación real)
            const incluirGraficos = document.getElementById('printGraficos').checked;
            
            if (!incluirGraficos) {
                // Ocultar gráficos temporalmente
                document.querySelectorAll('.chart-container').forEach(chart => {
                    chart.style.display = 'none';
                });
            }
            
            // Esperar un momento para que se oculten los elementos
            setTimeout(() => {
                window.print();
                
                // Restaurar visibilidad después de imprimir
                if (!incluirGraficos) {
                    document.querySelectorAll('.chart-container').forEach(chart => {
                        chart.style.display = 'block';
                    });
                }
            }, 500);
        }
        
        function generarPDF() {
            showLoading('Generando PDF...');
            
            // Simular generación de PDF
            setTimeout(() => {
                hideLoading();
                showSuccess('PDF generado exitosamente');
                
                // En una implementación real, aquí se descargaría el PDF
                window.open('exportar-pdf.php', '_blank');
            }, 2000);
        }
        
        function procesarExportacion() {
            const fechaDesde = document.getElementById('exportFechaDesde').value;
            const fechaHasta = document.getElementById('exportFechaHasta').value;
            const incluirGraficos = document.getElementById('incluirGraficos').checked;
            const nivelDetalle = document.getElementById('nivelDetalle').value;
            
            showLoading(`Generando exportación en formato ${selectedFormat.toUpperCase()}...`);
            
            // Simular procesamiento
            setTimeout(() => {
                hideLoading();
                showSuccess(`Exportación ${selectedFormat.toUpperCase()} completada`);
                
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
                
                // En una implementación real, redirigir a la descarga
                let url = '';
                switch(selectedFormat) {
                    case 'excel':
                        url = 'exportar-excel.php?detalle=' + nivelDetalle;
                        break;
                    case 'csv':
                        url = 'exportar-csv.php';
                        break;
                    case 'pdf':
                        url = 'exportar-pdf.php';
                        break;
                    case 'json':
                        url = 'exportar-json.php';
                        break;
                }
                
                // Agregar parámetros adicionales
                if (fechaDesde) url += '&fecha_desde=' + fechaDesde;
                if (fechaHasta) url += '&fecha_hasta=' + fechaHasta;
                if (!incluirGraficos) url += '&sin_graficos=1';
                
                window.open(url, '_blank');
            }, 3000);
        }
        
        function showLoading(message) {
            const loadingHTML = `
                <div class="loading-overlay">
                    <div class="text-center">
                        <div class="spinner-border text-light mb-3" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <div class="text-light">${message}</div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', loadingHTML);
        }
        
        function hideLoading() {
            const loadingOverlay = document.querySelector('.loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.remove();
            }
        }
        
        function showSuccess(message) {
            // Crear toast de éxito
            const toastHTML = `
                <div class="toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', toastHTML);
            const toast = new bootstrap.Toast(document.querySelector('.toast'));
            toast.show();
            
            // Remover después de ocultar
            document.querySelector('.toast').addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }

        // Gráficos
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de distribución por estado
            const ctxEstados = document.getElementById('chartEstados').getContext('2d');
            const chartEstados = new Chart(ctxEstados, {
                type: 'doughnut',
                data: {
                    labels: ['Completados', 'En Proceso', 'Pendientes', 'Cancelados'],
                    datasets: [{
                        data: [
                            <?php echo $estadisticas['completados']; ?>,
                            <?php echo $estadisticas['en_proceso']; ?>,
                            <?php echo $estadisticas['pendientes']; ?>,
                            <?php echo $estadisticas['cancelados']; ?>
                        ],
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(241, 196, 15, 0.8)',
                            'rgba(52, 152, 219, 0.8)',
                            'rgba(231, 76, 60, 0.8)'
                        ],
                        borderColor: [
                            'rgba(46, 204, 113, 1)',
                            'rgba(241, 196, 15, 1)',
                            'rgba(52, 152, 219, 1)',
                            'rgba(231, 76, 60, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Distribución de Requerimientos por Estado'
                        }
                    }
                }
            });

            // Gráfico de tendencia de cumplimiento SLA
            const ctxTendenciaSLA = document.getElementById('chartTendenciaSLA').getContext('2d');
            const chartTendenciaSLA = new Chart(ctxTendenciaSLA, {
                type: 'line',
                data: {
                    labels: [<?php 
                        $labels_tendencia = [];
                        foreach ($tendencia_sla as $mes) {
                            $labels_tendencia[] = "'" . date('M Y', strtotime($mes['mes'] . '-01')) . "'";
                        }
                        echo implode(',', $labels_tendencia);
                    ?>],
                    datasets: [{
                        label: '% Cumplimiento SLA',
                        data: [<?php echo implode(',', array_column($tendencia_sla, 'porcentaje_cumplimiento')); ?>],
                        backgroundColor: 'rgba(111, 66, 193, 0.1)',
                        borderColor: 'rgba(111, 66, 193, 1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Meta (80%)',
                        data: [<?php 
                            $meta_data = [];
                            for ($i = 0; $i < count($tendencia_sla); $i++) {
                                $meta_data[] = '80';
                            }
                            echo implode(',', $meta_data);
                        ?>],
                        borderColor: 'rgba(231, 76, 60, 1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Tendencia de Cumplimiento SLA (Últimos 6 Meses)'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Porcentaje de Cumplimiento'
                            }
                        }
                    }
                }
            });

            // Aplicar filtros
            document.getElementById('formFiltros').addEventListener('submit', function(e) {
                e.preventDefault();
                showLoading('Aplicando filtros...');
                
                // Simular aplicación de filtros
                setTimeout(() => {
                    hideLoading();
                    showSuccess('Filtros aplicados correctamente');
                }, 1500);
            });
        });
    </script>
</body>
</html>