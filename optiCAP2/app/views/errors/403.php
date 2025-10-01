<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/fonts/fontawesome/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 text-center">
                <div class="error-container">
                    <i class="fas fa-ban text-danger mb-4" style="font-size: 5rem;"></i>
                    <h1 class="display-4 text-gray-800">403</h1>
                    <h2 class="h3 text-gray-700 mb-4">Acceso Denegado</h2>
                    <p class="text-muted mb-4">
                        No tiene permisos para acceder a esta página.
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo SITE_URL; ?>/dashboard" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Ir al Dashboard
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver Atrás
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>