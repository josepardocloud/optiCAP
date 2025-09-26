<?php
$pageTitle = "Error {$error_code} - {$error_title}";
// No incluir header normal para evitar loops en errores
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
        .error-code {
            font-size: 5rem;
            font-weight: bold;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.5rem;
            color: #343a40;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-retry {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .btn-retry:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .error-details {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 2rem;
            text-align: left;
            font-size: 0.9rem;
            display: <?php echo ENVIRONMENT === 'development' ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <?php if ($error_code == 404): ?>
                    <i class="fas fa-map-signs"></i>
                <?php elseif ($error_code == 403): ?>
                    <i class="fas fa-ban"></i>
                <?php elseif ($error_code == 401): ?>
                    <i class="fas fa-user-lock"></i>
                <?php elseif ($error_code == 500): ?>
                    <i class="fas fa-exclamation-triangle"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php endif; ?>
            </div>
            
            <div class="error-code"><?php echo $error_code; ?></div>
            <h1 class="error-title"><?php echo $error_title; ?></h1>
            <p class="error-message"><?php echo $error_message; ?></p>
            
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="javascript:history.back()" class="btn-retry">
                    <i class="fas fa-arrow-left me-2"></i>Volver Atrás
                </a>
                <a href="<?php echo BASE_URL; ?>dashboard" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>Ir al Inicio
                </a>
                <?php if ($show_login_link): ?>
                <a href="<?php echo BASE_URL; ?>auth/login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (ENVIRONMENT === 'development' && isset($_SERVER['REQUEST_URI'])): ?>
            <div class="error-details">
                <h6>Detalles del Error (Solo desarrollo):</h6>
                <ul class="list-unstyled">
                    <li><strong>URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></li>
                    <li><strong>Método:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
                    <li><strong>IP:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?></li>
                    <li><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li><strong>Usuario:</strong> <?php echo $_SESSION['user_nombre'] . ' (ID: ' . $_SESSION['user_id'] . ')'; ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <small class="text-muted">
                    Si el problema persiste, contacte al 
                    <a href="mailto:soporte@opticap.com">administrador del sistema</a>.
                </small>
            </div>
        </div>
    </div>

    <script>
        // Prevenir reenvío del formulario si venimos de uno
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Auto-redirección para errores específicos después de 10 segundos
        setTimeout(function() {
            if (<?php echo in_array($error_code, [401, 403]) ? 'true' : 'false'; ?>) {
                window.location.href = '<?php echo BASE_URL; ?>auth/login';
            }
        }, 10000);

        // Botón de "Intentar de nuevo" para errores 500
        document.addEventListener('DOMContentLoaded', function() {
            const retryButton = document.querySelector('.btn-retry');
            if (retryButton && <?php echo $error_code == 500 ? 'true' : 'false'; ?>) {
                retryButton.addEventListener('click', function() {
                    window.location.reload();
                });
            }
        });
    </script>
</body>
</html>