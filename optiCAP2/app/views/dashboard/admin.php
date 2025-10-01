<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Dashboard Administrador</h1>
        <p class="text-muted">Resumen general del sistema y métricas de gestión</p>
    </div>
</div>

<!-- Estadísticas Rápidas -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Usuarios Activos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['usuarios_activos']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Requerimientos Totales
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['requerimientos_totales']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Incidencias Pendientes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['incidencias_pendientes']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Cuentas Bloqueadas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['bloqueos_cuenta']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-lock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Cumplimiento SLA -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Cumplimiento de SLA</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="slaChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Actividad Reciente</h6>
            </div>
            <div class="card-body">
                <div class="activity-feed">
                    <?php foreach ($actividadReciente as $actividad): ?>
                        <div class="feed-item">
                            <div class="feed-content">
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?>
                                </small>
                                <p class="mb-1 small">
                                    <strong><?php echo htmlspecialchars($actividad['usuario_nombre']); ?></strong>
                                    <?php echo $this->getDescripcionAccion($actividad['accion']); ?>
                                    <br>
                                    <span class="text-info">
                                        <?php echo htmlspecialchars($actividad['requerimiento_codigo']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Métricas de Procesos -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Resumen de Procesos</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Total</th>
                                <th>Completados</th>
                                <th>% Éxito</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Bienes</td>
                                <td><?php echo $this->getTotalProcesosPorTipo('BIEN'); ?></td>
                                <td><?php echo $this->getProcesosCompletadosPorTipo('BIEN'); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo $this->getPorcentajeExitoPorTipo('BIEN'); ?>%
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Servicios</td>
                                <td><?php echo $this->getTotalProcesosPorTipo('SERV'); ?></td>
                                <td><?php echo $this->getProcesosCompletadosPorTipo('SERV'); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo $this->getPorcentajeExitoPorTipo('SERV'); ?>%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/usuarios" class="btn btn-primary btn-block">
                            <i class="fas fa-users fa-fw"></i> Gestionar Usuarios
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/incidencias" class="btn btn-warning btn-block">
                            <i class="fas fa-exclamation-triangle fa-fw"></i> Ver Incidencias
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/configuracion" class="btn btn-info btn-block">
                            <i class="fas fa-cog fa-fw"></i> Configuración
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/reportes" class="btn btn-success btn-block">
                            <i class="fas fa-chart-bar fa-fw"></i> Generar Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo SITE_URL; ?>/public/assets/js/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de cumplimiento SLA
    const ctx = document.getElementById('slaChart').getContext('2d');
    const slaChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Cumplimiento SLA (%)',
                data: [85, 78, 90, 88, 92, 95],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
});
</script>