<?php
$pageTitle = "Cambiar Contraseña";
require_once 'app/views/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0 text-center">
                    <i class="fas fa-key me-2"></i>Cambio de Contraseña Requerido
                </h5>
            </div>
            <div class="card-body">
                <?php if ($_SESSION['primer_login']): ?>
                <div class="alert alert-info">
                    <h6 class="alert-heading">¡Bienvenido a <?php echo Config::APP_NAME; ?>!</h6>
                    <p class="mb-0">Por su seguridad, debe cambiar la contraseña temporal antes de continuar.</p>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <?php if (!$_SESSION['primer_login']): ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña Actual *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Por favor ingrese su contraseña actual.</div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   pattern=".{6,}" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">La contraseña debe tener al menos 6 caracteres.</div>
                        <div class="form-text">
                            <small>La contraseña debe contener al menos 6 caracteres.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                    </div>

                    <div class="alert alert-light border">
                        <h6 class="alert-heading">Recomendaciones de Seguridad:</h6>
                        <ul class="mb-0 small">
                            <li>Use una combinación de letras mayúsculas y minúsculas</li>
                            <li>Incluya números y caracteres especiales</li>
                            <li>No use información personal fácil de adivinar</li>
                            <li>Evite contraseñas que haya usado anteriormente</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-save me-2"></i>Actualizar Contraseña
                        </button>
                        <?php if (!$_SESSION['primer_login']): ?>
                        <a href="<?php echo BASE_URL; ?>dashboard" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para mostrar/ocultar contraseñas
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const target = document.getElementById(this.getAttribute('data-target'));
            const icon = this.querySelector('i');
            
            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Validación de coincidencia de contraseñas
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
    
    // Indicador de fortaleza de contraseña
    newPassword.addEventListener('input', function() {
        const strengthIndicator = document.getElementById('password-strength');
        if (!strengthIndicator) {
            const indicator = document.createElement('div');
            indicator.id = 'password-strength';
            indicator.className = 'mt-2';
            newPassword.parentNode.parentNode.appendChild(indicator);
        }
        
        const strength = calculatePasswordStrength(this.value);
        updateStrengthIndicator(strength);
    });
    
    function calculatePasswordStrength(password) {
        let score = 0;
        if (password.length >= 6) score++;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        return Math.min(score, 5);
    }
    
    function updateStrengthIndicator(strength) {
        const indicator = document.getElementById('password-strength');
        const labels = ['Muy Débil', 'Débil', 'Regular', 'Fuerte', 'Muy Fuerte'];
        const colors = ['danger', 'warning', 'info', 'success', 'success'];
        
        indicator.innerHTML = `
            <div class="progress mb-1" style="height: 5px;">
                <div class="progress-bar bg-${colors[strength - 1]}" 
                     style="width: ${(strength / 5) * 100}%"></div>
            </div>
            <small class="text-${colors[strength - 1]}">Fortaleza: ${labels[strength - 1]}</small>
        `;
    }
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>