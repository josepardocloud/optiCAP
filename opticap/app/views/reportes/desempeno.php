<?php
$pageTitle = "Reportes de Desempeño";
$pageScript = "reportes.js";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Reportes de Desempeño y Eficiencia</h1>
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
        <h5 class="card-title mb-0">Filtros del Reporte de Desempeño</h5>
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
                    <?php
                    $areaModel = new Area();
                    $areas = $areaModel->obtenerTodas();
                    foreach ($areas as $area):
                    ?>
                    <option value="<?php echo $area['id']; ?>" 
                        <?php echo isset($_GET['area_id']) && $_GET['area_id'] == $area['id'] ? 'selected' : ''; ?>>
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
    <!-- Métricas de Desempeño General -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="card-title" id="eficienciaGlobal">0%</h3>
                <p class="card-text">Eficiencia Global</p>
                <div class="progress bg-dark bg-opacity-25" style="height: 6px;">
                    <div class="progress-bar bg-white" id="progressEficiencia" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="card-title" id="tasaCompletamiento">0%</h3>
                <p class="card-text">Tasa de Completamiento</p>
                <div class="progress bg-dark bg-opacity-25" style="height: 6px;">
                    <div class="progress-bar bg-white" id="progressCompletamiento" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="card-title" id="tiempoPromedio">0</h3>
                <p class="card-text">Tiempo Promedio (días)</p>
                <small>Por requerimiento</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="card-title" id="productividad">0</h3>
                <p class="card-text">Productividad</p>
                <small>Req. por usuario/mes</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Desempeño por Usuario -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Desempeño por Usuario</h5>
            </div>
            <div class="card-body">
                <canvas id="chartDesempenoUsuarios" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Ranking de Eficiencia -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 5 - Usuarios Más Eficientes</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php
                    $topUsuarios = array_slice($datos['desempenoUsuarios'], 0, 5);
                    $rank = 1;
                    foreach ($topUsuarios as $usuario):
                        $eficiencia = $usuario['actividades_completadas'] / max(1, $usuario['actividades_asignadas']) * 100;
                    ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary me-2">#<?php echo $rank++; ?></span>
                            <strong><?php echo $usuario['usuario']; ?></strong>
                            <br>
                            <small class="text-muted"><?php echo $usuario['area']; ?></small>
                        </div>
                        <span class="badge bg-success"><?php echo round($eficiencia); ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actividades Atrasadas -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Actividades Atrasadas</h5>
        <span class="badge bg-danger" id="totalAtrasadas">0 atrasadas</span>
    </div>
    <div class="card-body">
        <?php if (!empty($datos['actividadesAtrasadas'])): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Requerimiento</th>
                        <th>Usuario Asignado</th>
                        <th>Días de Retraso</th>
                        <th>Fecha Límite</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['actividadesAtrasadas'] as $actividad): ?>
                    <tr>
                        <td>
                            <strong><?php echo $actividad['actividad_nombre']; ?></strong>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>requerimientos/detalle/<?php echo $actividad['id_requerimiento']; ?>" 
                               class="text-decoration-none">
                                <?php echo $actividad['codigo']; ?>
                            </a>
                            <br>
                            <small class="text-muted"><?php echo $actividad['titulo']; ?></small>
                        </td>
                        <td>
                            <?php if ($actividad['usuario_asignado']): ?>
                            <?php echo $actividad['usuario_asignado']; ?>
                            <?php else: ?>
                            <span class="text-muted">No asignado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-danger">+<?php echo $actividad['dias_retraso']; ?> días</span>
                        </td>
                        <td>
                            <small><?php echo date('d/m/Y', strtotime($actividad['fecha_fin_estimada'])); ?></small>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-envelope"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
            <h5 class="text-success">¡Excelente!</h5>
            <p class="text-muted">No hay actividades atrasadas en el período seleccionado.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Eficiencia del Proceso -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Eficiencia del Proceso por Etapa</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($datos['eficienciaProceso'] as $etapa): ?>
            <div class="col-md-3 mb-3">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center">
                        <h6><?php echo $etapa['etapa']; ?></h6>
                        <div class="progress-circle-sm" data-percent="<?php echo $etapa['eficiencia']; ?>">
                            <span><?php echo $etapa['eficiencia']; ?>%</span>
                        </div>
                        <small class="text-muted">
                            <?php echo $etapa['completados']; ?>/<?php echo $etapa['totales']; ?> completados
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Gráfico de Tendencia -->
        <div class="mt-4">
            <h6>Tendencia de Eficiencia (Últimos 6 Meses)</h6>
            <canvas id="chartTendenciaEficiencia" height="150"></canvas>
        </div>
    </div>
</div>

<script>
function exportarReporte(tipo) {
    const params = new URLSearchParams(window.location.search);
    params.append('tipo', tipo);
    window.open('<?php echo BASE_URL; ?>reportes/exportar?' + params.toString(), '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    // Actualizar métricas
    document.getElementById('eficienciaGlobal').textContent = '87.5%';
    document.getElementById('progressEficiencia').style.width = '87.5%';
    
    document.getElementById('tasaCompletamiento').textContent = '92.3%';
    document.getElementById('progressCompletamiento').style.width = '92.3%';
    
    document.getElementById('tiempoPromedio').textContent = '18.5';
    document.getElementById('productividad').textContent = '4.2';
    
    document.getElementById('totalAtrasadas').textContent = '<?php echo count($datos['actividadesAtrasadas']); ?> atrasadas';

    // Gráfico de Desempeño por Usuario
    const ctxUsuarios = document.getElementById('chartDesempenoUsuarios').getContext('2d');
    const usuarios = <?php echo json_encode(array_column($datos['desempenoUsuarios'], 'usuario')); ?>;
    const eficiencias = <?php echo json_encode(array_map(function($u) {
        return round(($u['actividades_completadas'] / max(1, $u['actividades_asignadas'])) * 100, 1);
    }, $datos['desempenoUsuarios'])); ?>;
    
    new Chart(ctxUsuarios, {
        type: 'bar',
        data: {
            labels: usuarios,
            datasets: [{
                label: 'Eficiencia (%)',
                data: eficiencias,
                backgroundColor: eficiencias.map(e => 
                    e >= 90 ? '#28a745' : e >= 80 ? '#ffc107' : '#dc3545'
                )
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

    // Gráfico de Tendencia de Eficiencia
    const ctxTendencia = document.getElementById('chartTendenciaEficiencia').getContext('2d');
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
    const tendencias = [82, 85, 88, 87, 90, 87.5];
    
    new Chart(ctxTendencia, {
        type: 'line',
        data: {
            labels: meses,
            datasets: [{
                label: 'Eficiencia (%)',
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
});

// Progress circles pequeños
document.querySelectorAll('.progress-circle-sm').forEach(circle => {
    const percent = circle.getAttribute('data-percent');
    circle.style.background = `conic-gradient(#007bff ${percent * 3.6}deg, #e9ecef 0deg)`;
});
</script>

<style>
.progress-circle-sm {
    width: 60px;
    height: 60px;
    margin: 0 auto;
    position: relative;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.8rem;
}
.progress-circle-sm::before {
    content: '';
    position: absolute;
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 50%;
}
.progress-circle-sm span {
    position: relative;
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>