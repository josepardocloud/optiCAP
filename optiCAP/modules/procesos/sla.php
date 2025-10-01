<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener procesos con sus actividades y SLAs
$query = "SELECT p.id as proceso_id, p.nombre as proceso_nombre, p.tipo as proceso_tipo, p.sla_objetivo as proceso_sla,
                 a.id as actividad_id, a.nombre as actividad_nombre, a.orden, a.sla_objetivo as actividad_sla
          FROM procesos p 
          LEFT JOIN actividades a ON p.id = a.proceso_id AND a.activo = 1 
          WHERE p.activo = 1 
          ORDER BY p.nombre, a.orden";
$stmt = $db->prepare($query);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por proceso
$procesos = [];
foreach ($resultados as $row) {
    $proceso_id = $row['proceso_id'];
    if (!isset($procesos[$proceso_id])) {
        $procesos[$proceso_id] = [
            'id' => $row['proceso_id'],
            'nombre' => $row['proceso_nombre'],
            'tipo' => $row['proceso_tipo'],
            'sla_objetivo' => $row['proceso_sla'],
            'actividades' => []
        ];
    }
    
    if ($row['actividad_id']) {
        $procesos[$proceso_id]['actividades'][] = [
            'id' => $row['actividad_id'],
            'nombre' => $row['actividad_nombre'],
            'orden' => $row['orden'],
            'sla_objetivo' => $row['actividad_sla']
        ];
    }
}

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

// Procesar actualización de SLAs
if ($_POST) {
    try {
        $db->beginTransaction();
        
        // Actualizar SLA del proceso si se proporciona
        if (isset($_POST['proceso_sla']) && is_array($_POST['proceso_sla'])) {
            foreach ($_POST['proceso_sla'] as $proceso_id => $sla) {
                if ($sla > 0) {
                    $query = "UPDATE procesos SET sla_objetivo = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$sla, $proceso_id]);
                }
            }
        }
        
        // Actualizar SLAs de actividades
        if (isset($_POST['actividad_sla']) && is_array($_POST['actividad_sla'])) {
            foreach ($_POST['actividad_sla'] as $actividad_id => $sla) {
                $query = "UPDATE actividades SET sla_objetivo = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$sla ?: null, $actividad_id]);
            }
        }
        
        $db->commit();
        $mensaje = "SLAs actualizados exitosamente";
        
        // Recargar la página para mostrar los cambios
        redirectTo("modules/procesos/sla.php?mensaje=" . urlencode($mensaje));
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al actualizar los SLAs: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de SLA - OptiCAP</title>
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
                    <h1 class="h2">Configuración de SLA</h1>
                    <a href="procesos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Procesos
                    </a>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Configurar Tiempos Objetivo (SLA)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Configure los tiempos objetivo (SLA) para cada proceso y actividad individual.
                            El SLA representa el tiempo máximo recomendado para completar cada etapa.
                        </div>
                        
                        <form method="POST">
                            <?php foreach ($procesos as $proceso): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="card-title mb-0">
                                                <?php echo $proceso['nombre']; ?>
                                                <span class="badge bg-secondary"><?php echo $proceso['tipo']; ?></span>
                                            </h6>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text">SLA Proceso (días)</span>
                                                <input type="number" class="form-control" 
                                                       name="proceso_sla[<?php echo $proceso['id']; ?>]"
                                                       value="<?php echo $proceso['sla_objetivo']; ?>" 
                                                       min="1" max="365">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($proceso['actividades'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th width="5%">Orden</th>
                                                    <th>Actividad</th>
                                                    <th width="20%">Tiempo Est.</th>
                                                    <th width="20%">SLA Objetivo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $tiempo_total = 0;
                                                foreach ($proceso['actividades'] as $actividad): 
                                                    $tiempo_total += $actividad['sla_objetivo'] ?? 0;
                                                ?>
                                                <tr>
                                                    <td><?php echo $actividad['orden']; ?></td>
                                                    <td><?php echo $actividad['nombre']; ?></td>
                                                    <td>
                                                        <?php 
                                                        // Obtener tiempo estimado de la actividad
                                                        $query_tiempo = "SELECT tiempo_dias FROM actividades WHERE id = ?";
                                                        $stmt_tiempo = $db->prepare($query_tiempo);
                                                        $stmt_tiempo->execute([$actividad['id']]);
                                                        $tiempo = $stmt_tiempo->fetch(PDO::FETCH_ASSOC);
                                                        echo $tiempo['tiempo_dias'] ?? 'N/A';
                                                        ?> días
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" class="form-control" 
                                                                   name="actividad_sla[<?php echo $actividad['id']; ?>]"
                                                                   value="<?php echo $actividad['sla_objetivo']; ?>" 
                                                                   min="1" max="90" placeholder="SLA en días">
                                                            <span class="input-group-text">días</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <td colspan="2"><strong>Total</strong></td>
                                                    <td><strong><?php echo array_sum(array_column($proceso['actividades'], 'tiempo_dias')); ?> días</strong></td>
                                                    <td><strong><?php echo $tiempo_total; ?> días</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-warning">
                                        No hay actividades configuradas para este proceso.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Configuración de SLAs
                                </button>
                                <a href="procesos.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Estadísticas de SLAs -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Resumen de SLAs Configurados</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="slaChart" width="400" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $query_estadisticas = "SELECT 
                                    COUNT(*) as total_actividades,
                                    COUNT(sla_objetivo) as actividades_con_sla,
                                    AVG(sla_objetivo) as promedio_sla
                                    FROM actividades 
                                    WHERE activo = 1";
                                $stmt_estadisticas = $db->prepare($query_estadisticas);
                                $stmt_estadisticas->execute();
                                $estadisticas = $stmt_estadisticas->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <div class="list-group">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Total de Actividades
                                        <span class="badge bg-primary rounded-pill"><?php echo $estadisticas['total_actividades']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Actividades con SLA configurado
                                        <span class="badge bg-success rounded-pill"><?php echo $estadisticas['actividades_con_sla']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Actividades sin SLA
                                        <span class="badge bg-warning rounded-pill"><?php echo $estadisticas['total_actividades'] - $estadisticas['actividades_con_sla']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        SLA Promedio
                                        <span class="badge bg-info rounded-pill"><?php echo round($estadisticas['promedio_sla'], 1); ?> días</span>
                                    </div>
                                </div>
                            </div>
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
        // Gráfico de SLAs
        const ctx = document.getElementById('slaChart').getContext('2d');
        const slaChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Con SLA Configurado', 'Sin SLA Configurado'],
                datasets: [{
                    data: [
                        <?php echo $estadisticas['actividades_con_sla']; ?>,
                        <?php echo $estadisticas['total_actividades'] - $estadisticas['actividades_con_sla']; ?>
                    ],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(241, 196, 15, 0.8)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(241, 196, 15, 1)'
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
                        text: 'Distribución de Actividades con SLA'
                    }
                }
            }
        });
    </script>
</body>
</html>