<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

if (!isset($_GET['id'])) {
    redirectTo('modules/requerimientos/requerimientos.php');
    exit();
}

$seguimiento_id = $_GET['id'];
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$area_usuario = $_SESSION['area_id'];

$database = new Database();
$db = $database->getConnection();

// Obtener información del seguimiento
$query_seguimiento = "SELECT sa.*, a.nombre as actividad_nombre, a.orden, a.tiempo_dias, 
                             r.id as requerimiento_id, r.codigo, r.area_id,
                             p.nombre as proceso_nombre, p.id as proceso_id,
                             ar.nombre as area_nombre
                      FROM seguimiento_actividades sa 
                      INNER JOIN actividades a ON sa.actividad_id = a.id 
                      INNER JOIN requerimientos r ON sa.requerimiento_id = r.id 
                      INNER JOIN procesos p ON r.proceso_id = p.id 
                      INNER JOIN areas ar ON r.area_id = ar.id
                      WHERE sa.id = ?";
$stmt_seguimiento = $db->prepare($query_seguimiento);
$stmt_seguimiento->execute([$seguimiento_id]);
$seguimiento = $stmt_seguimiento->fetch(PDO::FETCH_ASSOC);

if (!$seguimiento) {
    redirectTo('modules/requerimientos/requerimientos.php');
    exit();
}

// Verificar permisos por área (para usuarios y supervisores)
if (in_array($rol, ['usuario', 'supervisor'])) {
    if ($seguimiento['area_id'] != $area_usuario) {
        redirectTo("modules/requerimientos/detalle.php?id=" . $seguimiento['requerimiento_id']);
        exit();
    }
}

$mensaje = '';
$error = '';

if ($_POST) {
    try {
        $estado = $_POST['estado'];
        $observaciones = $_POST['observaciones'];
        
        $db->beginTransaction();
        
        // Procesar archivo adjunto
        $archivo_nombre = null;
        $archivo_original = null;
        
        if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['evidencia'];
            $archivo_original = $archivo['name'];
            $extension = pathinfo($archivo_original, PATHINFO_EXTENSION);
            $archivo_nombre = uniqid() . '_' . $seguimiento_id . '.' . $extension;
            $directorio_evidencias = '../../uploads/evidencias/';
            
            // Crear directorio si no existe
            if (!is_dir($directorio_evidencias)) {
                mkdir($directorio_evidencias, 0755, true);
            }
            
            $ruta_archivo = $directorio_evidencias . $archivo_nombre;
            
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
                throw new Exception("Error al subir el archivo de evidencia");
            }
        }
        
        // Registrar movimiento antes de la actualización
        $query_movimiento = "INSERT INTO seguimiento_movimientos 
                            (seguimiento_id, usuario_id, estado_anterior, estado_nuevo, observaciones, archivo_adjunto, archivo_original)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_movimiento = $db->prepare($query_movimiento);
        $stmt_movimiento->execute([
            $seguimiento_id,
            $usuario_id,
            $seguimiento['estado'],
            $estado,
            $observaciones,
            $archivo_nombre,
            $archivo_original
        ]);
        
        // Actualizar seguimiento
        $query_update = "UPDATE seguimiento_actividades SET estado = ?, observaciones = ?, usuario_id = ?";
        $params = [$estado, $observaciones, $usuario_id];
        
        if ($estado == 'en_proceso' && !$seguimiento['fecha_inicio']) {
            $query_update .= ", fecha_inicio = NOW()";
        } elseif ($estado == 'completado' && !$seguimiento['fecha_fin']) {
            $query_update .= ", fecha_fin = NOW()";
        }
        
        $query_update .= " WHERE id = ?";
        $params[] = $seguimiento_id;
        
        $stmt_update = $db->prepare($query_update);
        $stmt_update->execute($params);
        
        // Si se completa la actividad, crear siguiente actividad si existe
        if ($estado == 'completado') {
            // Obtener siguiente actividad en el proceso
            $query_siguiente_actividad = "SELECT id FROM actividades 
                                        WHERE proceso_id = ? AND orden > ? 
                                        ORDER BY orden ASC LIMIT 1";
            $stmt_siguiente = $db->prepare($query_siguiente_actividad);
            $stmt_siguiente->execute([$seguimiento['proceso_id'], $seguimiento['orden']]);
            $siguiente_actividad = $stmt_siguiente->fetch(PDO::FETCH_ASSOC);
            
            if ($siguiente_actividad) {
                // Crear seguimiento para la siguiente actividad
                $query_nuevo_seguimiento = "INSERT INTO seguimiento_actividades 
                                           (requerimiento_id, actividad_id, estado, fecha_creacion)
                                           VALUES (?, ?, 'pendiente', NOW())";
                $stmt_nuevo = $db->prepare($query_nuevo_seguimiento);
                $stmt_nuevo->execute([$seguimiento['requerimiento_id'], $siguiente_actividad['id']]);
            }
            
            // Actualizar estado del requerimiento
            $query_estado_requerimiento = "UPDATE requerimientos SET estado = 'en_proceso' WHERE id = ?";
            $stmt_requerimiento = $db->prepare($query_estado_requerimiento);
            $stmt_requerimiento->execute([$seguimiento['requerimiento_id']]);
        }
        
        // Si es la última actividad y se completa, marcar requerimiento como completado
        if ($estado == 'completado') {
            $query_ultima_actividad = "SELECT COUNT(*) as total FROM actividades 
                                     WHERE proceso_id = ? AND orden > ?";
            $stmt_ultima = $db->prepare($query_ultima_actividad);
            $stmt_ultima->execute([$seguimiento['proceso_id'], $seguimiento['orden']]);
            $resultado_ultima = $stmt_ultima->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado_ultima['total'] == 0) {
                $query_completar_requerimiento = "UPDATE requerimientos SET estado = 'completado' WHERE id = ?";
                $stmt_completar = $db->prepare($query_completar_requerimiento);
                $stmt_completar->execute([$seguimiento['requerimiento_id']]);
            }
        }
        
        $db->commit();
        
        $mensaje = "Actividad actualizada exitosamente";
        redirectTo("modules/requerimientos/detalle.php?id=" . $seguimiento['requerimiento_id'] . "&mensaje=" . urlencode($mensaje));
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al actualizar la actividad: " . $e->getMessage();
    }
}

