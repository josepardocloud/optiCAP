<?php
// Obtener información del usuario
$usuario = obtenerUsuario($_SESSION['usuario_id']);
?>
<header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="/opticap/dashboard.php">
            <img src="/opticap/assets/img/logo.png" alt="OptiCAP" height="40" class="d-inline-block align-text-top">
            <span class="ms-2 fw-bold text-primary">OptiCAP</span>
        </a>
        
        <!-- Botón para sidebar -->
        <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menú de usuario - SIEMPRE VISIBLE -->
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-1"></i>
                    <span class="d-none d-sm-inline"><?php echo $_SESSION['nombre']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="/opticap/perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/opticap/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>