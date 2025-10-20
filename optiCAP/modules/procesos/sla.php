<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener procesos con sus actividades y SLAs (CON TIEMPO_DIAS INCLUIDO)
$query = "SELECT p.id as proceso_id, p.nombre as proceso_nombre, p.tipo as proceso_tipo, p.sla_objetivo as proceso_sla,
                 a.id as actividad_id, a.nombre as actividad_nombre, a.orden, a.sla_objetivo as actividad_sla, a.tiempo_dias
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
            'sla_objetivo' => $row['actividad_sla'],
            'tiempo_dias' => $row['tiempo_dias']
        ];
    }
}

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

// Procesar actualización de SLAs
if ($_POST) {
    try {
        // Validar datos antes de procesar
        $errores_validacion = [];
        
        // Validar SLAs de procesos
        if (isset($_POST['proceso_sla']) && is_array($_POST['proceso_sla'])) {
            foreach ($_POST['proceso_sla'] as $proceso_id => $sla) {
                if ($sla !== '' && (!is_numeric($sla) || $sla < 1 || $sla > 365)) {
                    $errores_validacion[] = "El SLA del proceso ID $proceso_id debe ser un número entre 1 y 365 días";
                }
            }
        }
        
        // Validar SLAs de actividades
        if (isset($_POST['actividad_sla']) && is_array($_POST['actividad_sla'])) {
            foreach ($_POST['actividad_sla'] as $actividad_id => $sla) {
                if ($sla !== '' && (!is_numeric($sla) || $sla < 1 || $sla > 90)) {
                    $errores_validacion[] = "El SLA de la actividad ID $actividad_id debe ser un número entre 1 y 90 días";
                }
            }
        }
        
        // Si hay errores de validación, mostrar mensaje
        if (!empty($errores_validacion)) {
            $error = "Errores de validación:<br>" . implode("<br>", $errores_validacion);
        } else {
            // Iniciar transacción si la validación es exitosa
            $db->beginTransaction();
            
            $actualizaciones_procesos = 0;
            $actualizaciones_actividades = 0;
            
            // Actualizar SLA del proceso si se proporciona
            if (isset($_POST['proceso_sla']) && is_array($_POST['proceso_sla'])) {
                foreach ($_POST['proceso_sla'] as $proceso_id => $sla) {
                    if ($sla !== '') {
                        $query = "UPDATE procesos SET sla_objetivo = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$sla, $proceso_id]);
                        $actualizaciones_procesos += $stmt->rowCount();
                    }
                }
            }
            
            // Actualizar SLAs de actividades
            if (isset($_POST['actividad_sla']) && is_array($_POST['actividad_sla'])) {
                foreach ($_POST['actividad_sla'] as $actividad_id => $sla) {
                    $query = "UPDATE actividades SET sla_objetivo = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$sla ?: null, $actividad_id]);
                    $actualizaciones_actividades += $stmt->rowCount();
                }
            }
            
            $db->commit();
            
            // Verificar que se realizaron actualizaciones
            if ($actualizaciones_procesos > 0 || $actualizaciones_actividades > 0) {
                $mensaje = "SLAs actualizados exitosamente. ";
                $mensaje .= "Procesos actualizados: $actualizaciones_procesos, ";
                $mensaje .= "Actividades actualizadas: $actualizaciones_actividades";
            } else {
                $mensaje = "No se realizaron cambios en los SLAs.";
            }
            
            // Recargar la página para mostrar los cambios
            header("Location: sla.php?mensaje=" . urlencode($mensaje));
            exit();
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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
    <style>
        .required::after {
            content: " *";
            color: #dc3545;
        }
        .sla-input:invalid {
            border-color: #dc3545;
        }
        .breadcrumb {
            background-color: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
        }
        .table tfoot {
            background-color: rgba(0, 123, 255, 0.1);
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/opticap/index.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="procesos.php">Procesos</a></li>
                        <li class="breadcrumb-item active">Configuración de SLA</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configuración de SLA</h1>
                    <a href="procesos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Procesos
                    </a>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Configurar Tiempos Objetivo (SLA)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Configure los tiempos objetivo (SLA) para cada proceso y actividad individual.
                            El SLA representa el tiempo máximo recomendado para completar cada etapa.
                            <strong class="text-danger">Los campos marcados con * son obligatorios.</strong>
                        </div>
                        
                        <form method="POST" id="slaForm" onsubmit="return confirmarGuardado()">
                            <?php if (empty($procesos)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay procesos activos disponibles para configurar SLAs.
                            </div>
                            <?php else: ?>
                                <?php foreach ($procesos as $proceso): ?>
                                <div class="card mb-4 border-primary">
                                    <div class="card-header bg-light">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-project-diagram me-2"></i>
                                                    <?php echo htmlspecialchars($proceso['nombre']); ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($proceso['tipo']); ?></span>
                                                </h6>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <span class="input-group-text required">SLA Proceso (días)</span>
                                                    <input type="number" class="form-control sla-input" 
                                                           name="proceso_sla[<?php echo $proceso['id']; ?>]"
                                                           value="<?php echo htmlspecialchars($proceso['sla_objetivo'] ?? ''); ?>" 
                                                           min="1" max="365" required
                                                           title="SLA del proceso en días (1-365)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($proceso['actividades'])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="5%">Orden</th>
                                                        <th>Actividad</th>
                                                        <th width="15%">Tiempo Est.</th>
                                                        <th width="20%">SLA Objetivo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $tiempo_total_estimado = 0;
                                                    $tiempo_total_sla = 0;
                                                    foreach ($proceso['actividades'] as $actividad): 
                                                        $tiempo_estimado = $actividad['tiempo_dias'] ?? 0;
                                                        $sla_actividad = $actividad['sla_objetivo'] ?? 0;
                                                        $tiempo_total_estimado += $tiempo_estimado;
                                                        $tiempo_total_sla += $sla_actividad;
                                                    ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $actividad['orden']; ?></td>
                                                        <td><?php echo htmlspecialchars($actividad['nombre']); ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-info">
                                                                <?php echo $tiempo_estimado ? $tiempo_estimado . ' días' : 'N/A'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <input type="number" class="form-control sla-input" 
                                                                       name="actividad_sla[<?php echo $actividad['id']; ?>]"
                                                                       value="<?php echo htmlspecialchars($sla_actividad ?: ''); ?>" 
                                                                       min="1" max="90" 
                                                                       placeholder="SLA en días"
                                                                       title="SLA de la actividad en días (1-90)">
                                                                <span class="input-group-text">días</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-info">
                                                        <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                                        <td class="text-center"><strong><?php echo $tiempo_total_estimado; ?> días</strong></td>
                                                        <td class="text-center"><strong><?php echo $tiempo_total_sla; ?> días</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-warning text-center">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No hay actividades configuradas para este proceso.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-3">
                                <button type="submit" class="btn btn-primary me-md-2">
                                    <i class="fas fa-save me-1"></i> Guardar Configuración de SLAs
                                </button>
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-undo me-1"></i> Restablecer
                                </button>
                                <a href="procesos.php" class="btn btn-outline-danger">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Estadísticas de SLAs -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Resumen de SLAs Configurados
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="slaChart" width="400" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <?php
                                // Corregir la consulta de estadísticas para evitar ambigüedad
                                $query_estadisticas = "SELECT 
                                    COUNT(*) as total_actividades,
                                    COUNT(a.sla_objetivo) as actividades_con_sla,
                                    AVG(a.sla_objetivo) as promedio_sla,
                                    COUNT(DISTINCT p.id) as total_procesos,
                                    COUNT(DISTINCT CASE WHEN p.sla_objetivo IS NOT NULL THEN p.id END) as procesos_con_sla
                                    FROM actividades a
                                    INNER JOIN procesos p ON a.proceso_id = p.id
                                    WHERE a.activo = 1 AND p.activo = 1";
                                $stmt_estadisticas = $db->prepare($query_estadisticas);
                                $stmt_estadisticas->execute();
                                $estadisticas = $stmt_estadisticas->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <div class="list-group">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Total de Procesos</span>
                                        <span class="badge bg-primary rounded-pill"><?php echo $estadisticas['total_procesos']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Procesos con SLA configurado</span>
                                        <span class="badge bg-success rounded-pill"><?php echo $estadisticas['procesos_con_sla']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Total de Actividades</span>
                                        <span class="badge bg-primary rounded-pill"><?php echo $estadisticas['total_actividades']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Actividades con SLA configurado</span>
                                        <span class="badge bg-success rounded-pill"><?php echo $estadisticas['actividades_con_sla']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Actividades sin SLA</span>
                                        <span class="badge bg-warning rounded-pill"><?php echo $estadisticas['total_actividades'] - $estadisticas['actividades_con_sla']; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>SLA Promedio Actividades</span>
                                        <span class="badge bg-info rounded-pill"><?php echo round($estadisticas['promedio_sla'] ?? 0, 1); ?> días</span>
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
        // Función para confirmar el guardado
        function confirmarGuardado() {
            return confirm('¿Está seguro de que desea guardar los cambios en los SLAs?');
        }

        // Validación en tiempo real de los inputs
        document.addEventListener('DOMContentLoaded', function() {
            const slaInputs = document.querySelectorAll('.sla-input');
            
            slaInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value !== '' && (this.value < this.min || this.value > this.max)) {
                        this.classList.add('is-invalid');
                        // Mostrar tooltip de error
                        const tooltip = new bootstrap.Tooltip(this, {
                            title: `El valor debe estar entre ${this.min} y ${this.max} días`,
                            trigger: 'manual',
                            placement: 'top'
                        });
                        tooltip.show();
                        setTimeout(() => tooltip.hide(), 3000);
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            });

            // Restablecer validación al hacer reset del formulario
            document.getElementById('slaForm').addEventListener('reset', function() {
                slaInputs.forEach(input => {
                    input.classList.remove('is-invalid');
                });
            });
        });

        // Gráfico de SLAs
        <?php if (isset($estadisticas) && !empty($estadisticas)): ?>
        const ctx = document.getElementById('slaChart').getContext('2d');
        const slaChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Actividades con SLA', 'Actividades sin SLA'],
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
                        text: 'Distribución de Actividades con SLA',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>