// Obtener historial de movimientos
$query_movimientos = "SELECT sm.*, u.nombre as usuario_nombre 
                     FROM seguimiento_movimientos sm
                     INNER JOIN usuarios u ON sm.usuario_id = u.id
                     WHERE sm.seguimiento_id = ?
                     ORDER BY sm.fecha_creacion DESC";
$stmt_movimientos = $db->prepare($query_movimientos);
$stmt_movimientos->execute([$seguimiento_id]);
$movimientos = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Actividad - OptiCAP</title>
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
                    <h1 class="h2">Seguimiento de Actividad</h1>
                    <a href="detalle.php?id=<?php echo $seguimiento['requerimiento_id']; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actualizar Actividad</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Actividad</label>
                                        <p class="form-control-plaintext fw-bold"><?php echo $seguimiento['actividad_nombre']; ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Requerimiento</label>
                                        <p class="form-control-plaintext"><?php echo $seguimiento['codigo']; ?> - <?php echo $seguimiento['proceso_nombre']; ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Área Responsable</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge bg-info"><?php echo $seguimiento['area_nombre']; ?></span>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Estado Actual</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge bg-<?php 
                                                switch($seguimiento['estado']) {
                                                    case 'pendiente': echo 'secondary'; break;
                                                    case 'en_proceso': echo 'warning'; break;
                                                    case 'completado': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo ucfirst($seguimiento['estado']); ?></span>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Nuevo Estado *</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="">Seleccionar estado...</option>
                                            <?php if ($seguimiento['estado'] == 'pendiente'): ?>
                                            <option value="en_proceso">En Proceso</option>
                                            <?php elseif ($seguimiento['estado'] == 'en_proceso'): ?>
                                            <option value="completado">Completado</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label">Observaciones *</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4" 
                                                  placeholder="Describa los detalles de la ejecución de esta actividad..." required><?php echo htmlspecialchars($seguimiento['observaciones']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="evidencia" class="form-label">Evidencia (Opcional)</label>
                                        <input type="file" class="form-control" id="evidencia" name="evidencia" 
                                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                                        <div class="form-text">
                                            Formatos permitidos: PDF, Word, Excel, JPG, PNG. Tamaño máximo: 10MB
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Actualizar Actividad
                                        </button>
                                        <a href="detalle.php?id=<?php echo $seguimiento['requerimiento_id']; ?>" class="btn btn-outline-secondary">Cancelar</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Historial de Movimientos -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Historial de Cambios</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($movimientos): ?>
                                    <div class="timeline">
                                        <?php foreach ($movimientos as $movimiento): ?>
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex">
                                                <div class="timeline-badge bg-<?php 
                                                    switch($movimiento['estado_nuevo']) {
                                                        case 'pendiente': echo 'secondary'; break;
                                                        case 'en_proceso': echo 'warning'; break;
                                                        case 'completado': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?> me-3"></div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <strong><?php echo $movimiento['usuario_nombre']; ?></strong>
                                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_creacion'])); ?></small>
                                                    </div>
                                                    <div class="mb-1">
                                                        <span class="badge bg-secondary"><?php echo ucfirst($movimiento['estado_anterior']); ?></span>
                                                        <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                                        <span class="badge bg-<?php 
                                                            switch($movimiento['estado_nuevo']) {
                                                                case 'pendiente': echo 'secondary'; break;
                                                                case 'en_proceso': echo 'warning'; break;
                                                                case 'completado': echo 'success'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>"><?php echo ucfirst($movimiento['estado_nuevo']); ?></span>
                                                    </div>
                                                    <?php if ($movimiento['observaciones']): ?>
                                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($movimiento['observaciones'])); ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($movimiento['archivo_adjunto']): ?>
                                                    <div>
                                                        <a href="/opticap/uploads/evidencias/<?php echo $movimiento['archivo_adjunto']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="fas fa-download me-1"></i> Descargar Evidencia
                                                        </a>
                                                        <small class="text-muted">(<?php echo $movimiento['archivo_original']; ?>)</small>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No hay historial de cambios para esta actividad.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Información de la Actividad</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Orden en el Proceso:</strong><br>
                                    <?php echo $seguimiento['orden']; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Tiempo Estimado:</strong><br>
                                    <?php echo $seguimiento['tiempo_dias']; ?> días
                                </div>
                                
                                <?php if ($seguimiento['fecha_inicio']): ?>
                                <div class="mb-3">
                                    <strong>Fecha de Inicio:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($seguimiento['fecha_inicio'])); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($seguimiento['fecha_fin']): ?>
                                <div class="mb-3">
                                    <strong>Fecha de Finalización:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($seguimiento['fecha_fin'])); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($seguimiento['usuario_id']): ?>
                                <div class="mb-3">
                                    <strong>Último Responsable:</strong><br>
                                    <?php 
                                    $query_usuario = "SELECT nombre FROM usuarios WHERE id = ?";
                                    $stmt_usuario = $db->prepare($query_usuario);
                                    $stmt_usuario->execute([$seguimiento['usuario_id']]);
                                    $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
                                    echo $usuario ? $usuario['nombre'] : 'N/A';
                                    ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">Próximos Pasos</h6>
                            </div>
                            <div class="card-body">
                                <p>Al completar esta actividad, la siguiente en la secuencia se habilitará automáticamente para su ejecución.</p>
                                
                                <?php 
                                // Obtener siguiente actividad
                                $query_siguiente = "SELECT a.id, a.nombre 
                                                  FROM actividades a 
                                                  WHERE a.proceso_id = ? 
                                                  AND a.orden > ? 
                                                  ORDER BY a.orden LIMIT 1";
                                $stmt_siguiente = $db->prepare($query_siguiente);
                                $stmt_siguiente->execute([$seguimiento['proceso_id'], $seguimiento['orden']]);
                                $siguiente = $stmt_siguiente->fetch(PDO::FETCH_ASSOC);
                                
                                if ($siguiente): 
                                ?>
                                <div class="alert alert-info">
                                    <strong>Siguiente actividad:</strong><br>
                                    <?php echo $siguiente['nombre']; ?>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-success">
                                    <strong>¡Última actividad!</strong><br>
                                    Al completar esta actividad, el requerimiento finalizará.
                                </div>
                                <?php endif; ?>
                            </div>
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