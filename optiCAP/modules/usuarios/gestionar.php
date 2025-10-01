<?php
// PREVENIR CACHÉ - AGREGAR ESTO AL INICIO
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
 

require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

// DIAGNÓSTICO COMPLETO
echo "<!-- DEBUG INICIO -->";
echo "<!-- GET: " . print_r($_GET, true) . " -->";
echo "<!-- SESSION: " . print_r($_SESSION, true) . " -->";

if (isset($_GET['id'])) {
    echo "<!-- ID RECIBIDO: " . $_GET['id'] . " -->";
} else {
    echo "<!-- NO SE RECIBIÓ ID -->";
}
echo "<!-- DEBUG FIN -->";

$database = new Database();
$db = $database->getConnection();

// Obtener áreas
$query_areas = "SELECT * FROM areas WHERE activo = 1 ORDER BY nombre";
$stmt_areas = $db->prepare($query_areas);
$stmt_areas->execute();
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

$usuario = null;
$editar = false;

// VERIFICACIÓN CON DIAGNÓSTICO
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $usuario_id = intval($_GET['id']);
    
    echo "<!-- USUARIO_ID: " . $usuario_id . " -->";
    
    // Validar que el ID sea numérico y mayor a 0
    if ($usuario_id > 0) {
        $editar = true;
        
        // CONSULTA CON DIAGNÓSTICO
        $query_usuario = "SELECT u.*, a.nombre as area_nombre 
                         FROM usuarios u 
                         LEFT JOIN areas a ON u.area_id = a.id 
                         WHERE u.id = ?";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->execute([$usuario_id]);
        $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
        
        echo "<!-- CONSULTA EJECUTADA PARA ID: " . $usuario_id . " -->";
        echo "<!-- USUARIO ENCONTRADO: " . ($usuario ? 'SI' : 'NO') . " -->";
        
        if ($usuario) {
            echo "<!-- USUARIO DATA: " . print_r($usuario, true) . " -->";
        }
        
        // VERIFICAR QUE EL USUARIO EXISTA
        if (!$usuario) {
            $_SESSION['error'] = "Usuario no encontrado";
            redirectTo('usuarios.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "ID de usuario inválido";
        redirectTo('usuarios.php');
        exit();
    }
}

$mensaje = '';
$error = '';

if ($_POST) {
    try {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $rol = $_POST['rol'];
        $area_id = !empty($_POST['area_id']) ? $_POST['area_id'] : null;
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validaciones
        if (empty($nombre) || empty($email) || empty($rol)) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del email no es válido");
        }
        
        // Verificar si el email ya existe (excepto para el usuario actual)
        $query_verificar = "SELECT id FROM usuarios WHERE email = ?";
        $params = [$email];
        
        if ($editar) {
            $query_verificar .= " AND id != ?";
            $params[] = $usuario['id'];
        }
        
        $stmt_verificar = $db->prepare($query_verificar);
        $stmt_verificar->execute($params);
        
        if ($stmt_verificar->rowCount() > 0) {
            throw new Exception("El email ya está registrado por otro usuario");
        }
        
        if ($editar) {
            // Actualizar usuario
            $query = "UPDATE usuarios SET nombre = ?, email = ?, rol = ?, area_id = ?, activo = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$nombre, $email, $rol, $area_id, $activo, $usuario['id']]);
            $mensaje = "Usuario actualizado exitosamente";
        } else {
            // Crear nuevo usuario
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $query = "INSERT INTO usuarios (nombre, email, password, rol, area_id, activo) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$nombre, $email, $password, $rol, $area_id, $activo]);
            $mensaje = "Usuario creado exitosamente. Contraseña por defecto: <strong>password123</strong>";
        }
        
        // Registrar en logs
        $accion = $editar ? 'editar_usuario' : 'crear_usuario';
        $query_log = "INSERT INTO logs_seguridad (usuario_id, ip, accion, resultado, detalles) VALUES (?, ?, ?, 'exito', ?)";
        $stmt_log = $db->prepare($query_log);
        $stmt_log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $accion, $mensaje]);
        
        redirectTo("usuarios.php?mensaje=" . urlencode($mensaje));
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        // Registrar error en logs
        $accion = $editar ? 'editar_usuario' : 'crear_usuario';
        $query_log = "INSERT INTO logs_seguridad (usuario_id, ip, accion, resultado, detalles) VALUES (?, ?, ?, 'fallo', ?)";
        $stmt_log = $db->prepare($query_log);
        $stmt_log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $accion, $error]);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editar ? 'Editar' : 'Nuevo'; ?> Usuario - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- DIAGNÓSTICO VISUAL -->
    <div style="background: #ff0000; color: white; padding: 10px; position: fixed; top: 0; left: 0; right: 0; z-index: 9999;">
        <strong>DIAGNÓSTICO:</strong> 
        <?php 
        if ($editar && $usuario) {
            echo "Editando usuario ID: {$usuario['id']} - {$usuario['nombre']} - {$usuario['email']}";
        } else {
            echo "Creando nuevo usuario";
        }
        ?>
        | GET ID: <?php echo $_GET['id'] ?? 'NO'; ?>
    </div>
    <div style="height: 60px;"></div>
    
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas <?php echo $editar ? 'fa-user-edit' : 'fa-user-plus'; ?> me-2"></i>
                        <?php 
                        if ($editar && $usuario) {
                            echo 'Editar Usuario: ' . htmlspecialchars($usuario['nombre']);
                        } else {
                            echo 'Nuevo Usuario';
                        }
                        ?>
                    </h1>
                    <a href="usuarios.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Usuarios
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php 
                            if ($editar && $usuario) {
                                echo 'Editando Usuario: ' . htmlspecialchars($usuario['nombre']);
                            } else {
                                echo 'Información del Nuevo Usuario';
                            }
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formUsuario">
                            <?php if ($editar && $usuario): ?>
                            <!-- Información del Usuario que se está editando -->
                            <?php if ($editar && $usuario): ?>
