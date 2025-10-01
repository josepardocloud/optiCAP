<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/fonts/fontawesome/css/all.min.css">
    <link rel="icon" href="<?php echo SITE_URL; ?>/public/assets/img/favicon.ico">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo SITE_URL; ?>/public/assets/img/logo.png" alt="Logo" class="sidebar-logo">
                <h3 class="sidebar-title"><?php echo SITE_NAME; ?></h3>
            </div>
            
            <ul class="sidebar-menu">
                <?php if ($user['rol_nombre'] === 'Administrador'): ?>
                    <li><a href="<?php echo SITE_URL; ?>/dashboard/admin" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/usuarios" class="<?php echo $currentPage === 'usuarios' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>Usuarios
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/procesos" class="<?php echo $currentPage === 'procesos' ? 'active' : ''; ?>">
                        <i class="fas fa-sitemap"></i>Procesos
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/incidencias" class="<?php echo $currentPage === 'incidencias' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle"></i>Incidencias
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/configuracion" class="<?php echo $currentPage === 'configuracion' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>Configuración
                    </a></li>
                    
                <?php elseif ($user['rol_nombre'] === 'Supervisor'): ?>
                    <li><a href="<?php echo SITE_URL; ?>/dashboard/supervisor" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/requerimientos" class="<?php echo $currentPage === 'requerimientos' ? 'active' : ''; ?>">
                        <i class="fas fa-list-alt"></i>Requerimientos
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/reportes" class="<?php echo $currentPage === 'reportes' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>Reportes
                    </a></li>
                    
                <?php elseif ($user['rol_nombre'] === 'Super Usuario'): ?>
                    <li><a href="<?php echo SITE_URL; ?>/dashboard/superusuario" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/requerimientos" class="<?php echo $currentPage === 'requerimientos' ? 'active' : ''; ?>">
                        <i class="fas fa-list-alt"></i>Requerimientos
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/reportes" class="<?php echo $currentPage === 'reportes' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>Reportes
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/incidencias" class="<?php echo $currentPage === 'incidencias' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle"></i>Incidencias
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/solicitar-permisos" class="<?php echo $currentPage === 'permisos' ? 'active' : ''; ?>">
                        <i class="fas fa-key"></i>Solicitar Permisos
                    </a></li>
                    
                <?php else: // Usuario ?>
                    <li><a href="<?php echo SITE_URL; ?>/dashboard/usuario" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/requerimientos" class="<?php echo $currentPage === 'requerimientos' ? 'active' : ''; ?>">
                        <i class="fas fa-list-alt"></i>Requerimientos
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/reportes" class="<?php echo $currentPage === 'reportes' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>Reportes
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/incidencias" class="<?php echo $currentPage === 'incidencias' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle"></i>Incidencias
                    </a></li>
                    <li><a href="<?php echo SITE_URL; ?>/solicitar-permisos" class="<?php echo $currentPage === 'permisos' ? 'active' : ''; ?>">
                        <i class="fas fa-key"></i>Solicitar Permisos
                    </a></li>
                <?php endif; ?>
                
                <li class="sidebar-divider"></li>
                <li><a href="<?php echo SITE_URL; ?>/mi-perfil">
                    <i class="fas fa-user"></i>Mi Perfil
                </a></li>
                <li><a href="<?php echo SITE_URL; ?>/logout" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i>Cerrar Sesión
                </a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            <nav class="navbar">
                <div class="navbar-content">
                    <div class="navbar-left">
                        <button class="btn btn-sm mobile-menu-btn d-md-none">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h4 class="navbar-title mb-0"><?php echo $pageTitle ?? 'Dashboard'; ?></h4>
                    </div>
                    
                    <div class="user-menu">
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo htmlspecialchars($user['nombre']); ?>
                                <span class="badge bg-<?php 
                                    switch($user['rol_nombre']) {
                                        case 'Administrador': echo 'danger'; break;
                                        case 'Supervisor': echo 'warning'; break;
                                        case 'Super Usuario': echo 'info'; break;
                                        default: echo 'secondary';
                                    }
                                ?> ms-2"><?php echo $user['rol_nombre']; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/mi-perfil">
                                    <i class="fas fa-user me-2"></i>Mi Perfil
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Content -->
            <div class="content-wrapper">
                <div class="container-fluid">