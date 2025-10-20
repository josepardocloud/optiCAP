<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

if (!isset($_GET['usuario_id'])) {
    redirectTo('modules/usuarios/usuarios.php');
    exit();
}

$usuario_id = $_GET['usuario_id'];
$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario DESTINO (el que recibirá los permisos)
$query_usuario = "SELECT * FROM usuarios WHERE id = ?";
$stmt_usuario = $db->prepare($query_usuario);
$stmt_usuario->execute([$usuario_id]);
$usuario_destino = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario_destino) {
    redirectTo('modules/usuarios/usuarios.php');
    exit();
}

// VERIFICACIÓN CORREGIDA: Permitir permisos para usuario, super_usuario Y supervisor
if (!in_array($usuario_destino['rol'], ['usuario', 'super_usuario', 'supervisor'])) {
    redirectTo('modules/usuarios/usuarios.php?error=Solo se pueden asignar permisos a usuarios con rol Usuario, Super Usuario o Supervisor');
    exit();
}

// Obtener procesos y actividades
$query_procesos = "SELECT p.id as proceso_id, p.nombre as proceso_nombre, p.tipo as proceso_tipo,
                          a.id as actividad_id, a.nombre as actividad_nombre, a.orden, a.descripcion as actividad_descripcion
                   FROM procesos p 
                   INNER JOIN actividades a ON p.id = a.proceso_id 
                   WHERE p.activo = 1 AND a.activo = 1 
                   ORDER BY p.nombre, a.orden";
$stmt_procesos = $db->prepare($query_procesos);
$stmt_procesos->execute();
$actividades = $stmt_procesos->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por proceso
$procesos = [];
foreach ($actividades as $actividad) {
    $proceso_id = $actividad['proceso_id'];
    if (!isset($procesos[$proceso_id])) {
        $procesos[$proceso_id] = [
            'id' => $actividad['proceso_id'],
            'nombre' => $actividad['proceso_nombre'],
            'tipo' => $actividad['proceso_tipo'],
            'actividades' => []
        ];
    }
    $procesos[$proceso_id]['actividades'][] = [
        'actividad_id' => $actividad['actividad_id'],
        'actividad_nombre' => $actividad['actividad_nombre'],
        'actividad_descripcion' => $actividad['actividad_descripcion'] ?? '',
        'orden' => $actividad['orden']
    ];
}

// Obtener permisos actuales del usuario destino
$query_permisos = "SELECT * FROM permisos_actividades WHERE usuario_id = ? AND activo = 1";
$stmt_permisos = $db->prepare($query_permisos);
$stmt_permisos->execute([$usuario_id]);
$permisos_actuales = $stmt_permisos->fetchAll(PDO::FETCH_ASSOC);

// Crear array de permisos para fácil acceso
$permisos_usuario = [];
foreach ($permisos_actuales as $permiso) {
    $permisos_usuario[$permiso['actividad_id']] = $permiso;
}

$mensaje = '';
$error = '';

