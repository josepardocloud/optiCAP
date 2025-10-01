<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/fonts/fontawesome/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="<?php echo SITE_URL; ?>/public/assets/img/logo.png" alt="Logo" class="login-logo">
                <h1><?php echo SITE_NAME; ?></h1>
                <p class="text-muted">Sistema de Gestión de Procesos de Adquisición</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Contraseña
                    </label>
                    <div class="password-input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Recordar sesión</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                </button>
                
                <div class="login-links">
                    <a href="<?php echo SITE_URL; ?>/recuperar-password" class="text-muted">
                        <i class="fas fa-key me-1"></i>¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
            
            <div class="login-footer">
                <p class="text-muted small">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/public/assets/js/bootstrap.bundle.min.js"></script>
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
        
        // Auto-focus en el campo de email
        document.getElementById('email')?.focus();
    </script>
</body>
</html>