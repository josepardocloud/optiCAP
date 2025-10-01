<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['super_usuario', 'supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener lista de procesos
$query = "SELECT p.*, 
                 (SELECT COUNT(*) FROM actividades a WHERE a.proceso_id = p.id AND a.activo = 1) as total_actividades
          FROM procesos p 
          ORDER BY p.nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Procesos - OptiCAP</title>
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
                    <h1 class="h2">Gestión de Procesos</h1>
                    <?php if ($_SESSION['rol'] == 'administrador'): ?>
                    <div>
                        <a href="actividades.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-tasks me-1"></i> Actividades
                        </a>
                        <a href="sla.php" class="btn btn-outline-info">
                            <i class="fas fa-clock me-1"></i> Configurar SLA
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <?php foreach ($procesos as $proceso): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo $proceso['nombre']; ?></h5>
                                <span class="badge bg-<?php echo $proceso['tipo'] == 'Bien' ? 'info' : 'success'; ?>">
                                    <?php echo $proceso['tipo']; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Tiempo Total:</strong> <?php echo $proceso['tiempo_total_dias']; ?> días<br>
                                    <strong>Actividades:</strong> <?php echo $proceso['total_actividades']; ?><br>
                                    <strong>SLA Objetivo:</strong> <?php echo $proceso['sla_objetivo']; ?> días
                                </div>
                                
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar" role="progressbar" 
                                        style="width: <?php echo min(100, ($proceso['tiempo_total_dias'] / 60) * 100); ?>%; font-size: 14px;"
                                        aria-valuenow="<?php echo $proceso['tiempo_total_dias']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="60">
                                        <strong><?php echo $proceso['tiempo_total_dias']; ?> días</strong>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Estado:</strong> 
                                    <span class="badge bg-<?php echo $proceso['activo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $proceso['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">
                                    Última actualización: <?php echo date('d/m/Y', strtotime($proceso['fecha_creacion'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Estadísticas de Procesos -->
                <?php if ($_SESSION['rol'] == 'supervisor' || $_SESSION['rol'] == 'administrador'): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Estadísticas de Procesos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="procesosChart" width="400" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <h6>Resumen por Tipo</h6>
                                <?php
                                $query_estadisticas = "SELECT tipo, COUNT(*) as total, 
                                                      SUM(tiempo_total_dias) as total_dias 
                                                      FROM procesos 
                                                      WHERE activo = 1 
                                                      GROUP BY tipo";
                                $stmt_estadisticas = $db->prepare($query_estadisticas);
                                $stmt_estadisticas->execute();
                                $estadisticas = $stmt_estadisticas->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <ul class="list-group">
                                    <?php foreach ($estadisticas as $est): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo $est['tipo'] == 'Bien' ? 'Bienes' : 'Servicios'; ?>
                                        <span class="badge bg-primary rounded-pill"><?php echo $est['total']; ?> procesos</span>
                                        <span class="text-muted"><?php echo $est['total_dias']; ?> días totales</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/chart.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Gráfico de procesos
        const ctx = document.getElementById('procesosChart').getContext('2d');
        const procesosChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Bienes', 'Servicios'],
                datasets: [{
                    data: [
                        <?php echo $estadisticas[0]['total'] ?? 0; ?>,
                        <?php echo $estadisticas[1]['total'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(46, 204, 113, 1)'
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
                        text: 'Distribución de Procesos por Tipo'
                    }
                }
            }
        });
    </script>
</body>
</html>