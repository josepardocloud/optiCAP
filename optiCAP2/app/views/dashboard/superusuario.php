<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Dashboard - Super Usuario</h1>
        <p class="text-muted">Resumen global y métricas de todas las áreas</p>
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
                            Mis Requerimientos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['mis_requerimientos']; ?>
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
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Actividades Pendientes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['actividades_pendientes']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
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
                            Próximos a Vencer
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['proximos_vencer']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Completados (Mes)
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['completados_mes']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Progreso -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Progreso de Requerimientos por Área</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="progresoAreasChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividades Asignadas -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Mis Actividades Pendientes</h6>
            </div>
            <div class="card-body">
                <div class="activity-list">
                    <?php if (!empty($actividadesAsignadas)): ?>
                        <?php foreach ($actividadesAsignadas as $actividad): ?>
                        <div class="activity-item mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Paso <?php echo $actividad['numero_paso']; ?></h6>
                                    <p class="small text-muted mb-1"><?php echo htmlspecialchars($actividad['nombre']); ?></p>
                                    <small class="text-muted">
                                        Requerimiento: <?php echo htmlspecialchars($actividad['requerimiento_codigo']); ?>
                                    </small>
                                </div>
                                <span class="badge badge-warning">Pendiente</span>
                            </div>
                            <div class="mt-2">
                                <a href="<?php echo SITE_URL; ?>/actividades/editar/<?php echo $actividad['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-1"></i>Atender
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No tiene actividades pendientes asignadas</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Requerimientos Recientes -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Requerimientos Recientes</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Área</th>
                                <th>Estado</th>
                                <th>Progreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requerimientosRecientes as $req): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/requerimientos/detalle/<?php echo $req['id']; ?>" 
                                       class="text-primary">
                                        <?php echo htmlspecialchars($req['codigo']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($req['area_nombre']); ?></td>
                                <td>
                                    <?php echo Helpers::getBadgeEstado($req['estado_general']); ?>
                                </td>
                                <td>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $req['progreso']; ?>%;"
                                             aria-valuenow="<?php echo $req['progreso']; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($req['progreso'], 1); ?>%</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
                        <a href="<?php echo SITE_URL; ?>/requerimientos/crear" class="btn btn-primary btn-block">
                            <i class="fas fa-plus fa-fw"></i> Nuevo Requerimiento
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/requerimientos" class="btn btn-info btn-block">
                            <i class="fas fa-list fa-fw"></i> Ver Todos
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/reportes" class="btn btn-success btn-block">
                            <i class="fas fa-chart-bar fa-fw"></i> Reportes
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/solicitar-permisos" class="btn btn-warning btn-block">
                            <i class="fas fa-key fa-fw"></i> Solicitar Permisos
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
    // Gráfico de progreso por áreas
    const ctx = document.getElementById('progresoAreasChart').getContext('2d');
    const progresoChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Administración', 'RRHH', 'TI', 'Finanzas', 'Logística'],
            datasets: [{
                label: 'Progreso Promedio (%)',
                data: [85, 72, 90, 78, 82],
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                ],
                borderColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                ],
                borderWidth: 1
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
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>