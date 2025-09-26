<?php
$pageTitle = "Editar Usuario";
require_once 'app/views/layouts/header.php';

$usuario = $datos['usuario'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2"></i>Editar Usuario: <?php echo $usuario['nombre']; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            <div class="invalid-feedback">Por favor ingrese el nombre del usuario.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="id_area" class="form-label">Área/Oficina</label>
                            <select class="form-select" id="id_area" name="id_area" required>
                                <option value="">Seleccionar área...</option>
                                <?php foreach ($datos['areas'] as $area): ?>
                                <option value="<?php echo $area['id']; ?>" 
                                    <?php echo $usuario['id_area'] == $area['id'] ? 'selected' : ''; ?>>
                                    <?php echo $area['nombre']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Por favor seleccione un área.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="">Seleccionar rol...</option>
                                <option value="usuario" <?php echo $usuario['rol'] == 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                <option value="proceso" <?php echo $usuario['rol'] == 'proceso' ? 'selected' : ''; ?>>Usuario Proceso</option>
                                <option value="supervisor" <?php echo $usuario['rol'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="admin" <?php echo $usuario['rol'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                            <div class="invalid-feedback">Por favor seleccione un rol.</div>
                        </div>

                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                       <?php echo $usuario['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Usuario activo en el sistema
                                </label>
                            </div>
                            <div class="form-text">
                                Los usuarios inactivos no pueden iniciar sesión en el sistema.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Información del Usuario
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Fecha de creación:</small><br>
                                            <strong><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])); ?></strong>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Última actualización:</small><br>
                                            <strong><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_actualizacion'])); ?></strong>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted">Estado de contraseña:</small><br>
                                            <span class="badge <?php echo $usuario['primer_login'] ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo $usuario['primer_login'] ? 'Debe cambiar contraseña' : 'Contraseña actualizada'; ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted">Acciones disponibles:</small><br>
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    onclick="resetearPassword(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-key me-1"></i>Resetear Contraseña
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>usuarios" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver a la lista
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-info me-2" 
                                            onclick="gestionarPermisos(<?php echo $usuario['id']; ?>)">
                                        <i class="fas fa-key me-2"></i>Gestionar Permisos
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Permisos Actuales -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-key me-2"></i>Permisos Asignados
                </h5>
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="gestionarPermisos(<?php echo $usuario['id']; ?>)">
                    <i class="fas fa-edit me-1"></i>Editar Permisos
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($datos['permisos'])): ?>
                <div class="row">
                    <?php foreach ($datos['permisos'] as $permiso): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $permiso['actividad_nombre']; ?></h6>
                                        <div class="small">
                                            <?php if ($permiso['puede_modificar']): ?>
                                            <span class="badge bg-success me-1">Modificar</span>
                                            <?php endif; ?>
                                            <?php if ($permiso['puede_aprobar']): ?>
                                            <span class="badge bg-primary">Aprobar</span>
                                            <?php endif; ?>
                                            <?php if (!$permiso['puede_modificar'] && !$permiso['puede_aprobar']): ?>
                                            <span class="badge bg-secondary">Solo lectura</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($permiso['fecha_asignacion'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="fas fa-key fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No se han asignado permisos específicos para este usuario.</p>
                    <button type="button" class="btn btn-sm btn-primary mt-2" 
                            onclick="gestionarPermisos(<?php echo $usuario['id']; ?>)">
                        <i class="fas fa-plus me-1"></i>Asignar Permisos
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function resetearPassword(usuarioId) {
    if (confirm('¿Está seguro de resetear la contraseña de este usuario? El usuario deberá cambiar su contraseña en el próximo inicio de sesión.')) {
        fetch('<?php echo BASE_URL; ?>api/usuarios/resetPassword/' + usuarioId, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Contraseña reseteada correctamente. La nueva contraseña temporal es: Temp123');
            } else {
                alert('Error al resetear la contraseña: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al resetear la contraseña');
        });
    }
}

function gestionarPermisos(usuarioId) {
    // Cargar modal de gestión de permisos
    fetch('<?php echo BASE_URL; ?>api/usuarios/permisos/' + usuarioId)
        .then(response => response.json())
        .then(data => {
            // Aquí se implementaría la lógica para mostrar el modal de permisos
            console.log('Gestión de permisos para usuario:', usuarioId, data);
            alert('Funcionalidad de gestión de permisos en desarrollo');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los permisos');
        });
}
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>