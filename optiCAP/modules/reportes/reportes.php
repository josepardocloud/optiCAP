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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Analytics - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reportes y Analytics</h1>
                    <div class="btn-group">
                        <a href="exportar-pdf.php" class="btn btn-outline-danger" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
                        </a>
                        <a href="exportar-excel.php" class="btn btn-outline-success">
                            <i class="fas fa-file-excel me-1"></i> Exportar Excel
                        </a>
                        <a href="exportar-csv.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-csv me-1"></i> Exportar CSV
                        </a>
                    </div>
                </div>

                <!-- Filtros de Reportes -->
                <div class="card mb-4">
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

                <!-- KPIs Principales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Requerimientos</h5>
                                        <h2><?php echo $estadisticas['total_requerimientos']; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Completados</h5>
                                        <h2><?php echo $estadisticas['completados']; ?></h2>
                                        <small>Tasa: <?php echo $estadisticas['total_requerimientos'] > 0 ? 
                                            round(($estadisticas['completados'] / $estadisticas['total_requerimientos']) * 100, 1) : 0; ?>%</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">En Proceso</h5>
                                        <h2><?php echo $estadisticas['en_proceso']; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-spinner fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Tiempo Promedio</h5>
                                        <h2><?php echo round($estadisticas['tiempo_promedio'], 1); ?> días</h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Distribución por Estado</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartEstados" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Requerimientos por Área</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartAreas" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tablas de Detalles -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Estadísticas por Área</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Área</th>
                                                <th>Total</th>
                                                <th>Completados</th>
                                                <th>Tasa</th>
                                                <th>Tiempo Prom.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($estadisticas_areas as $area): 
                                                $tasa_completacion = $area['total'] > 0 ? 
                                                    round(($area['completados'] / $area['total']) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo $area['nombre']; ?></td>
                                                <td><?php echo $area['total']; ?></td>
                                                <td><?php echo $area['completados']; ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" 
                                                             style="width: <?php echo $tasa_completacion; ?>%"
                                                             title="<?php echo $tasa_completacion; ?>%">
                                                            <?php echo $tasa_completacion; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo round($area['tiempo_promedio'] ?? 0, 1); ?> días</td>
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
                                        <th>Eficiencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas_procesos as $proceso): 
                                        $tasa_completacion = $proceso['total'] > 0 ? 
                                            round(($proceso['completados'] / $proceso['total']) * 100, 1) : 0;
                                        
                                        // Obtener tiempo estimado del proceso
                                        $query_tiempo_estimado = "SELECT tiempo_total_dias FROM procesos WHERE nombre = ?";
                                        $stmt_tiempo_estimado = $db->prepare($query_tiempo_estimado);
                                        $stmt_tiempo_estimado->execute([$proceso['nombre']]);
                                        $tiempo_estimado = $stmt_tiempo_estimado->fetch(PDO::FETCH_ASSOC);
                                        
                                        $eficiencia = $tiempo_estimado && $proceso['tiempo_promedio'] > 0 ? 
                                            round(($tiempo_estimado['tiempo_total_dias'] / $proceso['tiempo_promedio']) * 100, 1) : 0;
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
                                        <td>
                                            <span class="badge bg-<?php echo $eficiencia >= 100 ? 'success' : ($eficiencia >= 80 ? 'warning' : 'danger'); ?>">
                                                <?php echo $eficiencia; ?>%
                                            </span>
                                        </td>
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

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/chart.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
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

        // Gráfico de requerimientos por área
        const ctxAreas = document.getElementById('chartAreas').getContext('2d');
        const chartAreas = new Chart(ctxAreas, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($area) { return "'" . $area['nombre'] . "'"; }, $estadisticas_areas)); ?>],
                datasets: [{
                    label: 'Total de Requerimientos',
                    data: [<?php echo implode(',', array_column($estadisticas_areas, 'total')); ?>],
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Requerimientos por Área'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Aplicar filtros
        document.getElementById('formFiltros').addEventListener('submit', function(e) {
            e.preventDefault();
            // Aquí se implementaría la lógica para aplicar filtros a los reportes
            alert('Los filtros se aplicarían en una implementación completa con AJAX');
        });
    </script>
</body>
</html>