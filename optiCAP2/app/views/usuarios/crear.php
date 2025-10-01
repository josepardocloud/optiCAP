<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/usuarios">Usuarios</a>
                </li>
                <li class="breadcrumb-item active">Crear Nuevo</li>
            </ol>
        </nav>
        
        <h1 class="h3 mb-0">Crear Nuevo Usuario</h1>
        <p class="text-muted">Complete la información para crear un nuevo usuario del sistema</p>
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
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Usuario</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nombre Completo
                                </label>
                                <input type="text" name="nombre" id="nombre" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
                                       required autofocus>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       required>
                                <small class="text-muted">Será utilizado para iniciar sesión</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Contraseña
                                </label>
                                <div class="password-input-group">
                                    <input type="password" name="password" id="password" class="form-control" 
                                           required minlength="6">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirmar Contraseña
                                </label>
                                <div class="password-input-group">
                                    <input type="password" name="confirm_password" id="confirm_password" 
                                           class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rol_id" class="form-label">
                                    <i class="fas fa-user-tag me-2"></i>Rol
                                </label>
                                <select name="rol_id" id="rol_id" class="form-control" required>
                                    <option value="">Seleccione un rol</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?php echo $rol['id']; ?>" 
                                            <?php echo ($_POST['rol_id'] ?? '') == $rol['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rol['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="area_id" class="form-label">
                                    <i class="fas fa-building me-2"></i>Área
                                </label>
                                <select name="area_id" id="area_id" class="form-control" required>
                                    <option value="">Seleccione un área</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>" 
                                            <?php echo ($_POST['area_id'] ?? '') == $area['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($area['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="estado" 
                                       id="estado_activo" value="activo" checked>
                                <label class="form-check-label" for="estado_activo">
                                    Activo
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="estado" 
                                       id="estado_inactivo" value="inactivo">
                                <label class="form-check-label" for="estado_inactivo">
                                    Inactivo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Información Importante
                        </h6>
                        <ul class="mb-0">
                            <li>El usuario recibirá un email con sus credenciales de acceso</li>
                            <li>Los permisos granulares se asignan después de crear el usuario</li>
                            <li>El usuario debe cambiar su contraseña en el primer acceso</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo SITE_URL; ?>/usuarios" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Información de Roles -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Descripción de Roles</h6>
            </div>
            <div class="card-body">
                <?php foreach ($roles as $rol): ?>
                <div class="mb-3 p-3 border rounded">
                    <h6 class="mb-2">
                        <span class="badge badge-<?php echo $this->getClassRol($rol['nombre']); ?> me-2">
                            <?php echo htmlspecialchars($rol['nombre']); ?>
                        </span>
                    </h6>
                    <p class="small text-muted mb-0"><?php echo htmlspecialchars($rol['descripcion']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recordatorios de Seguridad -->
        <div class="card shadow">
            <div class="card-header py-3 bg-warning">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-shield-alt me-2"></i>Seguridad y Permisos
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-3">
                        <strong>Política de Contraseñas:</strong>
                        <ul class="mt-1 mb-0">
                            <li>Mínimo 6 caracteres</li>
                            <li>Se recomienda usar mayúsculas, minúsculas y números</li>
                            <li>El usuario debe cambiar la contraseña en el primer acceso</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <strong>Control de Accesos:</strong>
                        <ul class="mt-1 mb-0">
                            <li>Máximo 4 intentos fallidos de login</li>
                            <li>Bloqueo automático después de intentos fallidos</li>
                            <li>Solo el administrador puede desbloquear cuentas</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Permisos Granulares:</strong>
                        <p class="mb-0 mt-1">Los permisos específicos por actividad se asignan después de crear el usuario</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.querySelector(`[onclick="togglePassword('${inputId}')] i`);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.querySelector('form');
    
    // Validar coincidencia de contraseñas
    function validarPassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('input', validarPassword);
    confirmPassword.addEventListener('input', validarPassword);
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Las contraseñas no coinciden. Por favor, verifique.');
            password.focus();
        }
        
        if (password.value.length < 6) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 6 caracteres.');
            password.focus();
        }
    });
    
    // Auto-focus en el primer campo
    document.getElementById('nombre').focus();
});
</script>

<style>
.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
}
</style>