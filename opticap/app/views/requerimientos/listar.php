<?php
$pageTitle = "Requerimientos";
$pageScript = "requerimientos.js";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gestión de Requerimientos</h1>
    <a href="<?php echo BASE_URL; ?>requerimientos/crear" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nuevo Requerimiento
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php echo isset($_GET['estado']) && $_GET['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="en_proceso" <?php echo isset($_GET['estado']) && $_GET['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="completado" <?php echo isset($_GET['estado']) && $_GET['estado'] == 'completado' ? 'selected' : ''; ?>>Completado</option>
                    <option value="cancelado" <?php echo isset($_GET['estado']) && $_GET['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Área</label>
                <select name="area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($datos['areas'] as $area): ?>
                    <option value="<?php echo $area['id']; ?>" <?php echo isset($_GET['area_id']) && $_GET['area_id'] == $area['id'] ? 'selected' : ''; ?>>
                        <?php echo $area['nombre']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $_GET['fecha_inicio'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $_GET['fecha_fin'] ?? ''; ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
                <a href="<?php echo BASE_URL; ?>requerimientos" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Requerimientos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Requerimientos</h5>
        <div class="input-group" style="width: 300px;">
            <input type="text" class="form-control table-search" placeholder="Buscar..." data-table="tablaRequerimientos">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($datos['requerimientos'])): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="tablaRequerimientos">
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
                    <?php foreach ($datos['requerimientos'] as $req): ?>
                    <tr>
                        <td>
                            <strong><?php echo $req['codigo']; ?></strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo substr($req['titulo'], 0, 60); ?></div>
                                    <small class="text-muted"><?php echo substr($req['descripcion'], 0, 80); ?>...</small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $req['area_nombre']; ?></td>
                        <td>
                            <span class="badge estado-<?php echo $req['estado']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $req['estado'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($req['fecha_creacion'])); ?></td>
                        <td>
                            <div class="progress" style="height: 6px; width: 100px;">
                                <?php
                                $progreso = 0;
                                if ($req['estado'] == 'completado') {
                                    $progreso = 100;
                                } elseif ($req['estado'] == 'en_proceso') {
                                    $progreso = 50;
                                } elseif ($req['estado'] == 'pendiente') {
                                    $progreso = 10;
                                }
                                ?>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progreso; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $progreso; ?>%</small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>requerimientos/detalle/<?php echo $req['id']; ?>" 
                                   class="btn btn-outline-primary" title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($req['id_usuario_solicitante'] == $_SESSION['user_id'] || AuthHelper::hasRole('admin')): ?>
                                <a href="<?php echo BASE_URL; ?>requerimientos/editar/<?php echo $req['id']; ?>" 
                                   class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($datos['totalPaginas'] > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $datos['paginaActual'] == 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo BASE_URL; ?>requerimientos?pagina=<?php echo $datos['paginaActual'] - 1; ?>">
                        Anterior
                    </a>
                </li>
                
                <?php for ($i = 1; $i <= $datos['totalPaginas']; $i++): ?>
                <li class="page-item <?php echo $i == $datos['paginaActual'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo BASE_URL; ?>requerimientos?pagina=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $datos['paginaActual'] == $datos['totalPaginas'] ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo BASE_URL; ?>requerimientos?pagina=<?php echo $datos['paginaActual'] + 1; ?>">
                        Siguiente
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No hay requerimientos</h4>
            <p class="text-muted">No se encontraron requerimientos con los criterios de búsqueda especificados.</p>
            <a href="<?php echo BASE_URL; ?>requerimientos/crear" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Crear Primer Requerimiento
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>