<!-- Información del Usuario que se está editando -->
<div class="alert alert-info mb-4">
    <div class="row">
        <div class="col-md-4">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px; font-size: 20px;">
                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                </div>
                <div>
                    <strong class="d-block fs-5"><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                    <small class="text-muted">ID: <?php echo $usuario['id']; ?> | <?php echo htmlspecialchars($usuario['email']); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <p class="mb-1"><strong>Rol actual:</strong></p>
            <span class="badge bg-<?php 
                switch($usuario['rol']) {
                    case 'administrador': echo 'danger'; break;
                    case 'supervisor': echo 'warning'; break;
                    case 'super_usuario': echo 'info'; break;
                    case 'usuario': echo 'success'; break;
                    default: echo 'secondary';
                }
            ?>"><?php echo ucfirst(str_replace('_', ' ', $usuario['rol'])); ?></span>
            
            <p class="mb-1 mt-2"><strong>Estado:</strong></p>
            <span class="badge bg-<?php echo $usuario['activo'] ? 'success' : 'secondary'; ?>">
                <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
            </span>
            <?php if ($usuario['bloqueado']): ?>
            <span class="badge bg-danger ms-1">Bloqueado</span>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <p class="mb-1"><strong>Fecha de creación:</strong></p>
            <p><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])); ?></p>
            
            <p class="mb-1"><strong>Último login:</strong></p>
            <p><?php echo $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca'; ?></p>
        </div>
    </div>
</div>
<?php endif; ?>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">
                                            <i class="fas fa-user me-1 text-muted"></i> Nombre Completo *
                                        </label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" 
                                               value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" 
                                               required
                                               placeholder="Ingrese el nombre completo del usuario">
                                        <div class="form-text">Nombre y apellidos del usuario</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1 text-muted"></i> Email *
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" 
                                               required
                                               placeholder="usuario@ejemplo.com">
                                        <div class="form-text">El email será usado para iniciar sesión</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rol" class="form-label">
                                            <i class="fas fa-user-tag me-1 text-muted"></i> Rol *
                                        </label>
                                        <select class="form-select" id="rol" name="rol" required>
                                            <option value="">Seleccionar rol...</option>
                                            <option value="usuario" <?php echo ($usuario['rol'] ?? '') == 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                            <option value="super_usuario" <?php echo ($usuario['rol'] ?? '') == 'super_usuario' ? 'selected' : ''; ?>>Super Usuario</option>
                                            <option value="supervisor" <?php echo ($usuario['rol'] ?? '') == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                            <option value="administrador" <?php echo ($usuario['rol'] ?? '') == 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                                        </select>
                                        <div class="form-text">
                                            <small>
                                                <strong>Usuario:</strong> Acceso básico | 
                                                <strong>Super Usuario:</strong> Acceso amplio | 
                                                <strong>Supervisor:</strong> Supervisión | 
                                                <strong>Administrador:</strong> Acceso total
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="area_id" class="form-label">
                                            <i class="fas fa-building me-1 text-muted"></i> Área
                                        </label>
                                        <select class="form-select" id="area_id" name="area_id">
                                            <option value="">Sin área asignada</option>
                                            <?php foreach ($areas as $area): ?>
                                            <option value="<?php echo $area['id']; ?>" 
                                                <?php echo ($usuario['area_id'] ?? '') == $area['id'] ? 'selected' : ''; ?>>
                                                <?php echo $area['nombre']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Asignar área para filtros de requerimientos</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="activo" name="activo" 
                                           <?php echo !$editar || ($usuario['activo'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">
                                        <i class="fas fa-power-off me-1"></i> Usuario activo
                                    </label>
                                </div>
                                <div class="form-text">
                                    Los usuarios inactivos no pueden iniciar sesión en el sistema
                                </div>
                            </div>
                            
                            <?php if ($editar && $usuario): ?>
                            <!-- Acciones Rápidas -->
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                                </h6>
                                <div class="btn-group mt-2">
                                    <a href="acciones.php?action=reset_password&id=<?php echo $usuario['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary"
                                       onclick="return confirm('¿Resetear contraseña de <?php echo $usuario['nombre']; ?>?')">
                                        <i class="fas fa-sync-alt me-1"></i>Resetear Contraseña
                                    </a>
                                    <?php if ($usuario['bloqueado']): ?>
                                    <a href="acciones.php?action=desbloquear&id=<?php echo $usuario['id']; ?>" 
                                       class="btn btn-sm btn-outline-success"
                                       onclick="return confirm('¿Desbloquear usuario <?php echo $usuario['nombre']; ?>?')">
                                        <i class="fas fa-unlock me-1"></i>Desbloquear
                                    </a>
                                    <?php endif; ?>
                                    <?php if (in_array($usuario['rol'], ['usuario', 'super_usuario'])): ?>
                                    <a href="permisos.php?usuario_id=<?php echo $usuario['id']; ?>" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-key me-1"></i>Gestionar Permisos
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- Información para nuevo usuario -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Información importante:</strong> Al crear un nuevo usuario, se asignará automáticamente 
                                la contraseña por defecto <strong>"password123"</strong>. Se recomienda que el usuario cambie 
                                su contraseña después del primer inicio de sesión.
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> <?php echo $editar ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                                </button>
                                <a href="usuarios.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>