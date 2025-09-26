<?php
$pageTitle = "Gestión de Usuarios";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gestión de Usuarios</h1>
    <a href="<?php echo BASE_URL; ?>usuarios/crear" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Usuarios</h5>
        <div class="input-group" style="width: 300px;">
            <input type="text" class="form-control table-search" placeholder="Buscar usuarios..." data-table="tablaUsuarios">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($datos['usuarios'])): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Área</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['usuarios'] as $usuario): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($usuario['nombre'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo $usuario['nombre']; ?></div>
                                    <?php if ($usuario['primer_login']): ?>
                                    <small class="text-warning"><i class="fas fa-exclamation-circle me-1"></i>Debe cambiar contraseña</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $usuario['email']; ?></td>
                        <td><?php echo $usuario['area_nombre'] ?? 'No asignada'; ?></td>
                        <td>
                            <span class="badge 
                                <?php echo $usuario['rol'] == 'admin' ? 'bg-danger' : 
                                       ($usuario['rol'] == 'supervisor' ? 'bg-warning' : 
                                       ($usuario['rol'] == 'proceso' ? 'bg-info' : 'bg-secondary')); ?>">
                                <?php echo ucfirst($usuario['rol']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $usuario['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($usuario['fecha_actualizacion']): ?>
                            <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_actualizacion'])); ?>
                            <?php else: ?>
                            <span class="text-muted">Nunca</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>usuarios/editar/<?php echo $usuario['id']; ?>" 
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="verPermisos(<?php echo $usuario['id']; ?>)" title="Permisos">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-outline-<?php echo $usuario['activo'] ? 'warning' : 'success'; ?>" 
                                        onclick="cambiarEstado(<?php echo $usuario['id']; ?>, <?php echo $usuario['activo'] ? 0 : 1; ?>)" 
                                        title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="fas fa-<?php echo $usuario['activo'] ? 'times' : 'check'; ?>"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No hay usuarios registrados</h4>
            <p class="text-muted">Comience creando el primer usuario del sistema.</p>
            <a href="<?php echo BASE_URL; ?>usuarios/crear" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Crear Primer Usuario
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Permisos -->
<div class="modal fade" id="modalPermisos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestión de Permisos - <span id="nombreUsuario"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo BASE_URL; ?>usuarios/gestionarPermisos/0" id="formPermisos">
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Actividad</th>
                                    <th class="text-center">Puede Modificar</th>
                                    <th class="text-center">Puede Aprobar</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPermisos">
                                <!-- Los permisos se cargarán via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function verPermisos(usuarioId) {
    // Cargar permisos via AJAX
    fetch('<?php echo BASE_URL; ?>api/usuarios/permisos/' + usuarioId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('nombreUsuario').textContent = data.usuario.nombre;
            document.getElementById('formPermisos').action = 
                '<?php echo BASE_URL; ?>usuarios/gestionarPermisos/' + usuarioId;
            
            const tbody = document.getElementById('tbodyPermisos');
            tbody.innerHTML = '';
            
            data.actividades.forEach(actividad => {
                const permiso = data.permisos.find(p => p.id_actividad === actividad.id) || {};
                const row = `
                <tr>
                    <td>
                        <strong>${actividad.nombre}</strong>
                        <br><small class="text-muted">${actividad.descripcion}</small>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" 
                                   name="actividades[${actividad.id}][modificar]" 
                                   ${permiso.puede_modificar ? 'checked' : ''}>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" 
                                   name="actividades[${actividad.id}][aprobar]" 
                                   ${permiso.puede_aprobar ? 'checked' : ''}>
                        </div>
                    </td>
                </tr>
                `;
                tbody.innerHTML += row;
            });
            
            new bootstrap.Modal(document.getElementById('modalPermisos')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los permisos');
        });
}

function cambiarEstado(usuarioId, nuevoEstado) {
    if (confirm(`¿Está seguro de ${nuevoEstado ? 'activar' : 'desactivar'} este usuario?`)) {
        const formData = new FormData();
        formData.append('estado', nuevoEstado);
        
        fetch('<?php echo BASE_URL; ?>api/usuarios/cambiarEstado/' + usuarioId, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al cambiar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cambiar el estado');
        });
    }
}
</script>

<style>
.avatar {
    font-weight: bold;
    font-size: 14px;
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>