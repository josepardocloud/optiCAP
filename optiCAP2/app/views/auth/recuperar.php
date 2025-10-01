<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo SITE_NAME; ?></title>
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
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="text-center mb-4">
                    <i class="fas fa-key fa-3x text-primary mb-3"></i>
                    <h3>Recuperar Contraseña</h3>
                    <p class="text-muted">Ingrese su email para recibir un enlace de recuperación</p>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required autofocus>
                    <small class="text-muted">
                        Le enviaremos un enlace para restablecer su contraseña
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Enlace de Recuperación
                </button>
                
                <div class="login-links text-center">
                    <a href="<?php echo SITE_URL; ?>/login" class="text-muted">
                        <i class="fas fa-arrow-left me-1"></i>Volver al Login
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
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus en el campo de email
            document.getElementById('email')?.focus();
            
            // Validación del formulario
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value;
                if (!email) {
                    e.preventDefault();
                    alert('Por favor, ingrese su email');
                    return false;
                }
            });
        });
    </script>
</body>
</html>