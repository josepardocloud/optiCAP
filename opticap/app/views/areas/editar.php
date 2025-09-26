<?php
$pageTitle = "Editar Área";
require_once 'app/views/layouts/header.php';

$area = $datos['area'];
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Editar Área: <?php echo $area['nombre']; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre" class="form-label">Nombre del Área *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($area['nombre']); ?>" required maxlength="100">
                            <div class="invalid-feedback">Por favor ingrese el nombre del área.</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                      maxlength="500" placeholder="Describa las funciones principales del área..."><?php echo htmlspecialchars($area['descripcion']); ?></textarea>
                            <div class="form-text">
                                <span id="contadorCaracteres">0</span> / 500 caracteres
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                       <?php echo $area['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Área activa en el sistema
                                </label>
                            </div>
                            <div class="form-text">
                                Las áreas inactivas no aparecerán en los listados de selección.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Información del Área
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Fecha de creación:</small><br>
                                            <strong><?php echo date('d/m/Y H:i', strtotime($area['fecha_creacion'])); ?></strong>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Última actualización:</small><br>
                                            <strong><?php echo date('d/m/Y H:i', strtotime($area['fecha_actualizacion'] ?? $area['fecha_creacion'])); ?></strong>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <small class="text-muted">Usuarios asignados:</small><br>
                                            <?php
                                            $usuarioModel = new Usuario();
                                            $usuariosArea = $usuarioModel->obtenerPorArea($area['id']);
                                            $countUsuarios = count($usuariosArea);
                                            ?>
                                            <span class="badge bg-info"><?php echo $countUsuarios; ?> usuario(s)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>areas" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <div>
                                    <?php if ($countUsuarios == 0): ?>
                                    <button type="button" class="btn btn-outline-danger me-2" 
                                            onclick="confirmarEliminacion()">
                                        <i class="fas fa-trash me-2"></i>Eliminar Área
                                    </button>
                                    <?php endif; ?>
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

        <!-- Usuarios del Área -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>Usuarios Asignados a esta Área
                </h5>
                <span class="badge bg-primary"><?php echo $countUsuarios; ?> usuarios</span>
            </div>
            <div class="card-body">
                <?php if (!empty($usuariosArea)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Último Acceso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosArea as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                             style="width: 30px; height: 30px; font-size: 0.8rem;">
                                            <?php echo strtoupper(substr($usuario['nombre'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?php echo $usuario['nombre']; ?></div>
                                            <?php if ($usuario['primer_login']): ?>
                                            <small class="text-warning">Debe cambiar contraseña</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $usuario['email']; ?></td>
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
                                    <small><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_actualizacion'])); ?></small>
                                    <?php else: ?>
                                    <small class="text-muted">Nunca</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No hay usuarios asignados a esta área.</p>
                    <a href="<?php echo BASE_URL; ?>usuarios/crear" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-user-plus me-1"></i>Crear Usuario
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estadísticas del Área -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Estadísticas del Área
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <h5 class="text-primary mb-0" id="totalRequerimientos">0</h5>
                            <small>Total Requerimientos</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <h5 class="text-success mb-0" id="requerimientosCompletados">0</h5>
                            <small>Completados</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <h5 class="text-warning mb-0" id="requerimientosProceso">0</h5>
                            <small>En Proceso</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="cargarEstadisticasArea()">
                        <i class="fas fa-sync me-1"></i>Actualizar Estadísticas
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="modalConfirmarEliminacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                </div>
                <p>¿Está seguro de que desea eliminar permanentemente el área <strong>"<?php echo $area['nombre']; ?>"</strong>?</p>
                <p class="text-muted small">Solo se pueden eliminar áreas que no tengan usuarios asignados.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="eliminarArea()">Eliminar Permanentemente</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contador de caracteres
    const descripcion = document.getElementById('descripcion');
    const contador = document.getElementById('contadorCaracteres');
    
    descripcion.addEventListener('input', function() {
        contador.textContent = this.value.length;
    });
    
    // Inicializar contador
    contador.textContent = descripcion.value.length;
    
    // Cargar estadísticas iniciales
    cargarEstadisticasArea();
});

function confirmarEliminacion() {
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminacion'));
    modal.show();
}

function eliminarArea() {
    fetch('<?php echo BASE_URL; ?>api/areas/eliminar/<?php echo $area['id']; ?>', {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Área eliminada correctamente.');
            window.location.href = '<?php echo BASE_URL; ?>areas';
        } else {
            alert('Error al eliminar el área: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar el área');
    });
}

function cargarEstadisticasArea() {
    fetch('<?php echo BASE_URL; ?>api/areas/estadisticas/<?php echo $area['id']; ?>')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalRequerimientos').textContent = data.totalRequerimientos || 0;
            document.getElementById('requerimientosCompletados').textContent = data.completados || 0;
            document.getElementById('requerimientosProceso').textContent = data.enProceso || 0;
        })
        .catch(error => {
            console.error('Error al cargar estadísticas:', error);
        });
}
</script>

<style>
.avatar {
    font-weight: bold;
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>