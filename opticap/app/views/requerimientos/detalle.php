<?php
$pageTitle = "Detalle del Requerimiento";
$pageScript = "requerimientos.js";
require_once 'app/views/layouts/header.php';

$req = $datos['requerimiento'];
$seguimiento = $datos['seguimiento'];
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Información General -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Información del Requerimiento</h5>
                <span class="badge estado-<?php echo $req['estado']; ?> fs-6">
                    <?php echo ucfirst(str_replace('_', ' ', $req['estado'])); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">Código:</th>
                                <td><strong><?php echo $req['codigo']; ?></strong></td>
                            </tr>
                            <tr>
                                <th>Título:</th>
                                <td><?php echo $req['titulo']; ?></td>
                            </tr>
                            <tr>
                                <th>Área Solicitante:</th>
                                <td><?php echo $req['area_nombre']; ?></td>
                            </tr>
                            <tr>
                                <th>Solicitante:</th>
                                <td><?php echo $req['usuario_solicitante']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">Fecha Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($req['fecha_creacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha Límite Total:</th>
                                <td>
                                    <?php if ($req['fecha_limite_total']): ?>
                                    <span class="<?php echo strtotime($req['fecha_limite_total']) < time() ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo date('d/m/Y', strtotime($req['fecha_limite_total'])); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">No definida</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Días Transcurridos:</th>
                                <td>
                                    <?php
                                    $diasTranscurridos = floor((time() - strtotime($req['fecha_creacion'])) / (60 * 60 * 24));
                                    echo $diasTranscurridos . ' días';
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6>Descripción:</h6>
                    <p class="text-muted"><?php echo nl2br($req['descripcion']); ?></p>
                </div>
            </div>
        </div>

        <!-- Seguimiento de Actividades -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Seguimiento del Proceso</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($seguimiento as $index => $act): ?>
                    <div class="timeline-item <?php echo $act['estado'] == 'completado' ? 'completed' : ($act['estado'] == 'en_proceso' ? 'active' : ''); ?>">
                        <div class="timeline-marker">
                            <?php if ($act['estado'] == 'completado'): ?>
                            <i class="fas fa-check-circle text-success"></i>
                            <?php elseif ($act['estado'] == 'en_proceso'): ?>
                            <i class="fas fa-play-circle text-primary"></i>
                            <?php elseif ($act['estado'] == 'atrasado'): ?>
                            <i class="fas fa-exclamation-circle text-danger"></i>
                            <?php else: ?>
                            <i class="fas fa-clock text-secondary"></i>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo $act['actividad_nombre']; ?></h6>
                                    <p class="text-muted small mb-1"><?php echo $act['descripcion'] ?? 'Sin descripción'; ?></p>
                                    
                                    <?php if ($act['usuario_asignado']): ?>
                                    <p class="small mb-1"><strong>Asignado a:</strong> <?php echo $act['usuario_asignado']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($act['fecha_inicio_real']): ?>
                                    <p class="small mb-1">
                                        <strong>Ejecutado:</strong> 
                                        <?php echo date('d/m/Y', strtotime($act['fecha_inicio_real'])); ?> 
                                        al <?php echo date('d/m/Y', strtotime($act['fecha_fin_real'])); ?>
                                    </p>
                                    <?php else: ?>
                                    <p class="small mb-1">
                                        <strong>Planeado:</strong> 
                                        <?php echo date('d/m/Y', strtotime($act['fecha_inicio_estimada'])); ?> 
                                        al <?php echo date('d/m/Y', strtotime($act['fecha_fin_estimada'])); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($act['observaciones']): ?>
                                    <p class="small mb-1"><strong>Observaciones:</strong> <?php echo $act['observaciones']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge estado-<?php echo $act['estado']; ?>">
                                        <?php echo ucfirst($act['estado']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Evidencias -->
                            <?php if ($act['evidencias']): ?>
                            <div class="mt-2">
                                <strong class="small">Evidencias:</strong>
                                <?php
                                $evidencias = json_decode($act['evidencias'], true);
                                foreach ($evidencias as $evidencia):
                                ?>
                                <a href="<?php echo BASE_URL . 'assets/uploads/' . $evidencia['file_path']; ?>" 
                                   target="_blank" class="badge bg-light text-dark me-1">
                                    <i class="fas fa-paperclip me-1"></i><?php echo $evidencia['file_name']; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Acciones -->
                            <?php if ($act['estado'] == 'pendiente' || $act['estado'] == 'en_proceso'): ?>
                            <div class="mt-2">
                                <?php if (AuthHelper::hasRole('admin') || $act['id_usuario_asignado'] == $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                        data-bs-target="#modalAvanzarActividad" 
                                        data-actividad-id="<?php echo $act['id']; ?>"
                                        data-actividad-nombre="<?php echo $act['actividad_nombre']; ?>">
                                    <i class="fas fa-forward me-1"></i>Avanzar
                                </button>
                                <?php endif; ?>
                                
                                <?php if (AuthHelper::hasRole('admin') || AuthHelper::hasRole('supervisor')): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" 
                                        data-bs-target="#modalAsignarUsuario" 
                                        data-seguimiento-id="<?php echo $act['id']; ?>"
                                        data-actividad-nombre="<?php echo $act['actividad_nombre']; ?>">
                                    <i class="fas fa-user-plus me-1"></i>Asignar
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Resumen del Progreso -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Resumen del Progreso</h5>
            </div>
            <div class="card-body">
                <?php
                $totalActividades = count($seguimiento);
                $completadas = 0;
                $enProceso = 0;
                $pendientes = 0;
                
                foreach ($seguimiento as $act) {
                    if ($act['estado'] == 'completado') $completadas++;
                    elseif ($act['estado'] == 'en_proceso') $enProceso++;
                    else $pendientes++;
                }
                
                $porcentaje = $totalActividades > 0 ? round(($completadas / $totalActividades) * 100) : 0;
                ?>
                <div class="text-center mb-3">
                    <div class="progress-circle-lg" data-percent="<?php echo $porcentaje; ?>">
                        <span><?php echo $porcentaje; ?>%</span>
                    </div>
                    <h4 class="mt-2">Progreso General</h4>
                </div>
                
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <h5 class="text-success mb-0"><?php echo $completadas; ?></h5>
                            <small>Completadas</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <h5 class="text-primary mb-0"><?php echo $enProceso; ?></h5>
                            <small>En Proceso</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <h5 class="text-warning mb-0"><?php echo $pendientes; ?></h5>
                            <small>Pendientes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Acciones</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($req['id_usuario_solicitante'] == $_SESSION['user_id'] || AuthHelper::hasRole('admin')): ?>
                    <a href="<?php echo BASE_URL; ?>requerimientos/editar/<?php echo $req['id']; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Editar Requerimiento
                    </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-outline-secondary">
                        <i class="fas fa-print me-2"></i>Imprimir Reporte
                    </button>
                    
                    <button type="button" class="btn btn-outline-info">
                        <i class="fas fa-share-alt me-2"></i>Compartir
                    </button>
                    
                    <?php if (AuthHelper::hasRole('admin')): ?>
                    <button type="button" class="btn btn-outline-danger">
                        <i class="fas fa-times me-2"></i>Cancelar Requerimiento
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Avanzar Actividad -->
<div class="modal fade" id="modalAvanzarActividad" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>requerimientos/avanzarActividad/<?php echo $req['id']; ?>/0" 
                  enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Avanzar Actividad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="seguimiento_id" id="seguimientoId">
                    
                    <div class="mb-3">
                        <label class="form-label">Actividad</label>
                        <input type="text" class="form-control" id="actividadNombre" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" required>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completado">Completado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Evidencias (Opcional)</label>
                        <input type="file" name="evidencias[]" class="form-control" multiple 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="form-text">Puede seleccionar múltiples archivos</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Asignar Usuario -->
<div class="modal fade" id="modalAsignarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>requerimientos/asignarUsuario/0">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="requerimiento_id" value="<?php echo $req['id']; ?>">
                    <input type="hidden" name="seguimiento_id" id="asignarSeguimientoId">
                    
                    <div class="mb-3">
                        <label class="form-label">Actividad</label>
                        <input type="text" class="form-control" id="asignarActividadNombre" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Usuario a Asignar</label>
                        <select name="usuario_id" class="form-select" required>
                            <option value="">Seleccionar usuario...</option>
                            <?php foreach ($datos['usuariosArea'] as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Avanzar Actividad
    const modalAvanzar = document.getElementById('modalAvanzarActividad');
    modalAvanzar.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const seguimientoId = button.getAttribute('data-actividad-id');
        const actividadNombre = button.getAttribute('data-actividad-nombre');
        
        const modal = this;
        modal.querySelector('#seguimientoId').value = seguimientoId;
        modal.querySelector('#actividadNombre').value = actividadNombre;
        modal.querySelector('form').action = 
            '<?php echo BASE_URL; ?>requerimientos/avanzarActividad/<?php echo $req['id']; ?>/' + seguimientoId;
    });
    
    // Modal Asignar Usuario
    const modalAsignar = document.getElementById('modalAsignarUsuario');
    modalAsignar.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const seguimientoId = button.getAttribute('data-seguimiento-id');
        const actividadNombre = button.getAttribute('data-actividad-nombre');
        
        const modal = this;
        modal.querySelector('#asignarSeguimientoId').value = seguimientoId;
        modal.querySelector('#asignarActividadNombre').value = actividadNombre;
        modal.querySelector('form').action = 
            '<?php echo BASE_URL; ?>requerimientos/asignarUsuario/' + seguimientoId;
    });
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    margin-bottom: 30px;
}
.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    font-size: 1.2rem;
}
.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #dee2e6;
}
.timeline-item.completed .timeline-content {
    border-left-color: #28a745;
}
.timeline-item.active .timeline-content {
    border-left-color: #007bff;
}
.progress-circle-lg {
    width: 120px;
    height: 120px;
    margin: 0 auto;
    position: relative;
    background: conic-gradient(#007bff <?php echo $porcentaje * 3.6; ?>deg, #e9ecef 0deg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.progress-circle-lg::before {
    content: '';
    position: absolute;
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 50%;
}
.progress-circle-lg span {
    position: relative;
    font-size: 1.5rem;
    font-weight: bold;
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>