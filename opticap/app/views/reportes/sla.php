<?php
$pageTitle = "Reportes de SLA";
$pageScript = "reportes.js";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Reportes de SLA (Service Level Agreement)</h1>
    <div>
        <button type="button" class="btn btn-outline-primary me-2" onclick="exportarReporte('pdf')">
            <i class="fas fa-file-pdf me-2"></i>Exportar PDF
        </button>
        <button type="button" class="btn btn-outline-success" onclick="exportarReporte('excel')">
            <i class="fas fa-file-excel me-2"></i>Exportar Excel
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros del Reporte SLA</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $datos['filtros']['fecha_inicio']; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $datos['filtros']['fecha_fin']; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Generar Reporte
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <!-- SLA General -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">SLA General del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <canvas id="chartSLAGeneral" height="250"></canvas>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h4 class="text-success mb-0" id="porcentajeSLA">0%</h4>
                            <small>Cumplimiento SLA</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h4 class="text-danger mb-0" id="incumplimientos">0</h4>
                            <small>Incumplimientos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SLA por Área -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">SLA por Área</h5>
            </div>
            <div class="card-body">
                <canvas id="chartSLAPorArea" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tendencias Mensuales -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tendencia de SLA (Últimos 6 Meses)</h5>
            </div>
            <div class="card-body">
                <canvas id="chartTendenciasSLA" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Métricas Clave -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Métricas Clave de SLA</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small">Tiempo Promedio de Proceso</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0">18.5 días</span>
                        <span class="badge bg-success">Dentro del SLA</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small">Actividad con Mayor Retraso</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Evaluación Técnica</span>
                        <span class="badge bg-danger">+3.2 días</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small">Área con Mejor Desempeño</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Compras</span>
                        <span class="badge bg-success">95.2%</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small">Tasa de Cumplimiento Global</label>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: 87.5%"></div>
                    </div>
                    <div class="text-end small">87.5%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de SLA por Actividad -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">SLA Detallado por Actividad</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($datos['slaPorActividad'])): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th class="text-center">Tiempo Límite</th>
                        <th class="text-center">Tiempo Promedio</th>
                        <th class="text-center">Diferencia</th>
                        <th class="text-center">Cumplimiento</th>
                        <th class="text-center">Estado SLA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['slaPorActividad'] as $sla): ?>
                    <tr>
                        <td>
                            <strong><?php echo $sla['actividad']; ?></strong>
                            <br><small class="text-muted"><?php echo $sla['descripcion']; ?></small>
                        </td>
                        <td class="text-center"><?php echo $sla['tiempo_limite']; ?> días</td>
                        <td class="text-center"><?php echo number_format($sla['tiempo_promedio'], 1); ?> días</td>
                        <td class="text-center">
                            <?php
                            $diferencia = $sla['tiempo_promedio'] - $sla['tiempo_limite'];
                            $clase = $diferencia <= 0 ? 'text-success' : 'text-danger';
                            ?>
                            <span class="<?php echo $clase; ?>">
                                <?php echo ($diferencia <= 0 ? '-' : '+') . number_format(abs($diferencia), 1); ?> días
                            </span>
                        </td>
                        <td class="text-center">
                            <?php
                            $porcentaje = min(100, ($sla['dentro_sla'] / max(1, $sla['total'])) * 100);
                            ?>
                            <div class="progress" style="height: 6px; width: 80px; margin: 0 auto;">
                                <div class="progress-bar <?php echo $porcentaje >= 90 ? 'bg-success' : ($porcentaje >= 80 ? 'bg-warning' : 'bg-danger'); ?>" 
                                     style="width: <?php echo $porcentaje; ?>%"></div>
                            </div>
                            <small><?php echo number_format($porcentaje, 1); ?>%</small>
                        </td>
                        <td class="text-center">
                            <?php if ($porcentaje >= 90): ?>
                            <span class="badge bg-success">Excelente</span>
                            <?php elseif ($porcentaje >= 80): ?>
                            <span class="badge bg-warning">Aceptable</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Crítico</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No hay datos de SLA</h4>
            <p class="text-muted">No se encontraron datos de SLA para el período seleccionado.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportarReporte(tipo) {
    const params = new URLSearchParams(window.location.search);
    params.append('tipo', tipo);
    window.open('<?php echo BASE_URL; ?>reportes/exportar?' + params.toString(), '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    // Gráfico SLA General
    const ctxGeneral = document.getElementById('chartSLAGeneral').getContext('2d');
    new Chart(ctxGeneral, {
        type: 'doughnut',
        data: {
            labels: ['Dentro del SLA', 'Fuera del SLA'],
            datasets: [{
                data: [87.5, 12.5],
                backgroundColor: ['#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });

    // Gráfico SLA por Área
    const ctxArea = document.getElementById('chartSLAPorArea').getContext('2d');
    const areas = <?php echo json_encode(array_column($datos['slaPorArea'], 'area')); ?>;
    const porcentajes = <?php echo json_encode(array_column($datos['slaPorArea'], 'porcentaje_cumplimiento')); ?>;
    
    new Chart(ctxArea, {
        type: 'bar',
        data: {
            labels: areas,
            datasets: [{
                label: '% Cumplimiento SLA',
                data: porcentajes,
                backgroundColor: porcentajes.map(p => p >= 90 ? '#28a745' : p >= 80 ? '#ffc107' : '#dc3545')
            }]
        },
        options: {
            responsive: true,
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

    // Gráfico de Tendencias
    const ctxTendencias = document.getElementById('chartTendenciasSLA').getContext('2d');
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
    const tendencias = [85, 82, 88, 87, 90, 87.5];
    
    new Chart(ctxTendencias, {
        type: 'line',
        data: {
            labels: meses,
            datasets: [{
                label: 'Cumplimiento SLA (%)',
                data: tendencias,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 80,
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

    // Actualizar métricas
    document.getElementById('porcentajeSLA').textContent = '87.5%';
    document.getElementById('incumplimientos').textContent = '12';
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>