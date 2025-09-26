<?php
$pageTitle = "Dashboard";
require_once 'app/views/layouts/header.php';
?>

<div class="row">
    <!-- Estadísticas Rápidas -->
    <div class="col-md-3 mb-4">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title text-primary"><?php echo $datos['totalRequerimientos']; ?></h5>
                        <p class="card-text">Total Requerimientos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-list fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title text-warning"><?php echo $datos['requerimientosPendientes']; ?></h5>
                        <p class="card-text">Pendientes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title text-info"><?php echo $datos['requerimientosProceso']; ?></h5>
                        <p class="card-text">En Proceso</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-sync fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title text-success"><?php echo $datos['requerimientosCompletados']; ?></h5>
                        <p class="card-text">Completados</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Requerimientos Recientes -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Requerimientos Recientes</h5>
                <a href="<?php echo BASE_URL; ?>requerimientos/crear" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Nuevo Requerimiento
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($datos['requerimientosRecientes'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Título</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos['requerimientosRecientes'] as $req): ?>
                            <tr>
                                <td><?php echo $req['codigo']; ?></td>
                                <td><?php echo substr($req['titulo'], 0, 50) . '...'; ?></td>
                                <td>
                                    <span class="badge estado-<?php echo $req['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $req['estado'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($req['fecha_creacion'])); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>requerimientos/detalle/<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No hay requerimientos recientes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actividades Pendientes -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Mis Actividades Pendientes</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($datos['actividadesPendientes'])): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($datos['actividadesPendientes'] as $act): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo $act['actividad_nombre']; ?></h6>
                            <small class="text-muted"><?php echo date('d/m', strtotime($act['fecha_fin_estimada'])); ?></small>
                        </div>
                        <p class="mb-1 small"><?php echo $act['titulo']; ?></p>
                        <small class="text-muted"><?php echo $act['codigo']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No tienes actividades pendientes</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Indicador SLA -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Indicador SLA</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <div class="progress-circle" data-percent="<?php echo $datos['slaData']['porcentaje']; ?>">
                        <span><?php echo $datos['slaData']['porcentaje']; ?>%</span>
                    </div>
                    <p class="mt-2 small">Cumplimiento de tiempos</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>