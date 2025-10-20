<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

// Verificar que el usuario puede crear requerimientos
if (!usuarioPuedeCrearRequerimientos($_SESSION['usuario_id'])) {
    redirectTo('modules/requerimientos/requerimientos.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener procesos activos
$query_procesos = "SELECT * FROM procesos WHERE activo = 1";
$stmt_procesos = $db->prepare($query_procesos);
$stmt_procesos->execute();
$procesos = $stmt_procesos->fetchAll(PDO::FETCH_ASSOC);

// Obtener áreas según el rol del usuario
if ($_SESSION['rol'] == 'super_usuario') {
    // Super usuario puede ver todas las áreas activas
    $query_areas = "SELECT * FROM areas WHERE activo = 1";
    $stmt_areas = $db->prepare($query_areas);
    $stmt_areas->execute();
    $areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Supervisor y usuario solo pueden ver su área asignada
    $query_areas = "SELECT * FROM areas WHERE id = ? AND activo = 1";
    $stmt_areas = $db->prepare($query_areas);
    $stmt_areas->execute([$_SESSION['area_id']]);
    $areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);
}

$mensaje = '';
$error = '';

if ($_POST) {
    try {
        $proceso_id = $_POST['proceso_id'];
        $area_id = $_POST['area_id'];
        $observaciones = $_POST['observaciones'];
        
        // Validar que el usuario tiene permiso para crear en el área seleccionada
        if ($_SESSION['rol'] != 'super_usuario' && $area_id != $_SESSION['area_id']) {
            throw new Exception("No tiene permisos para crear requerimientos en esta área.");
        }
        
        // Obtener información del proceso
        $query_proceso = "SELECT tipo FROM procesos WHERE id = ?";
        $stmt_proceso = $db->prepare($query_proceso);
        $stmt_proceso->execute([$proceso_id]);
        $proceso = $stmt_proceso->fetch(PDO::FETCH_ASSOC);
        
        if (!$proceso) {
            throw new Exception("Proceso no válido.");
        }
        
        // Generar código único
        $codigo = generarCodigoRequerimiento($proceso['tipo']);
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Crear requerimiento
        $query_requerimiento = "INSERT INTO requerimientos (codigo, proceso_id, area_id, usuario_solicitante_id, observaciones) VALUES (?, ?, ?, ?, ?)";
        $stmt_requerimiento = $db->prepare($query_requerimiento);
        $stmt_requerimiento->execute([$codigo, $proceso_id, $area_id, $_SESSION['usuario_id'], $observaciones]);
        $requerimiento_id = $db->lastInsertId();
        
        // Obtener actividades del proceso
        $query_actividades = "SELECT * FROM actividades WHERE proceso_id = ? ORDER BY orden";
        $stmt_actividades = $db->prepare($query_actividades);
        $stmt_actividades->execute([$proceso_id]);
        $actividades = $stmt_actividades->fetchAll(PDO::FETCH_ASSOC);
        
        // Crear seguimiento para cada actividad
        foreach ($actividades as $actividad) {
            $estado = $actividad['orden'] == 1 ? 'pendiente' : 'pendiente';
            
            $query_seguimiento = "INSERT INTO seguimiento_actividades (requerimiento_id, actividad_id, estado) VALUES (?, ?, ?)";
            $stmt_seguimiento = $db->prepare($query_seguimiento);
            $stmt_seguimiento->execute([$requerimiento_id, $actividad['id'], $estado]);
        }
        
        // Confirmar transacción
        $db->commit();
        // TEST: Verificar todo el proceso de notificación 
        testNotificacionCompleta($db, $requerimiento_id, $codigo);

        // ENVIAR NOTIFICACIÓN POR EMAIL
        try {
            // Obtener información completa del requerimiento para la notificación
            $query_info = "SELECT r.codigo, p.tipo, a.nombre as area_nombre, u.nombre as usuario_nombre, u.email as usuario_email
                           FROM requerimientos r 
                           JOIN procesos p ON r.proceso_id = p.id 
                           JOIN areas a ON r.area_id = a.id 
                           JOIN usuarios u ON r.usuario_solicitante_id = u.id 
                           WHERE r.id = ?";
            $stmt_info = $db->prepare($query_info);
            $stmt_info->execute([$requerimiento_id]);
            $requerimiento_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            
            if ($requerimiento_info) {
                $datos_notificacion = [
                    'codigo' => $requerimiento_info['codigo'],
                    'tipo' => $requerimiento_info['tipo'],
                    'area' => $requerimiento_info['area_nombre'],
                    'usuario' => $requerimiento_info['usuario_nombre'],
                    'fecha' => date('d/m/Y H:i')
                ];
                
                // Enviar notificación
                enviarNotificacionEmail('nuevo_requerimiento', $datos_notificacion);
                
                // También notificar al usuario que creó el requerimiento
                $datos_usuario = [
                    'codigo' => $requerimiento_info['codigo'],
                    'tipo' => $requerimiento_info['tipo'],
                    'area' => $requerimiento_info['area_nombre'],
                    'fecha' => date('d/m/Y H:i')
                ];
                
                enviarNotificacionEmail('nuevo_requerimiento', $datos_usuario, [$requerimiento_info['usuario_email']]);
            }
        } catch (Exception $e) {
            // No interrumpir el flujo si falla la notificación
            error_log("Error en notificación: " . $e->getMessage());
        }
        
        $mensaje = "Requerimiento creado exitosamente: {$codigo}";
        
        // Redirigir al detalle del requerimiento
        redirectTo("modules/requerimientos/detalle.php?id={$requerimiento_id}&mensaje=" . urlencode($mensaje));
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al crear el requerimiento: " . $e->getMessage();
    }
}
/**
 * Función de testing para notificaciones
 */
function testNotificacionCompleta($db, $requerimiento_id, $codigo) {
    error_log("=== TEST NOTIFICACIÓN COMPLETO ===");
    
    // 1. Verificar configuración SMTP
    $query_config = "SELECT * FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute();
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    
    error_log("1. CONFIGURACIÓN SMTP:");
    error_log("   - Email activo: " . ($config['email_activo'] ?? '0'));
    error_log("   - SMTP Host: " . ($config['smtp_host'] ?? 'No configurado'));
    error_log("   - SMTP User: " . ($config['smtp_user'] ?? 'No configurado'));
    error_log("   - SMTP Port: " . ($config['smtp_port'] ?? 'No configurado'));
    
    // 2. Verificar evento
    $query_evento = "SELECT * FROM eventos_notificacion WHERE nombre = 'nuevo_requerimiento'";
    $stmt_evento = $db->prepare($query_evento);
    $stmt_evento->execute();
    $evento = $stmt_evento->fetch(PDO::FETCH_ASSOC);
    error_log("2. EVENTO:");
    error_log("   - Encontrado: " . ($evento ? 'SÍ' : 'NO'));
    error_log("   - Activo: " . ($evento['activo'] ?? 'No'));
    
    // 3. Verificar plantilla
    $query_plantilla = "SELECT * FROM plantillas_email WHERE tipo = 'nuevo_requerimiento'";
    $stmt_plantilla = $db->prepare($query_plantilla);
    $stmt_plantilla->execute();
    $plantilla = $stmt_plantilla->fetch(PDO::FETCH_ASSOC);
    error_log("3. PLANTILLA:");
    error_log("   - Encontrada: " . ($plantilla ? 'SÍ' : 'NO'));
    error_log("   - Activa: " . ($plantilla['activa'] ?? 'No'));
    error_log("   - Asunto: " . ($plantilla['asunto'] ?? 'No tiene'));
    
    // 4. Verificar destinatarios
    $query_dest = "SELECT email FROM usuarios WHERE rol IN ('administrador', 'super_usuario') AND activo = 1 AND email IS NOT NULL";
    $stmt_dest = $db->prepare($query_dest);
    $stmt_dest->execute();
    $destinatarios = $stmt_dest->fetchAll(PDO::FETCH_COLUMN);
    error_log("4. DESTINATARIOS:");
    error_log("   - Total: " . count($destinatarios));
    foreach ($destinatarios as $dest) {
        error_log("   - " . $dest);
    }
    
    // 5. Verificar información del requerimiento
    $query_info = "SELECT r.codigo, p.tipo, a.nombre as area_nombre, u.nombre as usuario_nombre 
                   FROM requerimientos r 
                   JOIN procesos p ON r.proceso_id = p.id 
                   JOIN areas a ON r.area_id = a.id 
                   JOIN usuarios u ON r.usuario_solicitante_id = u.id 
                   WHERE r.id = ?";
    $stmt_info = $db->prepare($query_info);
    $stmt_info->execute([$requerimiento_id]);
    $requerimiento_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    error_log("5. REQUERIMIENTO:");
    error_log("   - Código: " . ($requerimiento_info['codigo'] ?? 'No encontrado'));
    error_log("   - Tipo: " . ($requerimiento_info['tipo'] ?? 'No encontrado'));
    error_log("   - Área: " . ($requerimiento_info['area_nombre'] ?? 'No encontrado'));
    
    error_log("=== FIN TEST ===");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Requerimiento - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link href="/opticap/assets/css/fontawesome/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Nuevo Requerimiento</h1>
                    <a href="requerimientos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="proceso_id" class="form-label">Tipo de Proceso *</label>
                                        <select class="form-select" id="proceso_id" name="proceso_id" required>
                                            <option value="">Seleccionar proceso...</option>
                                            <?php foreach ($procesos as $proceso): ?>
                                            <option value="<?php echo $proceso['id']; ?>">
                                                <?php echo $proceso['nombre']; ?> (<?php echo $proceso['tipo']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="area_id" class="form-label">Área Solicitante *</label>
                                        <?php if ($_SESSION['rol'] == 'super_usuario'): ?>
                                            <!-- Super usuario puede seleccionar cualquier área -->
                                            <select class="form-select" id="area_id" name="area_id" required>
                                                <option value="">Seleccionar área...</option>
                                                <?php foreach ($areas as $area): ?>
                                                <option value="<?php echo $area['id']; ?>">
                                                    <?php echo $area['nombre']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Puede crear requerimientos para cualquier área.</div>
                                        <?php else: ?>
                                            <!-- Supervisor y Usuario: solo su área, mostrada como texto -->
                                            <?php if (!empty($areas)): ?>
                                                <input type="text" class="form-control" value="<?php echo $areas[0]['nombre']; ?>" readonly>
                                                <input type="hidden" name="area_id" value="<?php echo $areas[0]['id']; ?>">
                                                <div class="form-text">Solo puede crear requerimientos para su área asignada.</div>
                                            <?php else: ?>
                                                <div class="alert alert-warning">
                                                    No tiene un área asignada. Contacte al administrador.
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Descripción adicional del requerimiento..."></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Información del Proceso</h6>
                                <p class="mb-0" id="info-proceso">Seleccione un proceso para ver los detalles.</p>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Crear Requerimiento
                                </button>
                                <a href="requerimientos.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Cargar información del proceso seleccionado
        document.getElementById('proceso_id').addEventListener('change', function() {
            const procesoId = this.value;
            const infoProceso = document.getElementById('info-proceso');
            
            if (procesoId) {
                // Aquí se podría hacer una petición AJAX para obtener los detalles del proceso
                infoProceso.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando información...';
                
                // Simulación de carga de información
                setTimeout(() => {
                    const procesosInfo = {
                        '1': 'Proceso de adquisición de bienes con 14 actividades. Tiempo estimado: 45 días.',
                        '2': 'Proceso de adquisición de servicios con 14 actividades. Tiempo estimado: 40 días.'
                    };
                    infoProceso.textContent = procesosInfo[procesoId] || 'Información no disponible.';
                }, 500);
            } else {
                infoProceso.textContent = 'Seleccione un proceso para ver los detalles.';
            }
        });
    </script>
</body>
</html>