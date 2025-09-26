<?php
$pageTitle = "Dashboard Supervisor";
$pageScript = "dashboard.js";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard Supervisor</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="actualizarDashboard()">
            <i class="fas fa-sync-alt me-2"></i>Actualizar
        </button>
        <button type="button" class="btn btn-outline-success" onclick="generarReporteDiario()">
            <i class="fas fa-file-excel me-2"></i>Reporte Diario
        </button>
    </div>
</div>

<!-- Métricas Principales -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Requerimientos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $datos['totalRequerimientos']; ?></div>
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
                            Cumplimiento SLA
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $datos['slaPorArea']['global'] ?? 0; ?>%</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            Actividades Atrasadas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($datos['actividadesAtrasadas']); ?></div>
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
                            Tiempo Promedio
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">18.5 días</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Requerimientos por Área -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Requerimientos por Área</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" 
                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" 
                         aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Opciones:</div>
                        <a class="dropdown-item" href="#" onclick="exportarGrafico('area')">Exportar</a>
                        <a class="dropdown-item" href="#" onclick="cambiarVistaGrafico('area')">Cambiar Vista</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="chartRequerimientosArea" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de SLA -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Resumen de SLA</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="chartSLA" height="250"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2">
                        <i class="fas fa-circle text-success"></i> Dentro SLA
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-danger"></i> Fuera SLA
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Actividades Atrasadas -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">Actividades Atrasadas</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($datos['actividadesAtrasadas'])): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($datos['actividadesAtrasadas'] as $actividad): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo $actividad['actividad_nombre']; ?></h6>
                            <small class="text-muted"><?php echo $actividad['codigo']; ?> - <?php echo $actividad['titulo']; ?></small>
                            <br>
                            <small class="text-danger">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo $actividad['dias_retraso']; ?> días de retraso
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">Atrasada</span>
                            <br>
                            <small class="text-muted"><?php echo $actividad['area_nombre']; ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>reportes/desempeno" class="btn btn-sm btn-warning">
                        Ver Reporte Completo
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-success">¡Excelente!</h5>
                    <p class="text-muted">No hay actividades atrasadas en este momento.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Estadísticas Mensuales -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">Estadísticas Mensuales</h6>
            </div>
            <div class="card-body">
                <canvas id="chartMensual" height="200"></canvas>
                <div class="row text-center mt-3">
                    <div class="col-4">
                        <div class="border-right">
                            <div class="h5 mb-0 text-primary" id="mesActual">0</div>
                            <small class="text-muted">Este Mes</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-right">
                            <div class="h5 mb-0 text-success" id="mesAnterior">0</div>
                            <small class="text-muted">Mes Anterior</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="h5 mb-0 text-info" id="variacion">0%</div>
                        <small class="text-muted">Variación</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Requerimientos Recientes -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Requerimientos Recientes</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Área</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Progreso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $requerimientoModel = new Requerimiento();
                    $requerimientosRecientes = $requerimientoModel->obtenerRecientes(null, null, 'supervisor', 10);
                    ?>
                    <?php foreach ($requerimientosRecientes as $req): ?>
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
                            <div class="progress progress-sm">
                                <?php
                                $progreso = 0;
                                if ($req['estado'] == 'completado') $progreso = 100;
                                elseif ($req['estado'] == 'en_proceso') $progreso = 50;
                                else $progreso = 10;
                                ?>
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $progreso; ?>%"></div>
                            </div>
                            <small><?php echo $progreso; ?>%</small>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>requerimientos/detalle/<?php echo $req['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Inicializar gráficos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    inicializarGraficos();
    cargarEstadisticasMensuales();
});

function inicializarGraficos() {
    // Gráfico de Requerimientos por Área
    const ctxArea = document.getElementById('chartRequerimientosArea').getContext('2d');
    const areas = <?php echo json_encode(array_column($datos['requerimientosPorArea'], 'area')); ?>;
    const counts = <?php echo json_encode(array_column($datos['requerimientosPorArea'], 'total_requerimientos')); ?>;
    
    new Chart(ctxArea, {
        type: 'bar',
        data: {
            labels: areas,
            datasets: [{
                label: 'Requerimientos',
                data: counts,
                backgroundColor: '#4e73df',
                borderColor: '#4e73df',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
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

    // Gráfico de SLA
    const ctxSLA = document.getElementById('chartSLA').getContext('2d');
    new Chart(ctxSLA, {
        type: 'doughnut',
        data: {
            labels: ['Dentro SLA', 'Fuera SLA'],
            datasets: [{
                data: [85, 15],
                backgroundColor: ['#1cc88a', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#d52a1e']
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%'
        }
    });
}

function cargarEstadisticasMensuales() {
    // Simular carga de datos mensuales
    const datosMensuales = {
        actual: 45,
        anterior: 38,
        variacion: 18.4
    };
    
    document.getElementById('mesActual').textContent = datosMensuales.actual;
    document.getElementById('mesAnterior').textContent = datosMensuales.anterior;
    document.getElementById('variacion').textContent = `+${datosMensuales.variacion}%`;
    
    // Gráfico mensual
    const ctxMensual = document.getElementById('chartMensual').getContext('2d');
    new Chart(ctxMensual, {
        type: 'line',
        data: {
            labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
            datasets: [{
                label: 'Requerimientos',
                data: [10, 12, 15, 8],
                borderColor: '#36b9cc',
                backgroundColor: 'rgba(54, 185, 204, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function actualizarDashboard() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
    btn.disabled = true;
    
    // Simular actualización
    setTimeout(() => {
        location.reload();
    }, 2000);
}

function generarReporteDiario() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando...';
    btn.disabled = true;
    
    // Simular generación de reporte
    setTimeout(() => {
        alert('Reporte diario generado correctamente. Se descargará automáticamente.');
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // En una implementación real, aquí se descargaría el reporte
        window.open('<?php echo BASE_URL; ?>reportes/exportar?tipo=diario', '_blank');
    }, 3000);
}

function exportarGrafico(tipo) {
    // Lógica para exportar gráfico
    alert(`Exportando gráfico de ${tipo}...`);
}

function cambiarVistaGrafico(tipo) {
    // Lógica para cambiar vista de gráfico
    alert(`Cambiando vista del gráfico de ${tipo}...`);
}
</script>

<style>
.chart-area {
    position: relative;
    height: 300px;
    width: 100%;
}

.chart-pie {
    position: relative;
    height: 250px;
    width: 100%;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.text-xs {
    font-size: 0.7rem;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>