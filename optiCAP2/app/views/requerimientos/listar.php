<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Gestión de Requerimientos</h1>
        <p class="text-muted">Lista y gestión de todos los requerimientos del sistema</p>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tipo de Proceso</label>
                <select name="tipo_proceso_id" class="form-control">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($tiposProceso as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>" 
                            <?php echo ($filtros['tipo_proceso_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if (in_array($user['rol_nombre'], ['Administrador', 'Supervisor', 'Super Usuario'])): ?>
            <div class="col-md-3">
                <label class="form-label">Área</label>
                <select name="area_id" class="form-control">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?php echo $area['id']; ?>" 
                            <?php echo ($filtros['area_id'] ?? '') == $area['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php echo ($filtros['estado'] ?? '') == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="en_proceso" <?php echo ($filtros['estado'] ?? '') == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="completado" <?php echo ($filtros['estado'] ?? '') == 'completado' ? 'selected' : ''; ?>>Completado</option>
                    <option value="cancelado" <?php echo ($filtros['estado'] ?? '') == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" 
                       value="<?php echo htmlspecialchars($filtros['codigo'] ?? ''); ?>" 
                       placeholder="Buscar por código...">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
                <a href="<?php echo SITE_URL; ?>/requerimientos" class="btn btn-secondary">
                    <i class="fas fa-redo me-2"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Botón Crear Requerimiento -->
<?php if (in_array($user['rol_nombre'], ['Super Usuario', 'Usuario'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <a href="<?php echo SITE_URL; ?>/requerimientos/crear" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Crear Nuevo Requerimiento
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de Requerimientos -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Requerimientos</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable">
                <thead class="thead-light">
                    <tr>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Área</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Progreso</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requerimientos as $req): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($req['codigo']); ?></strong>
                            <?php if ($req['fecha_salto_condicional']): ?>
                                <span class="badge badge-info ms-1" title="Salto condicional aplicado">
                                    <i class="fas fa-fast-forward"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $req['tipo_proceso_nombre'] == 'Bienes' ? 'primary' : 'success'; ?>">
                                <?php echo htmlspecialchars($req['tipo_proceso_nombre']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($req['area_nombre']); ?></td>
                        <td>
                            <span class="text-truncate" style="max-width: 200px;" 
                                  title="<?php echo htmlspecialchars($req['motivo']); ?>">
                                <?php echo htmlspecialchars($req['motivo']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $this->getBadgeEstado($req['estado_general']); ?>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar 
                                    <?php echo $req['progreso'] >= 100 ? 'bg-success' : 
                                           ($req['progreso'] >= 50 ? 'bg-warning' : 'bg-info'); ?>" 
                                    role="progressbar" 
                                    style="width: <?php echo $req['progreso']; ?>%;"
                                    aria-valuenow="<?php echo $req['progreso']; ?>" 
                                    aria-valuemin="0" aria-valuemax="100">
                                    <?php echo number_format($req['progreso'], 1); ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($req['fecha_creacion'])); ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo SITE_URL; ?>/requerimientos/detalle/<?php echo $req['id']; ?>" 
                                   class="btn btn-info" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($user['rol_nombre'] !== 'Supervisor'): ?>
                                <a href="<?php echo SITE_URL; ?>/requerimientos/imprimir/<?php echo $req['id']; ?>" 
                                   class="btn btn-secondary" title="Imprimir" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (in_array($user['rol_nombre'], ['Super Usuario', 'Usuario']) && 
                                         in_array($req['estado_general'], ['pendiente', 'en_proceso'])): ?>
                                <a href="<?php echo SITE_URL; ?>/incidencias/reportar?requerimiento_id=<?php echo $req['id']; ?>" 
                                   class="btn btn-warning" title="Reportar incidencia">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($requerimientos)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No se encontraron requerimientos</p>
                            <?php if (in_array($user['rol_nombre'], ['Super Usuario', 'Usuario'])): ?>
                            <a href="<?php echo SITE_URL; ?>/requerimientos/crear" class="btn btn-primary">
                                Crear primer requerimiento
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>