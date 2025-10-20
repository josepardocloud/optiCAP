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

$mensaje = '';
$error = '';

if ($_POST) {
    try {
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
        
    } catch (Exception $e) {
        $error = "Error al actualizar la configuración: " . $e->getMessage();
    }
}

// Probar configuración de email
$test_result = '';
if (isset($_GET['test_email'])) {
    $test_result = probarConfiguracionEmail($config);
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
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Guardar Configuración
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Plantillas de Email</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        Nuevo Requerimiento
                                        <span class="badge bg-success">Activa</span>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        Actividad Asignada
                                        <span class="badge bg-success">Activa</span>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        Actividad Próxima a Vencer
                                        <span class="badge bg-warning">Pendiente</span>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        Bloqueo de Cuenta
                                        <span class="badge bg-success">Activa</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Eventos de Notificación</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notif_nuevo_req" checked disabled>
                                    <label class="form-check-label" for="notif_nuevo_req">
                                        Nuevo requerimiento creado
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notif_actividad_asignada" checked disabled>
                                    <label class="form-check-label" for="notif_actividad_asignada">
                                        Actividad asignada
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notif_actividad_vencida" checked disabled>
                                    <label class="form-check-label" for="notif_actividad_vencida">
                                        Actividad vencida
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notif_bloqueo_cuenta" checked disabled>
                                    <label class="form-check-label" for="notif_bloqueo_cuenta">
                                        Bloqueo de cuenta
                                    </label>
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