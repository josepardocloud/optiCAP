<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Configuración del Sistema</h1>
        <p class="text-muted">Configuración general y parámetros del sistema</p>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-cog me-2"></i>Configuración General
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre_sistema" class="form-label">Nombre del Sistema</label>
                                <input type="text" name="nombre_sistema" id="nombre_sistema" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['nombre_sistema']); ?>" 
                                       required>
                                <small class="text-muted">Nombre que se muestra en el sistema</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="logo_sistema" class="form-label">Logo del Sistema</label>
                                <input type="text" name="logo_sistema" id="logo_sistema" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['logo_sistema']); ?>">
                                <small class="text-muted">Ruta relativa del logo</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_intentos_login" class="form-label">Máximo Intentos de Login</label>
                                <input type="number" name="max_intentos_login" id="max_intentos_login" 
                                       class="form-control" min="1" max="10"
                                       value="<?php echo htmlspecialchars($configuracion['max_intentos_login']); ?>" 
                                       required>
                                <small class="text-muted">Número máximo de intentos fallidos antes del bloqueo</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tiempo_bloqueo_minutos" class="form-label">Tiempo de Bloqueo (minutos)</label>
                                <input type="number" name="tiempo_bloqueo_minutos" id="tiempo_bloqueo_minutos" 
                                       class="form-control" min="1" max="1440"
                                       value="<?php echo htmlspecialchars($configuracion['tiempo_bloqueo_minutos']); ?>" 
                                       required>
                                <small class="text-muted">Tiempo que permanece bloqueada una cuenta</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="dias_alerta_vencimiento" class="form-label">Días de Alerta Vencimiento</label>
                                <input type="number" name="dias_alerta_vencimiento" id="dias_alerta_vencimiento" 
                                       class="form-control" min="1" max="30"
                                       value="<?php echo htmlspecialchars($configuracion['dias_alerta_vencimiento']); ?>" 
                                       required>
                                <small class="text-muted">Días de anticipación para alertas de vencimiento</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_tamano_archivo_mb" class="form-label">Tamaño Máximo de Archivos (MB)</label>
                        <input type="number" name="max_tamano_archivo_mb" id="max_tamano_archivo_mb" 
                               class="form-control" min="1" max="100"
                               value="<?php echo htmlspecialchars($configuracion['max_tamano_archivo_mb']); ?>" 
                               required>
                        <small class="text-muted">Tamaño máximo permitido para archivos adjuntos</small>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Información Importante
                        </h6>
                        <ul class="mb-0 small">
                            <li>Los cambios en la configuración afectan a todos los usuarios del sistema</li>
                            <li>El tiempo de bloqueo se aplica después de superar el máximo de intentos fallidos</li>
                            <li>Las alertas de vencimiento se envían automáticamente por email</li>
                            <li>El tamaño máximo de archivos aplica para todas las evidencias</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo SITE_URL; ?>/dashboard/admin" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Información del Sistema -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-info-circle me-2"></i>Información del Sistema
                </h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Versión</dt>
                    <dd class="col-sm-7"><?php echo SYSTEM_VERSION; ?></dd>
                    
                    <dt class="col-sm-5">Última Actualización</dt>
                    <dd class="col-sm-7"><?php echo LAST_UPDATE; ?></dd>
                    
                    <dt class="col-sm-5">PHP</dt>
                    <dd class="col-sm-7"><?php echo PHP_VERSION; ?></dd>
                    
                    <dt class="col-sm-5">Servidor</dt>
                    <dd class="col-sm-7"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></dd>
                    
                    <dt class="col-sm-5">Base de Datos</dt>
                    <dd class="col-sm-7">MySQL</dd>
                    
                    <dt class="col-sm-5">Usuarios Activos</dt>
                    <dd class="col-sm-7">
                        <?php 
                        $pdo = (new Database())->getConnection();
                        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo'");
                        echo $stmt->fetchColumn();
                        ?>
                    </dd>
                    
                    <dt class="col-sm-5">Requerimientos</dt>
                    <dd class="col-sm-7">
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM requerimientos");
                        echo $stmt->fetchColumn();
                        ?>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Configuración de Seguridad -->
        <div class="card shadow">
            <div class="card-header py-3 bg-warning">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-shield-alt me-2"></i>Configuración de Seguridad
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-3">
                        <strong>Política de Contraseñas:</strong>
                        <ul class="mt-1 mb-0">
                            <li>Mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres</li>
                            <li>Encriptación bcrypt</li>
                            <li>Cambio obligatorio en primer acceso</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <strong>Control de Sesiones:</strong>
                        <ul class="mt-1 mb-0">
                            <li>Timeout: <?php echo SESSION_TIMEOUT / 60; ?> minutos</li>
                            <li>Sesiones seguras</li>
                            <li>Registro de accesos</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Protección de Archivos:</strong>
                        <ul class="mt-1 mb-0">
                            <li>Validación de tipos MIME</li>
                            <li>Límite de tamaño: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB</li>
                            <li>Almacenamiento seguro</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación de campos numéricos
    const numericInputs = document.querySelectorAll('input[type="number"]');
    numericInputs.forEach(input => {
        input.addEventListener('change', function() {
            const min = parseInt(this.min);
            const max = parseInt(this.max);
            const value = parseInt(this.value);
            
            if (value < min) {
                this.value = min;
                alert(`El valor mínimo permitido es ${min}`);
            } else if (value > max) {
                this.value = max;
                alert(`El valor máximo permitido es ${max}`);
            }
        });
    });
    
    // Previsualización del nombre del sistema
    const nombreSistemaInput = document.getElementById('nombre_sistema');
    const pageTitle = document.querySelector('title');
    
    nombreSistemaInput.addEventListener('input', function() {
        // Esta función podría actualizar una previsualización en tiempo real
        console.log('Nuevo nombre del sistema:', this.value);
    });
});
</script>