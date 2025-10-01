<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener lista de usuarios
$query = "SELECT u.*, a.nombre as area_nombre 
          FROM usuarios u 
          LEFT JOIN areas a ON u.area_id = a.id 
          ORDER BY u.nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener mensajes de acciones desde GET
$mensaje_accion = $_GET['accion_exitosa'] ?? '';
$error_accion = $_GET['accion_error'] ?? '';

// Mensajes normales (para crear/editar usuarios)
$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - OptiCAP</title>
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
                        <i class="fas fa-users me-2"></i>Gestión de Usuarios
                    </h1>
                    <?php if ($_SESSION['rol'] == 'administrador'): ?>
                    <a href="gestionar.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Usuario
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo count($usuarios); ?></h4>
                                        <small>Total Usuarios</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo count(array_filter($usuarios, fn($u) => $u['activo'])); ?></h4>
                                        <small>Usuarios Activos</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo count(array_filter($usuarios, fn($u) => !$u['activo'])); ?></h4>
                                        <small>Usuarios Inactivos</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-slash fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo count(array_filter($usuarios, fn($u) => $u['bloqueado'])); ?></h4>
                                        <small>Usuarios Bloqueados</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-lock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Lista de Usuarios del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select class="form-select" id="filtroRol">
                                    <option value="">Todos los roles</option>
                                    <option value="administrador">Administrador</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="super_usuario">Super Usuario</option>
                                    <option value="usuario">Usuario</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filtroEstado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo">Activos</option>
                                    <option value="inactivo">Inactivos</option>
                                    <option value="bloqueado">Bloqueados</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="buscarUsuario" placeholder="Buscar por nombre o email...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped" id="tablaUsuarios">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Área</th>
                                        <th>Estado</th>
                                        <th>Último Login</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr class="usuario-fila" 
                                        data-rol="<?php echo $usuario['rol']; ?>"
                                        data-estado="<?php echo $usuario['bloqueado'] ? 'bloqueado' : ($usuario['activo'] ? 'activo' : 'inactivo'); ?>"
                                        data-nombre="<?php echo strtolower($usuario['nombre']); ?>"
                                        data-email="<?php echo strtolower($usuario['email']); ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 16px;">
                                                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                                    <?php if ($usuario['bloqueado']): ?>
                                                    <br><small class="text-danger"><i class="fas fa-lock me-1"></i>Cuenta bloqueada</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($usuario['rol']) {
                                                    case 'administrador': echo 'danger'; break;
                                                    case 'supervisor': echo 'warning'; break;
                                                    case 'super_usuario': echo 'info'; break;
                                                    case 'usuario': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?> fs-6">
                                                <i class="fas fa-<?php 
                                                    switch($usuario['rol']) {
                                                        case 'administrador': echo 'user-shield'; break;
                                                        case 'supervisor': echo 'user-tie'; break;
                                                        case 'super_usuario': echo 'user-cog'; break;
                                                        case 'usuario': echo 'user'; break;
                                                        default: echo 'user';
                                                    }
                                                ?> me-1"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $usuario['rol'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($usuario['area_nombre']): ?>
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-building me-1"></i>
                                                <?php echo htmlspecialchars($usuario['area_nombre']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $usuario['activo'] ? 'success' : 'secondary'; ?> fs-6">
                                                <i class="fas fa-<?php echo $usuario['activo'] ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                                <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                            <?php if ($usuario['bloqueado']): ?>
                                            <span class="badge bg-danger ms-1 fs-6">
                                                <i class="fas fa-lock me-1"></i>Bloqueado
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if ($usuario['ultimo_login']): ?>
                                                <i class="fas fa-sign-in-alt me-1 text-muted"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                                <?php else: ?>
                                                <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($_SESSION['rol'] == 'administrador'): ?>
                                                
                                                <!-- Editar Usuario -->
                                                <a href="gestionar.php?id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Editar Usuario"
                                                   data-usuario-id="<?php echo $usuario['id']; ?>"
                                                   data-usuario-nombre="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <!-- Gestión de Estado -->
                                                <?php if ($usuario['bloqueado']): ?>
                                                <a href="acciones.php?action=desbloquear&id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success" 
                                                   title="Desbloquear Usuario" 
                                                   onclick="return confirm('¿Está seguro de desbloquear a <?php echo addslashes($usuario['nombre']); ?>?')">
                                                    <i class="fas fa-unlock"></i>
                                                </a>
                                                <?php elseif ($usuario['activo']): ?>
                                                <a href="acciones.php?action=desactivar&id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning" 
                                                   title="Desactivar Usuario" 
                                                   onclick="return confirm('¿Está seguro de desactivar a <?php echo addslashes($usuario['nombre']); ?>?')">
                                                    <i class="fas fa-user-slash"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="acciones.php?action=activar&id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success" 
                                                   title="Activar Usuario" 
                                                   onclick="return confirm('¿Está seguro de activar a <?php echo addslashes($usuario['nombre']); ?>?')">
                                                    <i class="fas fa-user-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <!-- Permisos (solo para usuarios y super_usuarios) -->
                                                <?php if (in_array($usuario['rol'], ['usuario', 'super_usuario'])): ?>
                                                <a href="permisos.php?usuario_id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Gestionar Permisos">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                                <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        title="Los permisos solo están disponibles para usuarios y super usuarios" 
                                                        disabled>
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <!-- Resetear Contraseña -->
                                                <a href="acciones.php?action=reset_password&id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" 
                                                   title="Resetear Contraseña" 
                                                   onclick="return confirm('¿Está seguro de resetear la contraseña de <?php echo addslashes($usuario['nombre']); ?>?\n\nLa nueva contraseña será: password123')">
                                                    <i class="fas fa-sync-alt"></i>
                                                </a>
                                                
                                                <?php else: ?>
                                                <!-- Vista para supervisores (solo ver) -->
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-eye me-1"></i>Solo lectura
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay usuarios registrados</h5>
                                            <?php if ($_SESSION['rol'] == 'administrador'): ?>
                                            <a href="gestionar.php" class="btn btn-primary mt-2">
                                                <i class="fas fa-plus me-1"></i> Crear Primer Usuario
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal para Mensajes de Acción -->
                <div class="modal fade" id="modalMensaje" tabindex="-1" aria-labelledby="modalMensajeLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="modalMensajeLabel">
                                    <i class="fas fa-check-circle me-2"></i>Acción Completada
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p id="mensajeContenido"></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal para Errores -->
                <div class="modal fade" id="modalError" tabindex="-1" aria-labelledby="modalErrorLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="modalErrorLabel">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Error
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p id="errorContenido"></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
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
    
    <script>
    // Mostrar modales automáticamente si hay mensajes en la URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Mostrar mensaje de acción exitosa
        if (urlParams.has('accion_exitosa')) {
            const mensaje = decodeURIComponent(urlParams.get('accion_exitosa'));
            document.getElementById('mensajeContenido').textContent = mensaje;
            var modalMensaje = new bootstrap.Modal(document.getElementById('modalMensaje'));
            modalMensaje.show();
            
            // Limpiar URL después de mostrar el mensaje
            limpiarURL();
        }
        
        // Mostrar mensaje de error
        if (urlParams.has('accion_error')) {
            const error = decodeURIComponent(urlParams.get('accion_error'));
            document.getElementById('errorContenido').textContent = error;
            var modalError = new bootstrap.Modal(document.getElementById('modalError'));
            modalError.show();
            
            // Limpiar URL después de mostrar el mensaje
            limpiarURL();
        }
        
        // Mostrar mensajes normales
        if (urlParams.has('mensaje')) {
            const mensaje = decodeURIComponent(urlParams.get('mensaje'));
            document.getElementById('mensajeContenido').textContent = mensaje;
            var modalMensaje = new bootstrap.Modal(document.getElementById('modalMensaje'));
            modalMensaje.show();
            
            // Limpiar URL después de mostrar el mensaje
            limpiarURL();
        }
        
        if (urlParams.has('error')) {
            const error = decodeURIComponent(urlParams.get('error'));
            document.getElementById('errorContenido').textContent = error;
            var modalError = new bootstrap.Modal(document.getElementById('modalError'));
            modalError.show();
            
            // Limpiar URL después de mostrar el mensaje
            limpiarURL();
        }
        
        // Función para limpiar parámetros de la URL sin recargar
        function limpiarURL() {
            const url = new URL(window.location);
            url.searchParams.delete('accion_exitosa');
            url.searchParams.delete('accion_error');
            url.searchParams.delete('mensaje');
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url);
        }
        
        // Filtros y búsqueda
        const filtroRol = document.getElementById('filtroRol');
        const filtroEstado = document.getElementById('filtroEstado');
        const buscarUsuario = document.getElementById('buscarUsuario');
        const filas = document.querySelectorAll('.usuario-fila');
        
        function filtrarUsuarios() {
            const rolSeleccionado = filtroRol.value;
            const estadoSeleccionado = filtroEstado.value;
            const textoBusqueda = buscarUsuario.value.toLowerCase();
            
            filas.forEach(fila => {
                const rol = fila.getAttribute('data-rol');
                const estado = fila.getAttribute('data-estado');
                const nombre = fila.getAttribute('data-nombre');
                const email = fila.getAttribute('data-email');
                
                let mostrar = true;
                
                if (rolSeleccionado && rol !== rolSeleccionado) {
                    mostrar = false;
                }
                
                if (estadoSeleccionado && estado !== estadoSeleccionado) {
                    mostrar = false;
                }
                
                if (textoBusqueda && !nombre.includes(textoBusqueda) && !email.includes(textoBusqueda)) {
                    mostrar = false;
                }
                
                fila.style.display = mostrar ? '' : 'none';
            });
        }
        
        if (filtroRol) filtroRol.addEventListener('change', filtrarUsuarios);
        if (filtroEstado) filtroEstado.addEventListener('change', filtrarUsuarios);
        if (buscarUsuario) buscarUsuario.addEventListener('input', filtrarUsuarios);
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    // Función para exportar tabla
    function exportarUsuarios() {
        const tabla = document.getElementById('tablaUsuarios');
        let csv = [];
        const filas = tabla.querySelectorAll('tr');
        
        for (let i = 0; i < filas.length; i++) {
            let fila = [], cols = filas[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length - 1; j++) {
                fila.push(cols[j].innerText.replace(/,/g, ''));
            }
            
            csv.push(fila.join(','));
        }
        
        const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "usuarios_opticap.csv");
        document.body.appendChild(link);
        link.click();
    }
    </script>
</body>
</html>