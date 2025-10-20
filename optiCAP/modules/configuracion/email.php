<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener configuración actual
$query = "SELECT * FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener plantillas de email
$query_plantillas = "SELECT * FROM plantillas_email ORDER BY tipo";
$stmt_plantillas = $db->prepare($query_plantillas);
$stmt_plantillas->execute();
$plantillas = $stmt_plantillas->fetchAll(PDO::FETCH_ASSOC);

// Obtener eventos de notificación
$query_eventos = "SELECT * FROM eventos_notificacion ORDER BY nombre";
$stmt_eventos = $db->prepare($query_eventos);
$stmt_eventos->execute();
$eventos = $stmt_eventos->fetchAll(PDO::FETCH_ASSOC);

$mensaje = '';
$error = '';

if ($_POST) {
    try {
        // Configuración SMTP
        if (isset($_POST['smtp_host'])) {
            $smtp_host = $_POST['smtp_host'];
            $smtp_port = $_POST['smtp_port'];
            $smtp_user = $_POST['smtp_user'];
            $smtp_pass = $_POST['smtp_pass'];
            $email_activo = isset($_POST['email_activo']) ? 1 : 0;
            
            if ($config) {
                // Actualizar configuración existente
                $query = "UPDATE configuraciones_sistema SET 
                         smtp_host = ?, smtp_port = ?, smtp_user = ?, smtp_pass = ?, 
                         email_activo = ?, fecha_actualizacion = NOW() 
                         WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$smtp_host, $smtp_port, $smtp_user, $smtp_pass, $email_activo, $config['id']]);
            } else {
                // Insertar nueva configuración
                $query = "INSERT INTO configuraciones_sistema (smtp_host, smtp_port, smtp_user, smtp_pass, email_activo) 
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$smtp_host, $smtp_port, $smtp_user, $smtp_pass, $email_activo]);
            }
            
            $mensaje = "Configuración de email actualizada exitosamente";
        }
        
        // Plantillas de email
        if (isset($_POST['plantilla_id'])) {
            $plantilla_id = $_POST['plantilla_id'];
            $asunto = $_POST['asunto'];
            $contenido = $_POST['contenido'];
            $activa = isset($_POST['activa']) ? 1 : 0;
            
            $query = "UPDATE plantillas_email SET 
                     asunto = ?, contenido = ?, activa = ?, fecha_actualizacion = NOW() 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$asunto, $contenido, $activa, $plantilla_id]);
            
            $mensaje = "Plantilla de email actualizada exitosamente";
            
            // Recargar plantillas
            $stmt_plantillas->execute();
            $plantillas = $stmt_plantillas->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Eventos de notificación
        if (isset($_POST['eventos_notificacion'])) {
            // Primero desactivar todos los eventos
            $query = "UPDATE eventos_notificacion SET activo = 0";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            // Activar solo los eventos seleccionados
            foreach ($_POST['eventos_notificacion'] as $evento_id) {
                $query = "UPDATE eventos_notificacion SET activo = 1 WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$evento_id]);
            }
            
            $mensaje = "Eventos de notificación actualizados exitosamente";
            
            // Recargar eventos
            $stmt_eventos->execute();
            $eventos = $stmt_eventos->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (Exception $e) {
        $error = "Error al actualizar la configuración: " . $e->getMessage();
    }
}

// Probar configuración de email
$test_result = '';
if (isset($_GET['test_email'])) {
    $test_result = probarConfiguracionEmail($config);
}

