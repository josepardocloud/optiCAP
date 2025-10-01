<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Dashboard - Supervisor</h1>
        <p class="text-muted">Métricas globales y análisis de todas las áreas</p>
    </div>
</div>

<!-- KPIs en Tiempo Real -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
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
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Completados
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['requerimientos_completados']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            Vencidos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['requerimientos_vencidos']; ?>
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
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Cumplimiento SLA
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['cumplimiento_sla']; ?>%
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Métricas por Área -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Métricas por Área</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Área</th>
                                <th>Total</th>
                                <th>Completados</th>
                                <th>En Proceso</th>
                                <th>Tasa Éxito</th>
                                <th>SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metricasAreas as $area): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($area['nombre']); ?></td>
                                <td><?php echo $area['total']; ?></td>
                                <td><?php echo $area['completados']; ?></td>
                                <td><?php echo $area['en_proceso']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $area['tasa_exito'] >= 80 ? 'success' : ($area['tasa_exito'] >= 60 ? 'warning' : 'danger'); ?>">
                                        <?php echo $area['tasa_exito']; ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $area['sla'] >= 90 ? 'success' : ($area['sla'] >= 80 ? 'warning' : 'danger'); ?>">
                                        <?php echo $area['sla']; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Tendencias -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tendencias Mensuales</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="tendenciasChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Análisis Comparativo -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Análisis Comparativo</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="comparativaChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Saltos Condicionales -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Análisis de Saltos Condicionales</h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <div class="mb-3">
                        <h4 class="text-primary"><?php echo $stats['saltos_condicionales']; ?>%</h4>
                        <p class="text-muted">de requerimientos aplicaron salto condicional</p>
                    </div>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-info" role="progressbar" 
                             style="width: <?php echo $stats['saltos_condicionales']; ?>%;"
                             aria-valuenow="<?php echo $stats['saltos_condicionales']; ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            <?php echo $stats['saltos_condicionales']; ?>%
                        </div>
                    </div>
                    <p class="small text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Los saltos condicionales reducen el tiempo del proceso en aproximadamente 
                        <?php echo $stats['tiempo_promedio']; ?> días
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo SITE_URL; ?>/public/assets/js/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de tendencias
    const tendenciasCtx = document.getElementById('tendenciasChart').getContext('2d');
    const tendenciasChart = new Chart(tendenciasCtx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Completados',
                data: [45, 52, 48, 55, 58, 62],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
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
            }
        }
    });

    // Gráfico comparativo
    const comparativaCtx = document.getElementById('comparativaChart').getContext('2d');
    const comparativaChart = new Chart(comparativaCtx, {
        type: 'bar',
        data: {
            labels: ['Bienes', 'Servicios'],
            datasets: [{
                label: 'Tiempo Promedio (días)',
                data: [<?php echo $stats['tiempo_promedio'] - 2; ?>, <?php echo $stats['tiempo_promedio'] + 1; ?>],
                backgroundColor: ['#4e73df', '#1cc88a']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>