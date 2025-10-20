<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

if (!isset($_GET['id'])) {
    redirectTo('modules/requerimientos/requerimientos.php');
    exit();
}

$requerimiento_id = $_GET['id'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar permisos de visualización
if (!puedeVerRequerimiento($usuario_id, $requerimiento_id)) {
    redirectTo('modules/requerimientos/requerimientos.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener información del requerimiento
$query_requerimiento = "SELECT r.*, a.nombre as area_nombre, p.nombre as proceso_nombre, p.tipo as proceso_tipo, 
                               u.nombre as usuario_solicitante
                        FROM requerimientos r 
                        INNER JOIN areas a ON r.area_id = a.id 
                        INNER JOIN procesos p ON r.proceso_id = p.id 
                        INNER JOIN usuarios u ON r.usuario_solicitante_id = u.id 
                        WHERE r.id = ?";
$stmt_requerimiento = $db->prepare($query_requerimiento);
$stmt_requerimiento->execute([$requerimiento_id]);
$requerimiento = $stmt_requerimiento->fetch(PDO::FETCH_ASSOC);

if (!$requerimiento) {
    redirectTo('modules/requerimientos/requerimientos.php');
    exit();
}

// Obtener seguimiento de actividades - EVITANDO DUPLICADOS
$query_seguimiento = "SELECT 
    a.id as actividad_id,
    a.nombre as actividad_nombre, 
    a.orden, 
    a.tiempo_dias,
    sa.estado,
    sa.observaciones,
    sa.fecha_inicio,
    sa.fecha_fin,
    sa.usuario_id,
    u.nombre as usuario_nombre,
    sa.id as seguimiento_id
FROM actividades a
LEFT JOIN seguimiento_actividades sa ON (
    sa.actividad_id = a.id 
    AND sa.requerimiento_id = ?
    AND sa.id = (
        SELECT id FROM seguimiento_actividades 
        WHERE actividad_id = a.id AND requerimiento_id = ?
        ORDER BY fecha_creacion DESC 
        LIMIT 1
    )
)
LEFT JOIN usuarios u ON sa.usuario_id = u.id
WHERE a.proceso_id = (SELECT proceso_id FROM requerimientos WHERE id = ?)
ORDER BY a.orden ASC";

$stmt_seguimiento = $db->prepare($query_seguimiento);
$stmt_seguimiento->execute([$requerimiento_id, $requerimiento_id, $requerimiento_id]);
$seguimientos = $stmt_seguimiento->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los movimientos para todas las actividades y calcular contadores
$movimientos_por_actividad = [];
$contadores_actividades = [];

foreach ($seguimientos as $seguimiento) {
    if ($seguimiento['seguimiento_id']) {
        $query_movimientos = "SELECT sm.*, u.nombre as usuario_nombre 
                             FROM seguimiento_movimientos sm
                             INNER JOIN usuarios u ON sm.usuario_id = u.id
                             WHERE sm.seguimiento_id = ?
                             ORDER BY sm.fecha_creacion DESC";
        $stmt_movimientos = $db->prepare($query_movimientos);
        $stmt_movimientos->execute([$seguimiento['seguimiento_id']]);
        $movimientos = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $movimientos = [];
    }
    
    $movimientos_por_actividad[$seguimiento['actividad_id']] = $movimientos;
    
    // Calcular contadores para esta actividad
    $total_evidencias = 0;
    $total_movimientos = count($movimientos);
    
    foreach ($movimientos as $movimiento) {
        if ($movimiento['archivo_adjunto']) {
            $total_evidencias++;
        }
    }
    
    // Guardar contadores en un array separado
    $contadores_actividades[$seguimiento['actividad_id']] = [
        'total_movimientos' => $total_movimientos,
        'total_evidencias' => $total_evidencias
    ];
}

// Obtener actividades pendientes para el usuario actual
$actividades_pendientes = [];
foreach ($seguimientos as $seguimiento) {
    if ($seguimiento['estado'] == 'pendiente' && puedeModificarActividad($usuario_id, $seguimiento['actividad_id'], $requerimiento_id)) {
        $actividades_pendientes[] = $seguimiento;
    }
}

$mensaje = $_GET['mensaje'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Requerimiento - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalle de Requerimiento</h1>
                    <div>
                        <a href="imprimir.php?id=<?php echo $requerimiento_id; ?>" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-print me-1"></i> Imprimir
                        </a>
                        <a href="requerimientos.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>

                <!-- Información del Requerimiento -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Información General</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Código:</strong><br>
                                <span class="h5 text-primary"><?php echo $requerimiento['codigo']; ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Tipo:</strong><br>
                                <span class="badge bg-<?php echo $requerimiento['proceso_tipo'] == 'Bien' ? 'info' : 'success'; ?>">
                                    <?php echo $requerimiento['proceso_tipo']; ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <strong>Proceso:</strong><br>
                                <?php echo $requerimiento['proceso_nombre']; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Estado:</strong><br>
                                <span class="badge bg-<?php 
                                    switch($requerimiento['estado']) {
                                        case 'pendiente': echo 'warning'; break;
                                        case 'en_proceso': echo 'info'; break;
                                        case 'completado': echo 'success'; break;
                                        case 'cancelado': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>"><?php echo ucfirst(str_replace('_', ' ', $requerimiento['estado'])); ?></span>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Área Solicitante:</strong><br>
                                <?php echo $requerimiento['area_nombre']; ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Usuario Solicitante:</strong><br>
                                <?php echo $requerimiento['usuario_solicitante']; ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Fecha de Creación:</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($requerimiento['fecha_creacion'])); ?>
                            </div>
                        </div>
                        <?php if ($requerimiento['observaciones']): ?>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <strong>Observaciones:</strong><br>
                                <?php echo nl2br(htmlspecialchars($requerimiento['observaciones'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actividades Pendientes para el Usuario -->
                <?php if (!empty($actividades_pendientes)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actividades Pendientes para Usted</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($actividades_pendientes as $actividad): ?>
                        <div class="alert alert-warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="alert-heading"><?php echo $actividad['actividad_nombre']; ?></h6>
                                    <p class="mb-0">Orden: <?php echo $actividad['orden']; ?> | Tiempo estimado: <?php echo $actividad['tiempo_dias']; ?> días</p>
                                </div>
                                <a href="seguimiento.php?id=<?php echo $actividad['seguimiento_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Atender Actividad
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Línea de Tiempo de Actividades -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Línea de Tiempo del Proceso</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($seguimientos as $seguimiento): 
                                $clase = '';
                                if ($seguimiento['estado'] == 'completado') {
                                    $clase = 'completed';
                                } elseif ($seguimiento['estado'] == 'en_proceso') {
                                    $clase = 'current';
                                }
                                
                                // Obtener contadores para esta actividad
                                $total_movimientos = $contadores_actividades[$seguimiento['actividad_id']]['total_movimientos'] ?? 0;
                                $total_evidencias = $contadores_actividades[$seguimiento['actividad_id']]['total_evidencias'] ?? 0;
                            ?>
                            <div class="timeline-item <?php echo $clase; ?>">
                                <div class="timeline-marker"></div>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title"><?php echo $seguimiento['actividad_nombre']; ?></h6>
                                                <p class="card-text text-muted mb-1">
                                                    Orden: <?php echo $seguimiento['orden']; ?> | 
                                                    Tiempo: <?php echo $seguimiento['tiempo_dias']; ?> días
                                                </p>
                                                <p class="card-text mb-1">
                                                    <strong>Estado:</strong> 
                                                    <span class="badge bg-<?php 
                                                        switch($seguimiento['estado']) {
                                                            case 'pendiente': echo 'secondary'; break;
                                                            case 'en_proceso': echo 'warning'; break;
                                                            case 'completado': echo 'success'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>"><?php echo ucfirst($seguimiento['estado']); ?></span>
                                                </p>
                                                <?php if ($seguimiento['fecha_inicio']): ?>
                                                <p class="card-text mb-1">
                                                    <strong>Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($seguimiento['fecha_inicio'])); ?>
                                                </p>
                                                <?php endif; ?>
                                                <?php if ($seguimiento['fecha_fin']): ?>
                                                <p class="card-text mb-1">
                                                    <strong>Fin:</strong> <?php echo date('d/m/Y H:i', strtotime($seguimiento['fecha_fin'])); ?>
                                                </p>
                                                <?php endif; ?>
                                                <?php if ($seguimiento['usuario_nombre']): ?>
                                                <p class="card-text mb-1">
                                                    <strong>Responsable:</strong> <?php echo $seguimiento['usuario_nombre']; ?>
                                                </p>
                                                <?php endif; ?>
                                                <?php if ($seguimiento['observaciones']): ?>
                                                <p class="card-text mb-2">
                                                    <strong>Observaciones:</strong> <?php echo nl2br(htmlspecialchars($seguimiento['observaciones'])); ?>
                                                </p>
                                                <?php endif; ?>

                                                <!-- Botón para ver historial en modal -->
                                                <?php if ($total_movimientos > 0): ?>
                                                <div class="mt-2">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalHistorial"
                                                            data-actividad-id="<?php echo $seguimiento['actividad_id']; ?>"
                                                            data-actividad-nombre="<?php echo htmlspecialchars($seguimiento['actividad_nombre']); ?>">
                                                        <i class="fas fa-history me-1"></i> 
                                                        Ver Historial 
                                                        <span class="badge bg-secondary ms-1"><?php echo $total_movimientos; ?></span>
                                                        <?php if ($total_evidencias > 0): ?>
                                                        <span class="badge bg-primary ms-1"><?php echo $total_evidencias; ?> evidencias</span>
                                                        <?php endif; ?>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end ms-3">
                                                <?php if (puedeModificarActividad($usuario_id, $seguimiento['actividad_id'], $requerimiento_id)): ?>
                                                    <?php if ($seguimiento['estado'] == 'pendiente'): ?>
                                                    <a href="seguimiento.php?id=<?php echo $seguimiento['seguimiento_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-play me-1"></i> Iniciar
                                                    </a>
                                                    <?php elseif ($seguimiento['estado'] == 'en_proceso'): ?>
                                                    <a href="seguimiento.php?id=<?php echo $seguimiento['seguimiento_id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check me-1"></i> Completar
                                                    </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($seguimiento['estado'] == 'pendiente'): ?>
                                                    <span class="badge bg-secondary">Esperando secuencia</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Historial de Movimientos -->
    <div class="modal fade" id="modalHistorial" tabindex="-1" aria-labelledby="modalHistorialLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalHistorialLabel">Historial de Actividad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="modalActividadNombre" class="text-primary mb-3"></h6>
                    <div id="modalHistorialContent">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    <script>
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Datos de movimientos para usar en el modal
        const movimientosData = <?php echo json_encode($movimientos_por_actividad); ?>;

        // Manejar el modal de historial
        const modalHistorial = document.getElementById('modalHistorial');
        modalHistorial.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const actividadId = button.getAttribute('data-actividad-id');
            const actividadNombre = button.getAttribute('data-actividad-nombre');
            
            // Actualizar título del modal
            const modalTitle = modalHistorial.querySelector('.modal-title');
            modalTitle.textContent = 'Historial de Actividad';
            
            // Actualizar nombre de la actividad
            document.getElementById('modalActividadNombre').textContent = actividadNombre;
            
            // Cargar contenido del historial
            cargarHistorialActividad(actividadId);
        });

        function cargarHistorialActividad(actividadId) {
            const contenido = document.getElementById('modalHistorialContent');
            const movimientos = movimientosData[actividadId];
            
            if (!movimientos || movimientos.length === 0) {
                contenido.innerHTML = '<p class="text-muted">No hay historial de cambios para esta actividad.</p>';
                return;
            }
            
            let html = '';
            movimientos.forEach(movimiento => {
                const estadoColor = getEstadoColor(movimiento.estado_nuevo);
                const fecha = new Date(movimiento.fecha_creacion).toLocaleString('es-ES');
                
                html += `
                <div class="card mb-3 border-start border-3 border-${estadoColor}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong class="h6">${movimiento.usuario_nombre}</strong>
                                <small class="text-muted ms-2">${fecha}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary">${capitalizeFirst(movimiento.estado_anterior)}</span>
                                <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                <span class="badge bg-${estadoColor}">${capitalizeFirst(movimiento.estado_nuevo)}</span>
                            </div>
                        </div>
                        
                        ${movimiento.observaciones ? `
                        <div class="mb-3">
                            <strong>Observaciones:</strong>
                            <p class="mb-0 mt-1">${movimiento.observaciones.replace(/\n/g, '<br>')}</p>
                        </div>
                        ` : ''}
                        
                        ${movimiento.archivo_adjunto ? `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Evidencia adjunta:</strong>
                                <p class="mb-0 text-muted">${movimiento.archivo_original}</p>
                            </div>
                            <div>
                                <a href="/opticap/uploads/evidencias/${movimiento.archivo_adjunto}" 
                                   class="btn btn-sm btn-primary"
                                   download="${movimiento.archivo_original}">
                                    <i class="fas fa-download me-1"></i> Descargar
                                </a>
                                <a href="/opticap/uploads/evidencias/${movimiento.archivo_adjunto}" 
                                   class="btn btn-sm btn-outline-primary" 
                                   target="_blank"
                                   title="Abrir en nueva pestaña">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                `;
            });
            
            contenido.innerHTML = html;
        }

        function getEstadoColor(estado) {
            switch(estado) {
                case 'pendiente': return 'secondary';
                case 'en_proceso': return 'warning';
                case 'completado': return 'success';
                default: return 'secondary';
            }
        }

        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    </script>
</body>
</html>