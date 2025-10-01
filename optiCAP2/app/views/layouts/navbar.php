<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <!-- Sidebar Toggle -->
        <button class="btn btn-sm btn-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>/dashboard">
            <img src="<?php echo SITE_URL; ?>/public/assets/img/logo.png" alt="Logo" height="30" class="d-inline-block align-text-top">
            <span class="ms-2 d-none d-sm-inline"><?php echo SITE_NAME; ?></span>
        </a>

        <!-- Mobile Actions -->
        <div class="d-flex d-lg-none">
            <!-- Notifications -->
            <div class="dropdown me-2">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificacionesNoLeidas > 0): ?>
                    <span class="badge bg-danger badge-notification"><?php echo $notificacionesNoLeidas; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <h6 class="dropdown-header">Notificaciones</h6>
                    <?php if (!empty($notificacionesRecientes)): ?>
                        <?php foreach ($notificacionesRecientes as $notificacion): ?>
                        <a class="dropdown-item" href="<?php echo $notificacion['enlace'] ?? '#'; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <small class="text-truncate"><?php echo htmlspecialchars($notificacion['titulo']); ?></small>
                                <small class="text-muted"><?php echo time_elapsed_string($notificacion['fecha_envio']); ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center" href="<?php echo SITE_URL; ?>/notificaciones">
                            Ver todas
                        </a>
                    <?php else: ?>
                        <span class="dropdown-item text-muted">No hay notificaciones</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <span class="dropdown-item-text">
                            <small>Conectado como</small><br>
                            <strong><?php echo htmlspecialchars($user['nombre']); ?></strong>
                        </span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/mi-perfil">
                            <i class="fas fa-user me-2"></i>Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/notificaciones">
                            <i class="fas fa-bell me-2"></i>Notificaciones
                            <?php if ($notificacionesNoLeidas > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $notificacionesNoLeidas; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Desktop Actions -->
        <div class="collapse navbar-collapse d-none d-lg-flex">
            <ul class="navbar-nav ms-auto">
                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificacionesNoLeidas > 0): ?>
                        <span class="badge bg-danger badge-notification"><?php echo $notificacionesNoLeidas; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Notificaciones</h6>
                        <?php if (!empty($notificacionesRecientes)): ?>
                            <?php foreach ($notificacionesRecientes as $notificacion): ?>
                            <a class="dropdown-item" href="<?php echo $notificacion['enlace'] ?? '#'; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="text-truncate"><?php echo htmlspecialchars($notificacion['titulo']); ?></small>
                                    <small class="text-muted"><?php echo time_elapsed_string($notificacion['fecha_envio']); ?></small>
                                </div>
                            </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="<?php echo SITE_URL; ?>/notificaciones">
                                Ver todas las notificaciones
                            </a>
                        <?php else: ?>
                            <span class="dropdown-item text-muted">No hay notificaciones nuevas</span>
                        <?php endif; ?>
                    </div>
                </li>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($user['nombre']); ?>
                        <span class="badge bg-<?php 
                            switch($user['rol_nombre']) {
                                case 'Administrador': echo 'danger'; break;
                                case 'Supervisor': echo 'warning'; break;
                                case 'Super Usuario': echo 'info'; break;
                                default: echo 'secondary';
                            }
                        ?> ms-1"><?php echo $user['rol_nombre']; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
                                <small>Conectado como</small><br>
                                <strong><?php echo htmlspecialchars($user['nombre']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/mi-perfil">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/notificaciones">
                                <i class="fas fa-bell me-2"></i>Notificaciones
                                <?php if ($notificacionesNoLeidas > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $notificacionesNoLeidas; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.badge-notification {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 0.7rem;
    padding: 0.25em 0.4em;
}

.navbar-brand {
    font-weight: 600;
}

.dropdown-menu {
    min-width: 280px;
}
</style>

<script>
// Función para formatear fechas relativas
function time_elapsed_string(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " años";
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " meses";
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " días";
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " horas";
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutos";
    
    return "hace un momento";
}

// Actualizar notificaciones en tiempo real
function actualizarNotificaciones() {
    fetch('<?php echo SITE_URL; ?>/api/notificaciones/contador')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                // Actualizar contadores
                document.querySelectorAll('.badge-notification').forEach(badge => {
                    badge.textContent = data.count;
                });
                
                // Mostrar notificación toast si hay nuevas
                if (data.nuevas > 0) {
                    mostrarNotificacionToast('Tiene ' + data.nuevas + ' nuevas notificaciones');
                }
            }
        })
        .catch(error => console.error('Error actualizando notificaciones:', error));
}

// Mostrar toast de notificación
function mostrarNotificacionToast(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-primary border-0 position-fixed top-0 end-0 m-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-bell me-2"></i>${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remover después de cerrar
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Actualizar cada 30 segundos
setInterval(actualizarNotificaciones, 30000);

// Marcar notificaciones como leídas al hacer clic
document.addEventListener('click', function(e) {
    if (e.target.closest('.dropdown-item') && e.target.closest('.dropdown-menu')) {
        const enlace = e.target.closest('.dropdown-item').getAttribute('href');
        if (enlace && enlace !== '#') {
            // Marcar como leída si es una notificación
            const notificacionId = e.target.closest('[data-notificacion-id]')?.getAttribute('data-notificacion-id');
            if (notificacionId) {
                fetch(`<?php echo SITE_URL; ?>/api/notificaciones/marcar-leida/${notificacionId}`, {
                    method: 'POST'
                });
            }
        }
    }
});
</script>