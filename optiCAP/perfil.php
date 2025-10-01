<?php
require_once 'config/session.php';
require_once 'includes/funciones.php';
verificarSesion();

$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario actual
$usuario_id = $_SESSION['usuario_id'];

// Consulta corregida - usando el campo 'rol' de la tabla usuarios
$query_usuario = "SELECT u.*, a.nombre as area_nombre 
                 FROM usuarios u 
                 LEFT JOIN areas a ON u.area_id = a.id 
                 WHERE u.id = ?";

$stmt_usuario = $db->prepare($query_usuario);
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

// Si no se pudo obtener el usuario, redirigir
if (!$usuario) {
    header('Location: dashboard.php');
    exit;
}

// Procesar actualización de perfil
$mensaje = '';
$tipo_mensaje = '';

if ($_POST && !isset($_POST['cambiar_password'])) {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    
    // Validar campos obligatorios
    if (empty($nombre) || empty($email)) {
        $mensaje = 'Nombre y email son campos obligatorios';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Actualizar información del usuario
            $query_update = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([$nombre, $email, $telefono, $usuario_id]);
            
            // Actualizar información en sesión
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            
            $mensaje = 'Perfil actualizado correctamente';
            $tipo_mensaje = 'success';
            
            // Recargar datos del usuario
            $stmt_usuario->execute([$usuario_id]);
            $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar el perfil: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Procesar cambio de contraseña
if (isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'] ?? '';
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    if (empty($password_actual) || empty($nueva_password) || empty($confirmar_password)) {
        $mensaje = 'Todos los campos de contraseña son obligatorios';
        $tipo_mensaje = 'error';
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje = 'Las nuevas contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } else {
        // Verificar contraseña actual
        if (password_verify($password_actual, $usuario['password'])) {
            // Actualizar contraseña
            $nueva_password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $query_update_pass = "UPDATE usuarios SET password = ? WHERE id = ?";
            $stmt_update_pass = $db->prepare($query_update_pass);
            $stmt_update_pass->execute([$nueva_password_hash, $usuario_id]);
            
            $mensaje = 'Contraseña actualizada correctamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'La contraseña actual es incorrecta';
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link href="/opticap/assets/css/fontawesome/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mi Perfil</h1>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Información del Usuario -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Información Personal</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-user-circle fa-5x text-secondary"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($usuario['nombre']); ?></h4>
                                <p class="text-muted">
                                    <?php 
                                    // Mostrar el rol con formato más legible
                                    $rol_display = [
                                        'administrador' => 'Administrador',
                                        'supervisor' => 'Supervisor',
                                        'super_usuario' => 'Super Usuario',
                                        'usuario' => 'Usuario'
                                    ];
                                    echo $rol_display[$usuario['rol']] ?? $usuario['rol'];
                                    ?>
                                </p>
                                
                                <div class="text-start mt-4">
                                    <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong><br>
                                    <?php echo htmlspecialchars($usuario['email']); ?></p>
                                    
                                    <p><strong><i class="fas fa-phone me-2"></i>Teléfono:</strong><br>
                                    <?php echo htmlspecialchars($usuario['telefono'] ?? 'No especificado'); ?></p>
                                    
                                    <?php if (isset($usuario['area_nombre'])): ?>
                                    <p><strong><i class="fas fa-building me-2"></i>Área:</strong><br>
                                    <?php echo htmlspecialchars($usuario['area_nombre']); ?></p>
                                    <?php endif; ?>
                                    
                                    <p><strong><i class="fas fa-calendar me-2"></i>Miembro desde:</strong><br>
                                    <?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></p>
                                    
                                    <?php if ($usuario['ultimo_login']): ?>
                                    <p><strong><i class="fas fa-sign-in-alt me-2"></i>Último acceso:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Editar Perfil -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Editar Información</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label">Nombre Completo *</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                                   value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Rol</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo $rol_display[$usuario['rol']] ?? $usuario['rol']; ?>" 
                                                   disabled>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Área</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($usuario['area_nombre'] ?? 'No asignada'); ?>" 
                                                   disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Usuario</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($usuario['username'] ?? 'No disponible'); ?>" 
                                                   disabled>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                                </form>
                            </div>
                        </div>

                        <!-- Cambiar Contraseña -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Cambiar Contraseña</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="password_actual" class="form-label">Contraseña Actual *</label>
                                            <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nueva_password" class="form-label">Nueva Contraseña *</label>
                                            <input type="password" class="form-control" id="nueva_password" name="nueva_password" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirmar_password" class="form-label">Confirmar Contraseña *</label>
                                            <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="cambiar_password" class="btn btn-warning">Cambiar Contraseña</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
</body>
</html>