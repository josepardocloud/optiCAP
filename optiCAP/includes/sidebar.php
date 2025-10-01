<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="/opticap/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <?php if (in_array($_SESSION['rol'], ['usuario', 'super_usuario', 'supervisor'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/requerimientos/requerimientos.php">
                    <i class="fas fa-file-alt me-2"></i>
                    Requerimientos
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($_SESSION['rol'], ['super_usuario', 'supervisor', 'administrador'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/procesos/procesos.php">
                    <i class="fas fa-project-diagram me-2"></i>
                    Procesos
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($_SESSION['rol'], ['supervisor', 'administrador'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/usuarios/usuarios.php">
                    <i class="fas fa-users me-2"></i>
                    Usuarios
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['rol'] == 'administrador'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/areas/areas.php">
                    <i class="fas fa-building me-2"></i>
                    Áreas
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($_SESSION['rol'], ['usuario', 'super_usuario', 'supervisor'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/seguimiento/seguimiento.php">
                    <i class="fas fa-chart-line me-2"></i>
                    Seguimiento
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/incidencias/incidencias.php">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Incidencias
                </a>
            </li>
            
            <?php if (in_array($_SESSION['rol'], ['supervisor', 'administrador'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/reportes/reportes.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Reportes
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['rol'] == 'administrador'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/opticap/modules/configuracion/sistema.php">
                    <i class="fas fa-cogs me-2"></i>
                    Configuración
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>