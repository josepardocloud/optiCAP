<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Gestión de Incidencias</h1>
        <p class="text-muted">Reporte y seguimiento de incidencias del sistema</p>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <?php if (in_array($user['rol_nombre'], ['Administrador', 'Supervisor'])): ?>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php echo ($filtros['estado'] ?? '') == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="en_proceso" <?php echo ($filtros['estado'] ?? '') == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="resuelto" <?php echo ($filtros['estado'] ?? '') == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
                <label class="form-label">Prioridad</label>
                <select name="prioridad" class="form-control">
                    <option value="">Todas las prioridades</option>
                    <option value="baja" <?php echo ($filtros['prioridad'] ?? '') == 'baja' ? 'selected' : ''; ?>>Baja</option>
                    <option value="media" <?php echo ($filtros['prioridad'] ?? '') == 'media' ? 'selected' : ''; ?>>Media</option>
                    <option value="alta" <?php echo ($filtros['prioridad'] ?? '') == 'alta' ? 'selected' : ''; ?>>Alta</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="busqueda" class="form-control" 
                       value="<?php echo htmlspecialchars($filtros['busqueda'] ?? ''); ?>" 
                       placeholder="Código o título...">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control" 
                       value="<?php echo htmlspecialchars($filtros['fecha_desde'] ?? ''); ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
                <a href="<?php echo SITE_URL; ?>/incidencias" class="btn btn-secondary">
                    <i class="fas fa-redo me-2"></i>Limpiar
                </a>
                
                <?php if (in_array($user['rol_nombre'], ['Usuario', 'Super Usuario'])): ?>
                <a href="<?php echo SITE_URL; ?>/incidencias/reportar" class="btn btn-success float-end">
                    <i class="fas fa-plus me-2"></i>Reportar Incidencia
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Incidencias -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Incidencias</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Requerimiento</th>
                        <th>Reportada por</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha Reporte</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incidencias as $incidencia): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($incidencia['codigo']); ?></strong>
                        </td>
                        <td>
                            <span title="<?php echo htmlspecialchars($incidencia['descripcion']); ?>">
                                <?php echo htmlspecialchars($incidencia['titulo']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($incidencia['requerimiento_codigo']): ?>
                            <a href="<?php echo SITE_URL; ?>/requerimientos/detalle/<?php echo $incidencia['requerimiento_id']; ?>" 
                               class="text-primary">
                                <?php echo htmlspecialchars($incidencia['requerimiento_codigo']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($incidencia['usuario_reportero_nombre']); ?></td>
                        <td>
                            <?php if ($incidencia['prioridad'] === 'alta'): ?>
                                <span class="badge badge-danger">Alta</span>
                            <?php elseif ($incidencia['prioridad'] === 'media'): ?>
                                <span class="badge badge-warning">Media</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Baja</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($incidencia['estado'] === 'pendiente'): ?>
                                <span class="badge badge-warning">Pendiente</span>
                            <?php elseif ($incidencia['estado'] === 'en_proceso'): ?>
                                <span class="badge badge-primary">En Proceso</span>
                            <?php else: ?>
                                <span class="badge badge-success">Resuelto</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($incidencia['fecha_reporte'])); ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-info" 
                                        onclick="verDetalleIncidencia(<?php echo $incidencia['id']; ?>)"
                                        title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($user['rol_nombre'] === 'Administrador' && $incidencia['estado'] !== 'resuelto'): ?>
                                <a href="<?php echo SITE_URL; ?>/incidencias/resolver/<?php echo $incidencia['id']; ?>" 
                                   class="btn btn-success" title="Resolver incidencia">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (in_array($user['rol_nombre'], ['Usuario', 'Super Usuario']) && 
                                         $incidencia['usuario_reportero_id'] == $user['id'] && 
                                         $incidencia['estado'] === 'pendiente'): ?>
                                <button type="button" class="btn btn-warning" 
                                        onclick="editarIncidencia(<?php echo $incidencia['id']; ?>)"
                                        title="Editar incidencia">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($incidencias)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <p>No se encontraron incidencias</p>
                            <?php if (in_array($user['rol_nombre'], ['Usuario', 'Super Usuario'])): ?>
                            <a href="<?php echo SITE_URL; ?>/incidencias/reportar" class="btn btn-primary">
                                Reportar primera incidencia
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

<!-- Modal para Detalles -->
<div class="modal fade" id="detalleIncidenciaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleIncidenciaBody">
                Cargando...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalleIncidencia(incidenciaId) {
    fetch(`<?php echo SITE_URL; ?>/api/incidencias/detalle/${incidenciaId}`)
        .then(response => response.json())
        .then(data => {
            const modalBody = document.getElementById('detalleIncidenciaBody');
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Código:</strong> ${data.codigo}
                    </div>
                    <div class="col-md-6">
                        <strong>Estado:</strong> ${getBadgeEstado(data.estado)}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <strong>Prioridad:</strong> ${getBadgePrioridad(data.prioridad)}
                    </div>
                    <div class="col-md-6">
                        <strong>Fecha Reporte:</strong> ${new Date(data.fecha_reporte).toLocaleString()}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <strong>Título:</strong>
                        <p>${data.titulo}</p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <strong>Descripción:</strong>
                        <p>${data.descripcion}</p>
                    </div>
                </div>
                ${data.solucion ? `
                <div class="row mt-2">
                    <div class="col-12">
                        <strong>Solución:</strong>
                        <p>${data.solucion}</p>
                    </div>
                </div>
                ` : ''}
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('detalleIncidenciaModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles de la incidencia');
        });
}

function getBadgeEstado(estado) {
    const estados = {
        'pendiente': '<span class="badge badge-warning">Pendiente</span>',
        'en_proceso': '<span class="badge badge-primary">En Proceso</span>',
        'resuelto': '<span class="badge badge-success">Resuelto</span>'
    };
    return estados[estado] || estado;
}

function getBadgePrioridad(prioridad) {
    const prioridades = {
        'alta': '<span class="badge badge-danger">Alta</span>',
        'media': '<span class="badge badge-warning">Media</span>',
        'baja': '<span class="badge badge-secondary">Baja</span>'
    };
    return prioridades[prioridad] || prioridad;
}

function editarIncidencia(incidenciaId) {
    // Implementar edición de incidencia
    alert('Funcionalidad de edición en desarrollo');
}
</script>