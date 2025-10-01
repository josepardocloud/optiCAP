<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Gestión de Usuarios</h1>
        <p class="text-muted">Administración completa de usuarios del sistema</p>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Rol</label>
                <select name="rol_id" class="form-control">
                    <option value="">Todos los roles</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo $rol['id']; ?>" 
                            <?php echo ($filtros['rol_id'] ?? '') == $rol['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rol['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Área</label>
                <select name="area_id" class="form-control">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?php echo $area['id']; ?>" 
                            <?php echo ($filtros['area_id'] ?? '') == $area['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo ($filtros['estado'] ?? '') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo ($filtros['estado'] ?? '') == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="busqueda" class="form-control" 
                       value="<?php echo htmlspecialchars($filtros['busqueda'] ?? ''); ?>" 
                       placeholder="Nombre o email...">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
                <a href="<?php echo SITE_URL; ?>/usuarios" class="btn btn-secondary">
                    <i class="fas fa-redo me-2"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Botón Crear Usuario -->
<div class="row mb-4">
    <div class="col-12">
        <a href="<?php echo SITE_URL; ?>/usuarios/crear" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Crear Nuevo Usuario
        </a>
    </div>
</div>

<!-- Tabla de Usuarios -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Usuarios</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable">
                <thead class="thead-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Área</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                            <?php if ($usuario['intentos_fallidos'] >= 4): ?>
                                <span class="badge badge-danger ms-1" title="Cuenta bloqueada">
                                    <i class="fas fa-lock"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $this->getClassRol($usuario['rol_nombre']); ?>">
                                <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($usuario['area_nombre']); ?></td>
                        <td>
                            <?php echo $this->getBadgeEstadoUsuario($usuario['estado']); ?>
                        </td>
                        <td>
                            <?php if ($usuario['ultimo_intento']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_intento'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Nunca</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo SITE_URL; ?>/usuarios/editar/<?php echo $usuario['id']; ?>" 
                                   class="btn btn-info" title="Editar usuario">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <a href="<?php echo SITE_URL; ?>/usuarios/permisos/<?php echo $usuario['id']; ?>" 
                                   class="btn btn-warning" title="Gestionar permisos">
                                    <i class="fas fa-key"></i>
                                </a>
                                
                                <?php if ($usuario['intentos_fallidos'] >= 4): ?>
                                <button class="btn btn-success" 
                                        onclick="desbloquearUsuario(<?php echo $usuario['id']; ?>)" 
                                        title="Desbloquear cuenta">
                                    <i class="fas fa-unlock"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-secondary" 
                                        onclick="reiniciarPassword(<?php echo $usuario['id']; ?>)" 
                                        title="Reiniciar contraseña">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                
                                <?php if ($usuario['estado'] === 'activo'): ?>
                                <button class="btn btn-danger" 
                                        onclick="desactivarUsuario(<?php echo $usuario['id']; ?>)" 
                                        title="Desactivar usuario">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn btn-success" 
                                        onclick="activarUsuario(<?php echo $usuario['id']; ?>)" 
                                        title="Activar usuario">
                                    <i class="fas fa-user-check"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>No se encontraron usuarios</p>
                            <a href="<?php echo SITE_URL; ?>/usuarios/crear" class="btn btn-primary">
                                Crear primer usuario
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para acciones -->
<div class="modal fade" id="accionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accionModalTitle">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="accionModalBody">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="accionModalConfirm">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
function desbloquearUsuario(usuarioId) {
    mostrarModal(
        'Desbloquear Cuenta',
        '¿Está seguro de desbloquear esta cuenta? El usuario podrá intentar iniciar sesión nuevamente.',
        function() {
            ejecutarAccionUsuario(usuarioId, 'desbloquear');
        }
    );
}

function reiniciarPassword(usuarioId) {
    mostrarModal(
        'Reiniciar Contraseña',
        '¿Está seguro de reiniciar la contraseña de este usuario? Se generará una nueva contraseña temporal y se enviará por email.',
        function() {
            ejecutarAccionUsuario(usuarioId, 'reiniciar_password');
        }
    );
}

function desactivarUsuario(usuarioId) {
    mostrarModal(
        'Desactivar Usuario',
        '¿Está seguro de desactivar este usuario? No podrá acceder al sistema hasta que sea reactivado.',
        function() {
            ejecutarAccionUsuario(usuarioId, 'desactivar');
        }
    );
}

function activarUsuario(usuarioId) {
    mostrarModal(
        'Activar Usuario',
        '¿Está seguro de activar este usuario? Podrá acceder al sistema nuevamente.',
        function() {
            ejecutarAccionUsuario(usuarioId, 'activar');
        }
    );
}

function mostrarModal(titulo, mensaje, callback) {
    document.getElementById('accionModalTitle').textContent = titulo;
    document.getElementById('accionModalBody').textContent = mensaje;
    
    const confirmBtn = document.getElementById('accionModalConfirm');
    confirmBtn.onclick = callback;
    
    const modal = new bootstrap.Modal(document.getElementById('accionModal'));
    modal.show();
}

function ejecutarAccionUsuario(usuarioId, accion) {
    const formData = new FormData();
    formData.append('accion', accion);
    formData.append('usuario_id', usuarioId);
    
    fetch('<?php echo SITE_URL; ?>/api/usuarios/accion', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            mostrarNotificacion(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al ejecutar la acción', 'error');
    });
}

function mostrarNotificacion(mensaje, tipo) {
    // Implementar notificación toast
    const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
    const icon = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <i class="fas ${icon} me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>