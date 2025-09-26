<?php
$pageTitle = "Reportes de Requerimientos";
$pageScript = "reportes.js";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Reportes de Requerimientos</h1>
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
        <h5 class="card-title mb-0">Filtros del Reporte</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $datos['filtros']['fecha_inicio']; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $datos['filtros']['fecha_fin']; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Área</label>
                <select name="area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($datos['areas'] as $area): ?>
                    <option value="<?php echo $area['id']; ?>" 
                        <?php echo $datos['filtros']['area_id'] == $area['id'] ? 'selected' : ''; ?>>
                        <?php echo $area['nombre']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
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
    <!-- Estadísticas Generales -->
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title"><?php echo $datos['slaGeneral']['total']; ?></h3>
                        <p class="card-text">Total Requerimientos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-list fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title"><?php echo $datos['slaGeneral']['dentroSLA']; ?></h3>
                        <p class="card-text">Dentro del SLA</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title"><?php echo $datos['slaGeneral']['fueraSLA']; ?></h3>
                        <p class="card-text">Fuera del SLA</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Requerimientos por Área -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Requerimientos por Área</h5>
            </div>
            <div class="card-body">
                <canvas id="chartRequerimientosArea" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Tiempos Promedio -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tiempos Promedio por Actividad</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Actividad</th>
                                <th class="text-center">Tiempo Promedio</th>
                                <th class="text-center">Límite</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos['tiemposPromedio'] as $tiempo): ?>
                            <tr>
                                <td><?php echo $tiempo['actividad']; ?></td>
                                <td class="text-center"><?php echo round($tiempo['tiempo_promedio'], 1); ?> días</td>
                                <td class="text-center"><?php echo $tiempo['tiempo_limite']; ?> días</td>
                                <td class="text-center">
                                    <?php if ($tiempo['tiempo_promedio'] <= $tiempo['tiempo_limite']): ?>
                                    <span class="badge bg-success">Dentro</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Excedido</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla Detallada -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Detalle de Requerimientos</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($datos['requerimientos'])): ?>
        <div class="table-responsive">
            <table class="table table-striped" id="tablaReporte">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Área</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Fecha Límite</th>
                        <th>Días Transcurridos</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['requerimientos'] as $req): ?>
                    <tr>
                        <td><?php echo $req['codigo']; ?></td>
                        <td><?php echo substr($req['titulo'], 0, 50); ?>...</td>
                        <td><?php echo $req['area_nombre']; ?></td>
                        <td>
                            <span class="badge estado-<?php echo $req['estado']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $req['estado'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($req['fecha_creacion'])); ?></td>
                        <td>
                            <?php if ($req['fecha_limite_total']): ?>
                            <?php echo date('d/m/Y', strtotime($req['fecha_limite_total'])); ?>
                            <?php else: ?>
                            <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $dias = floor((time() - strtotime($req['fecha_creacion'])) / (60 * 60 * 24));
                            echo $dias;
                            ?>
                        </td>
                        <td>
                            <?php if ($req['estado'] == 'completado'): ?>
                                <?php if (strtotime($req['fecha_limite_total']) >= time()): ?>
                                <span class="badge bg-success">Dentro</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Fuera</span>
                                <?php endif; ?>
                            <?php else: ?>
                            <span class="badge bg-warning">En Proceso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No hay datos para mostrar</h4>
            <p class="text-muted">No se encontraron requerimientos con los filtros especificados.</p>
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

// Gráfico de Requerimientos por Área
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartRequerimientosArea').getContext('2d');
    
    const datos = <?php echo json_encode($datos['estadisticasAreas']); ?>;
    const labels = datos.map(item => item.area);
    const valores = datos.map(item => item.total_requerimientos);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Requerimientos',
                data: valores,
                backgroundColor: [
                    '#007bff', '#28a745', '#dc3545', '#ffc107', '#6f42c1',
                    '#e83e8c', '#fd7e14', '#20c997', '#6610f2', '#6c757d'
                ]
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
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>