if ($_POST) {
    try {
        $db->beginTransaction();
        
        // Desactivar permisos existentes
        $query_desactivar = "UPDATE permisos_actividades SET activo = 0 WHERE usuario_id = ?";
        $stmt_desactivar = $db->prepare($query_desactivar);
        $stmt_desactivar->execute([$usuario_id]);
        
        // Agregar nuevos permisos
        if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
            foreach ($_POST['permisos'] as $actividad_id) {
                $query_insert = "INSERT INTO permisos_actividades (usuario_id, proceso_id, actividad_id, permiso_modificar, usuario_asignador_id, fecha_asignacion) 
                               SELECT ?, proceso_id, ?, 1, ?, NOW() FROM actividades WHERE id = ?";
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([$usuario_id, $actividad_id, $_SESSION['usuario_id'], $actividad_id]);
            }
        }
        
        $db->commit();
        $mensaje = "Permisos actualizados exitosamente para el usuario " . $usuario_destino['nombre'];
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al actualizar permisos: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permisos de Usuario - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-key me-2"></i>Permisos de Usuario
                    </h1>
                    <a href="usuarios.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Usuarios
                    </a>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Información del Usuario DESTINO (el que recibe los permisos) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Asignando Permisos a:
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px; font-size: 20px;">
                                        <?php echo strtoupper(substr($usuario_destino['nombre'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong class="d-block fs-5"><?php echo $usuario_destino['nombre']; ?></strong>
                                        <small class="text-muted"><?php echo $usuario_destino['email']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <strong>Rol:</strong><br>
                                <span class="badge bg-<?php 
                                    switch($usuario_destino['rol']) {
                                        case 'super_usuario': echo 'info'; break;
                                        case 'supervisor': echo 'warning'; break;
                                        case 'usuario': echo 'success'; break;
                                        default: echo 'secondary';
                                    }
                                ?> fs-6"><?php echo ucfirst(str_replace('_', ' ', $usuario_destino['rol'])); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Estado:</strong><br>
                                <span class="badge bg-<?php echo $usuario_destino['activo'] ? 'success' : 'secondary'; ?> fs-6">
                                    <?php echo $usuario_destino['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                                <?php if ($usuario_destino['bloqueado']): ?>
                                <span class="badge bg-danger ms-1 fs-6">Bloqueado</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Último Login:</strong><br>
                                <span class="fs-6"><?php echo $usuario_destino['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario_destino['ultimo_login'])) : 'Nunca'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Administrador que asigna -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-shield me-2"></i>Permisos asignados por:
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 18px;">
                                        <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong class="d-block"><?php echo $_SESSION['nombre']; ?></strong>
                                        <small class="text-muted"><?php echo $_SESSION['email']; ?> (Administrador)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <strong>Fecha de asignación:</strong><br>
                                <span class="fs-6"><?php echo date('d/m/Y H:i'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="acciones.php?action=reset_password&id=<?php echo $usuario_id; ?>" 
                                   class="btn btn-outline-secondary w-100" 
                                   onclick="return confirm('¿Resetear contraseña de <?php echo $usuario_destino['nombre']; ?>?')">
                                    <i class="fas fa-sync-alt me-2"></i>Resetear Contraseña
                                </a>
                            </div>
                            <div class="col-md-3">
                                <?php if ($usuario_destino['activo']): ?>
                                <a href="acciones.php?action=desactivar&id=<?php echo $usuario_id; ?>" 
                                   class="btn btn-outline-warning w-100"
                                   onclick="return confirm('¿Desactivar usuario <?php echo $usuario_destino['nombre']; ?>?')">
                                    <i class="fas fa-user-slash me-2"></i>Desactivar
                                </a>
                                <?php else: ?>
                                <a href="acciones.php?action=activar&id=<?php echo $usuario_id; ?>" 
                                   class="btn btn-outline-success w-100"
                                   onclick="return confirm('¿Activar usuario <?php echo $usuario_destino['nombre']; ?>?')">
                                    <i class="fas fa-user-check me-2"></i>Activar
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <?php if ($usuario_destino['bloqueado']): ?>
                                <a href="acciones.php?action=desbloquear&id=<?php echo $usuario_id; ?>" 
                                   class="btn btn-outline-success w-100"
                                   onclick="return confirm('¿Desbloquear usuario <?php echo $usuario_destino['nombre']; ?>?')">
                                    <i class="fas fa-unlock me-2"></i>Desbloquear
                                </a>
                                <?php else: ?>
                                <span class="btn btn-outline-success w-100" style="opacity: 0.6;">
                                    <i class="fas fa-lock-open me-2"></i>No Bloqueado
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <a href="gestionar.php?id=<?php echo $usuario_id; ?>" 
                                   class="btn btn-outline-primary w-100">
                                    <i class="fas fa-edit me-2"></i>Editar Usuario
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Permisos -->
                <form method="POST" id="formPermisos">
                    <div class="card">
                        <div class="card-header ">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tasks me-2"></i>Asignación de Permisos por Actividad
                                <small class="d-block mt-1">
                                    Usuario: <strong><?php echo $usuario_destino['nombre']; ?></strong> 
                                    (<?php echo ucfirst(str_replace('_', ' ', $usuario_destino['rol'])); ?>)
                                </small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Seleccione las actividades para las cuales <strong><?php echo $usuario_destino['nombre']; ?></strong> tendrá permisos de modificación. 
                                Los permisos asignados permitirán al usuario modificar los datos de las actividades seleccionadas en los requerimientos.
                            </div>
                            
                            <!-- Controles de selección masiva -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTodos()">
                                        <i class="fas fa-check-square me-1"></i> Seleccionar Todos
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodos()">
                                        <i class="fas fa-square me-1"></i> Deseleccionar Todos
                                    </button>
                                </div>
                                <div class="col-md-6 text-end">
                                    <span class="badge bg-success fs-6" id="contadorSeleccionados">0</span> 
                                    <span class="text-muted">actividades seleccionadas para <?php echo $usuario_destino['nombre']; ?></span>
                                </div>
                            </div>
                            
                            <?php if (empty($procesos)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay procesos y actividades configurados en el sistema.
                            </div>
                            <?php else: ?>
                            
                            <?php foreach ($procesos as $proceso): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-project-diagram me-2"></i>
                                        <?php echo $proceso['nombre']; ?> 
                                        <span class="badge bg-secondary ms-2"><?php echo $proceso['tipo']; ?></span>
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleProceso(<?php echo $proceso['id']; ?>)">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                </div>
                                <div class="card-body proceso-content" id="proceso-<?php echo $proceso['id']; ?>">
                                    <div class="row">
                                        <?php foreach ($proceso['actividades'] as $actividad): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 <?php echo isset($permisos_usuario[$actividad['actividad_id']]) ? 'border-success' : 'border-light'; ?>">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input permiso-checkbox" 
                                                               type="checkbox" 
                                                               name="permisos[]" 
                                                               value="<?php echo $actividad['actividad_id']; ?>" 
                                                               id="actividad_<?php echo $actividad['actividad_id']; ?>"
                                                               <?php echo isset($permisos_usuario[$actividad['actividad_id']]) ? 'checked' : ''; ?>
                                                               onchange="actualizarContador()">
                                                        <label class="form-check-label w-100" for="actividad_<?php echo $actividad['actividad_id']; ?>">
                                                            <strong class="d-block">
                                                                <?php echo $actividad['orden']; ?>. <?php echo $actividad['actividad_nombre']; ?>
                                                                <?php if (isset($permisos_usuario[$actividad['actividad_id']])): ?>
                                                                <span class="badge bg-success ms-2">Permitido</span>
                                                                <?php endif; ?>
                                                            </strong>
                                                            <?php if (!empty($actividad['actividad_descripcion'])): ?>
                                                            <br><small class="text-muted"><?php echo $actividad['actividad_descripcion']; ?></small>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> Guardar Permisos para <?php echo $usuario_destino['nombre']; ?>
                                </button>
                                <a href="usuarios.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Función para actualizar contador de seleccionados
        function actualizarContador() {
            const checkboxes = document.querySelectorAll('.permiso-checkbox:checked');
            document.getElementById('contadorSeleccionados').textContent = checkboxes.length;
        }
        
        // Función para seleccionar todos los checkboxes
        function seleccionarTodos() {
            const checkboxes = document.querySelectorAll('.permiso-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            actualizarContador();
        }
        
        // Función para deseleccionar todos los checkboxes
        function deseleccionarTodos() {
            const checkboxes = document.querySelectorAll('.permiso-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            actualizarContador();
        }
        
        // Función para expandir/contraer proceso
        function toggleProceso(procesoId) {
            const contenido = document.getElementById('proceso-' + procesoId);
            const boton = event.currentTarget;
            
            if (contenido.style.display === 'none') {
                contenido.style.display = 'block';
                boton.innerHTML = '<i class="fas fa-compress-alt"></i>';
            } else {
                contenido.style.display = 'none';
                boton.innerHTML = '<i class="fas fa-expand-alt"></i>';
            }
        }
        
        // Inicializar contador al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarContador();
            
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Confirmación antes de enviar el formulario
        document.getElementById('formPermisos').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.permiso-checkbox:checked');
            const usuarioNombre = '<?php echo $usuario_destino['nombre']; ?>';
            
            if (checkboxes.length === 0) {
                if (!confirm('No ha seleccionado ninguna actividad para ' + usuarioNombre + '. ¿Desea continuar sin asignar permisos?')) {
                    e.preventDefault();
                }
            } else {
                if (!confirm('¿Está seguro de que desea guardar ' + checkboxes.length + ' permisos para el usuario ' + usuarioNombre + '?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>