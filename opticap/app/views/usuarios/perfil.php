<?php
$pageTitle = "Mi Perfil";
require_once 'app/views/layouts/header.php';

$usuario = $datos['usuario'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>Mi Perfil
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            <div class="invalid-feedback">Por favor ingrese su nombre completo.</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Avatar</label>
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <?php echo strtoupper(substr($usuario['nombre'], 0, 2)); ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-camera me-1"></i>Cambiar
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área/Oficina</label>
                            <input type="text" class="form-control" value="<?php echo $usuario['area_nombre']; ?>" disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol en el Sistema</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo ucfirst($usuario['rol']); ?>" disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>" disabled>
                        </div>

                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-chart-bar me-2"></i>Estadísticas de Actividad
                                    </h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h5 class="text-primary mb-0" id="totalRequerimientos">0</h5>
                                                <small>Requerimientos</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h5 class="text-success mb-0" id="actividadesCompletadas">0</h5>
                                                <small>Actividades Completadas</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h5 class="text-warning mb-0" id="actividadesPendientes">0</h5>
                                                <small>Actividades Pendientes</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>dashboard" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                                </a>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>auth/cambiarPassword" class="btn btn-warning me-2">
                                        <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Actualizar Perfil
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información de la Cuenta -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Información de la Cuenta
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">Fecha de Registro:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_actualizacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Estado de Contraseña:</th>
                                <td>
                                    <span class="badge <?php echo $usuario['primer_login'] ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo $usuario['primer_login'] ? 'Debe ser actualizada' : 'Actualizada'; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">Sesión Actual:</th>
                                <td><?php echo date('d/m/Y H:i'); ?></td>
                            </tr>
                            <tr>
                                <th>IP de Conexión:</th>
                                <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                            </tr>
                            <tr>
                                <th>Navegador:</th>
                                <td><?php echo $_SERVER['HTTP_USER_AGENT']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Mi Actividad Reciente
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Actividad</th>
                                <th>Descripción</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody id="actividadReciente">
                            <!-- La actividad se cargará via JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="cargarMasActividad()">
                        <i class="fas fa-plus me-1"></i>Cargar más actividad
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas del usuario
    cargarEstadisticasUsuario();
    
    // Cargar actividad reciente
    cargarActividadReciente();
});

function cargarEstadisticasUsuario() {
    fetch('<?php echo BASE_URL; ?>api/usuarios/estadisticas/<?php echo $_SESSION['user_id']; ?>')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalRequerimientos').textContent = data.totalRequerimientos || 0;
            document.getElementById('actividadesCompletadas').textContent = data.actividadesCompletadas || 0;
            document.getElementById('actividadesPendientes').textContent = data.actividadesPendientes || 0;
        })
        .catch(error => {
            console.error('Error al cargar estadísticas:', error);
        });
}

function cargarActividadReciente() {
    fetch('<?php echo BASE_URL; ?>api/usuarios/actividad/<?php echo $_SESSION['user_id']; ?>')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('actividadReciente');
            tbody.innerHTML = '';
            
            data.forEach(actividad => {
                const row = `
                <tr>
                    <td>${new Date(actividad.fecha).toLocaleString()}</td>
                    <td><span class="badge bg-info">${actividad.accion}</span></td>
                    <td>${actividad.descripcion}</td>
                    <td><small class="text-muted">${actividad.ip}</small></td>
                </tr>
                `;
                tbody.innerHTML += row;
            });
            
            if (data.length === 0) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        No se encontró actividad reciente
                    </td>
                </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error al cargar actividad:', error);
        });
}

function cargarMasActividad() {
    // Implementar paginación para cargar más actividad
    alert('Funcionalidad de cargar más actividad en desarrollo');
}
</script>

<style>
.avatar {
    font-weight: bold;
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>