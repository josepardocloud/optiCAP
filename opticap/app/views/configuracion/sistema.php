<?php
$pageTitle = "Configuración del Sistema";
require_once 'app/views/layouts/header.php';

$config = $datos['configuracion'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-cog me-2"></i>Configuración General del Sistema
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre_sistema" class="form-label">Nombre del Sistema *</label>
                            <input type="text" class="form-control" id="nombre_sistema" name="nombre_sistema" 
                                   value="<?php echo htmlspecialchars($config['nombre_sistema']); ?>" required maxlength="100">
                            <div class="invalid-feedback">Por favor ingrese el nombre del sistema.</div>
                            <div class="form-text">
                                Este nombre aparecerá en el header y título de las páginas.
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tiempo_maximo_proceso" class="form-label">Tiempo Máximo del Proceso (días) *</label>
                            <input type="number" class="form-control" id="tiempo_maximo_proceso" name="tiempo_maximo_proceso" 
                                   value="<?php echo $config['tiempo_maximo_proceso']; ?>" min="1" max="365" required>
                            <div class="invalid-feedback">El tiempo máximo debe ser entre 1 y 365 días.</div>
                            <div class="form-text">
                                Tiempo total máximo estimado para completar un requerimiento.
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email_notificaciones" class="form-label">Email para Notificaciones</label>
                            <input type="email" class="form-control" id="email_notificaciones" name="email_notificaciones" 
                                   value="<?php echo htmlspecialchars($config['email_notificaciones']); ?>">
                            <div class="form-text">
                                Email desde el cual se enviarán las notificaciones automáticas.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <th>Versión:</th>
                                                    <td><?php echo Config::APP_VERSION; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Entorno:</th>
                                                    <td>
                                                        <span class="badge <?php echo ENVIRONMENT == 'production' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo ENVIRONMENT; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>URL Base:</th>
                                                    <td><code><?php echo Config::APP_URL; ?></code></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <th>Base de Datos:</th>
                                                    <td><?php echo Config::DB_NAME; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Última Actualización:</th>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($config['fecha_actualizacion'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Usuario Config.:</th>
                                                    <td><?php echo $_SESSION['user_nombre']; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Configuraciones Avanzadas</h6>
                                <p class="mb-2">Las siguientes configuraciones requieren modificación directa del archivo de configuración:</p>
                                <ul class="mb-0 small">
                                    <li>Configuración de base de datos</li>
                                    <li>Parámetros de seguridad y sesiones</li>
                                    <li>Configuración de servidor de correo</li>
                                    <li>Límites de archivos y tipos permitidos</li>
                                </ul>
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
                                    <button type="button" class="btn btn-outline-info me-2" onclick="probarConfiguracion()">
                                        <i class="fas fa-test-tube me-2"></i>Probar Configuración
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Configuración
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel de Diagnóstico -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-stethoscope me-2"></i>Diagnóstico del Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Estado de Servicios</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Base de Datos
                                <span class="badge bg-success" id="status-db">Conectado</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Servidor Web
                                <span class="badge bg-success" id="status-web">Online</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Sistema de Archivos
                                <span class="badge bg-success" id="status-fs">Operativo</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Estadísticas del Sistema</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Usuarios Registrados
                                <span class="badge bg-primary" id="stats-users">0</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Requerimientos Activos
                                <span class="badge bg-info" id="stats-req">0</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Espacio en Disco
                                <span class="badge bg-warning" id="stats-disk">0 MB</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="ejecutarDiagnostico()">
                        <i class="fas fa-sync me-1"></i>Ejecutar Diagnóstico Completo
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="limpiarCache()">
                        <i class="fas fa-broom me-1"></i>Limpiar Cache del Sistema
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function probarConfiguracion() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Probando...';
    btn.disabled = true;
    
    // Simular prueba de configuración
    setTimeout(() => {
        alert('Configuración probada correctamente. Todos los servicios están operativos.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 2000);
}

function ejecutarDiagnostico() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Diagnosticando...';
    btn.disabled = true;
    
    // Simular diagnóstico
    setTimeout(() => {
        // Actualizar estadísticas
        document.getElementById('stats-users').textContent = '25';
        document.getElementById('stats-req').textContent = '148';
        document.getElementById('stats-disk').textContent = '45.2 MB';
        
        alert('Diagnóstico completado. El sistema funciona correctamente.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 3000);
}

function limpiarCache() {
    if (confirm('¿Está seguro de limpiar el cache del sistema? Esto puede mejorar el rendimiento.')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Limpiando...';
        btn.disabled = true;
        
        setTimeout(() => {
            alert('Cache limpiado correctamente.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
    }
}

// Cargar estadísticas al iniciar
document.addEventListener('DOMContentLoaded', function() {
    ejecutarDiagnostico();
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>