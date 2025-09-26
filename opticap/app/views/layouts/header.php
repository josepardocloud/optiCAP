<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Config::APP_NAME . (isset($pageTitle) ? " - $pageTitle" : ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php if (AuthHelper::isLoggedIn()): ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white" id="sidebar">
            <div class="sidebar-header p-3">
                <h4 class="text-center">
                    <i class="fas fa-boxes me-2"></i>
                    <?php echo Config::APP_NAME; ?>
                </h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>requerimientos">
                        <i class="fas fa-list me-2"></i>Requerimientos
                    </a>
                </li>
                
                <?php if (AuthHelper::hasRole('supervisor')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>reportes">
                        <i class="fas fa-chart-bar me-2"></i>Reportes
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (AuthHelper::hasRole('admin')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>Administración
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>usuarios">Usuarios</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>areas">Áreas</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>actividades">Actividades</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>configuracion">Configuración</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content w-100">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button class="btn btn-dark d-md-none" type="button" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_nombre']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>usuarios/perfil">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/cambiarPassword">Cambiar Contraseña</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/logout">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Content -->
            <div class="container-fluid p-4">
    <?php endif; ?>