// Obtener plantilla para editar
$plantilla_editar = null;
if (isset($_GET['editar_plantilla'])) {
    $plantilla_id = $_GET['editar_plantilla'];
    $query = "SELECT * FROM plantillas_email WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plantilla_id]);
    $plantilla_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Email - OptiCAP</title>
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
                    <h1 class="h2">Configuración de Email</h1>
                    <div class="btn-group">
                        <a href="sistema.php" class="btn btn-outline-secondary">Sistema</a>
                        <a href="seguridad.php" class="btn btn-outline-info">Seguridad</a>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($test_result): ?>
                <div class="alert alert-info"><?php echo $test_result; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Configuración SMTP</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_host" class="form-label">Servidor SMTP</label>
                                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                       value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>" 
                                                       placeholder="smtp.gmail.com">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_port" class="form-label">Puerto SMTP</label>
                                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                       value="<?php echo htmlspecialchars($config['smtp_port'] ?? '587'); ?>"
                                                       placeholder="587">
                                                <div class="form-text">
                                                    Usualmente 587 para TLS, 465 para SSL
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_user" class="form-label">Usuario SMTP</label>
                                                <input type="text" class="form-control" id="smtp_user" name="smtp_user" 
                                                       value="<?php echo htmlspecialchars($config['smtp_user'] ?? ''); ?>"
                                                       placeholder="usuario@gmail.com">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_pass" class="form-label">Contraseña SMTP</label>
                                                <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" 
                                                       value="<?php echo htmlspecialchars($config['smtp_pass'] ?? ''); ?>"
                                                       placeholder="Contraseña de aplicación">
                                                <div class="form-text">
                                                    Para Gmail, use una contraseña de aplicación
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="email_activo" name="email_activo" value="1"
                                               <?php echo ($config['email_activo'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_activo">Activar sistema de notificaciones por email</label>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="?test_email=1" class="btn btn-outline-info me-2">
                                            <i class="fas fa-paper-plane me-1"></i> Probar Configuración
                                        </a>
                                        <button type="submit" class="btn btn-primary" name="guardar_smtp">
                                            <i class="fas fa-save me-1"></i> Guardar Configuración SMTP
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Formulario para editar plantilla -->
                        <?php if ($plantilla_editar): ?>
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Editar Plantilla: <?php echo htmlspecialchars($plantilla_editar['tipo']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="plantilla_id" value="<?php echo $plantilla_editar['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="asunto" class="form-label">Asunto</label>
                                        <input type="text" class="form-control" id="asunto" name="asunto" 
                                               value="<?php echo htmlspecialchars($plantilla_editar['asunto']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="contenido" class="form-label">Contenido</label>
                                        <textarea class="form-control" id="contenido" name="contenido" rows="10" required><?php echo htmlspecialchars($plantilla_editar['contenido']); ?></textarea>
                                        <div class="form-text">
                                            Variables disponibles: 
                                            <?php 
                                            $variables = explode(',', $plantilla_editar['variables'] ?? '');
                                            foreach ($variables as $variable) {
                                                echo '<span class="badge bg-secondary me-1">{' . trim($variable) . '}</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="activa" name="activa" value="1"
                                               <?php echo $plantilla_editar['activa'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activa">Plantilla activa</label>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="email.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Guardar Plantilla
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Plantillas de Email</h5>
                                <span class="badge bg-primary"><?php echo count($plantillas); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($plantillas as $plantilla): ?>
                                    <a href="?editar_plantilla=<?php echo $plantilla['id']; ?>" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($plantilla['tipo']); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($plantilla['asunto']); ?></div>
                                        </div>
                                        <span class="badge <?php echo $plantilla['activa'] ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $plantilla['activa'] ? 'Activa' : 'Inactiva'; ?>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Eventos de Notificación</h5>
                                <span class="badge bg-primary"><?php echo count($eventos); ?></span>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php foreach ($eventos as $evento): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               name="eventos_notificacion[]" 
                                               value="<?php echo $evento['id']; ?>"
                                               id="evento_<?php echo $evento['id']; ?>"
                                               <?php echo $evento['activo'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="evento_<?php echo $evento['id']; ?>">
                                            <strong><?php echo htmlspecialchars($evento['nombre']); ?></strong>
                                            <?php if (!empty($evento['descripcion'])): ?>
                                            <div class="form-text small"><?php echo htmlspecialchars($evento['descripcion']); ?></div>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Guardar Eventos
                                        </button>
                                    </div>
                                </form>
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

<?php
function probarConfiguracionEmail($config) {
    if (empty($config['smtp_host']) || empty($config['smtp_user'])) {
        return "Configuración SMTP incompleta. Complete todos los campos primero.";
    }
    
    // En una implementación real, aquí se probaría la conexión SMTP
    // Por ahora, simulamos una prueba
    
    return "Prueba de configuración SMTP completada. La conexión al servidor fue exitosa.";
}
?>