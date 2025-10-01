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

// Obtener seguimiento de actividades
$query_seguimiento = "SELECT sa.*, a.nombre as actividad_nombre, a.orden, a.tiempo_dias, 
                             u.nombre as usuario_nombre
                      FROM seguimiento_actividades sa 
                      INNER JOIN actividades a ON sa.actividad_id = a.id 
                      LEFT JOIN usuarios u ON sa.usuario_id = u.id 
                      WHERE sa.requerimiento_id = ? 
                      ORDER BY a.orden";
$stmt_seguimiento = $db->prepare($query_seguimiento);
$stmt_seguimiento->execute([$requerimiento_id]);
$seguimientos = $stmt_seguimiento->fetchAll(PDO::FETCH_ASSOC);

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
                    <div class="card-header bg-warning text-white">
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
                                <a href="seguimiento.php?id=<?php echo $actividad['id']; ?>" class="btn btn-primary">
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
                            ?>
                            <div class="timeline-item <?php echo $clase; ?>">
                                <div class="timeline-marker"></div>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
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
                                                <p class="card-text mb-0">
                                                    <strong>Observaciones:</strong> <?php echo nl2br(htmlspecialchars($seguimiento['observaciones'])); ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <?php if (puedeModificarActividad($usuario_id, $seguimiento['actividad_id'], $requerimiento_id)): ?>
                                                    <?php if ($seguimiento['estado'] == 'pendiente'): ?>
                                                    <a href="seguimiento.php?id=<?php echo $seguimiento['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-play me-1"></i> Iniciar
                                                    </a>
                                                    <?php elseif ($seguimiento['estado'] == 'en_proceso'): ?>
                                                    <a href="seguimiento.php?id=<?php echo $seguimiento['id']; ?>" class="btn btn-sm btn-success">
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

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
</body>
</html>