<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/requerimientos">Requerimientos</a>
                </li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($requerimiento['codigo']); ?></li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Detalle de Requerimiento</h1>
                <p class="text-muted">Seguimiento completo de las 14 actividades del proceso</p>
            </div>
            <div class="btn-group">
                <a href="<?php echo SITE_URL; ?>/requerimientos/imprimir/<?php echo $requerimiento['id']; ?>" 
                   class="btn btn-secondary" target="_blank">
                    <i class="fas fa-print me-2"></i>Imprimir
                </a>
                <?php if (in_array($user['rol_nombre'], ['Super Usuario', 'Usuario']) && 
                         in_array($requerimiento['estado_general'], ['pendiente', 'en_proceso'])): ?>
                <a href="<?php echo SITE_URL; ?>/incidencias/reportar?requerimiento_id=<?php echo $requerimiento['id']; ?>" 
                   class="btn btn-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Reportar Incidencia
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Información General -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información General</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Código:</strong><br>
                        <span class="h5"><?php echo htmlspecialchars($requerimiento['codigo']); ?></span>
                        <?php if ($requerimiento['fecha_salto_condicional']): ?>
                            <span class="badge badge-info ms-2" title="Salto condicional aplicado">
                                <i class="fas fa-fast-forward me-1"></i>Salto Condicional
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Tipo:</strong><br>
                        <span class="badge badge-<?php echo $requerimiento['tipo_proceso_nombre'] == 'Bienes' ? 'primary' : 'success'; ?>">
                            <?php echo htmlspecialchars($requerimiento['tipo_proceso_nombre']); ?>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Área:</strong><br>
                        <?php echo htmlspecialchars($requerimiento['area_nombre']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Estado:</strong><br>
                        <?php echo $this->getBadgeEstado($requerimiento['estado_general']); ?>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <strong>Solicitante:</strong><br>
                        <?php echo htmlspecialchars($requerimiento['usuario_solicitante_nombre']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Fecha Creación:</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($requerimiento['fecha_creacion'])); ?>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Motivo:</strong><br>
                        <?php echo nl2br(htmlspecialchars($requerimiento['motivo'])); ?>
                    </div>
                </div>
                
                <!-- Progreso -->
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Progreso:</strong>
                        <div class="progress mt-2" style="height: 25px;">
                            <div class="progress-bar 
                                <?php echo $requerimiento['progreso'] >= 100 ? 'bg-success' : 
                                       ($requerimiento['progreso'] >= 50 ? 'bg-warning' : 'bg-info'); ?>" 
                                role="progressbar" 
                                style="width: <?php echo $requerimiento['progreso']; ?>%;"
                                aria-valuenow="<?php echo $requerimiento['progreso']; ?>" 
                                aria-valuemin="0" aria-valuemax="100">
                                <?php echo number_format($requerimiento['progreso'], 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Línea de Tiempo de Actividades -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Línea de Tiempo - 14 Actividades</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($actividades as $actividad): ?>
                    <div class="timeline-item <?php echo $actividad['estado']; ?>" 
                         data-actividad-id="<?php echo $actividad['id']; ?>">
                        <div class="row">
                            <div class="col-md-1">
                                <div class="timeline-step">
                                    <div class="timeline-step-icon 
                                        <?php echo $this->getClassEstadoActividad($actividad['estado']); ?>">
                                        <?php echo $actividad['numero_paso']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h6 class="mb-1"><?php echo htmlspecialchars($actividad['nombre']); ?></h6>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                
                                <!-- Requisitos Obligatorios -->
                                <?php 
                                $requisitos = json_decode($actividad['requisitos_obligatorios'], true);
                                $requisitosCumplidos = $actividad['requisitos_cumplidos'] ? 
                                    json_decode($actividad['requisitos_cumplidos'], true) : [];
                                ?>
                                
                                <?php if (!empty($requisitos)): ?>
                                <div class="mt-2">
                                    <small class="text-muted"><strong>Requisitos:</strong></small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php foreach ($requisitos as $requisito): ?>
                                        <span class="badge badge-<?php echo isset($requisitosCumplidos[$requisito]) && $requisitosCumplidos[$requisito] ? 'success' : 'secondary'; ?>">
                                            <i class="fas fa-<?php echo isset($requisitosCumplidos[$requisito]) && $requisitosCumplidos[$requisito] ? 'check' : 'times'; ?> me-1"></i>
                                            <?php echo $this->getNombreRequisito($requisito); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Observaciones -->
                                <?php if (!empty($actividad['observaciones'])): ?>
                                <div class="mt-2">
                                    <small class="text-muted"><strong>Observaciones:</strong></small>
                                    <p class="small mb-0"><?php echo nl2br(htmlspecialchars($actividad['observaciones'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Fechas -->
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <?php if ($actividad['fecha_inicio']): ?>
                                        Inicio: <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_inicio'])); ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($actividad['fecha_fin']): ?>
                                        | Fin: <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_fin'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="mb-2">
                                    <?php echo $this->getBadgeEstadoActividad($actividad['estado']); ?>
                                </div>
                                
                                <?php if ($actividad['salto_condicional']): ?>
                                <span class="badge badge-info">
                                    <i class="fas fa-fast-forward me-1"></i>Salto Condicional
                                </span>
                                <?php endif; ?>
                                
                                <!-- Botones de Acción -->
                                <div class="mt-2">
                                    <?php if ($actividad['estado'] === 'en_proceso' && 
                                            $this->puedeEditarActividad($actividad, $user)): ?>
                                    <a href="<?php echo SITE_URL; ?>/actividades/editar/<?php echo $actividad['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i>Editar
                                    </a>
                                    <?php elseif ($actividad['estado'] === 'pendiente'): ?>
                                    <span class="text-muted small">
                                        <i class="fas fa-lock me-1"></i>Actividad anterior pendiente
                                    </span>
                                    <?php elseif ($actividad['estado'] === 'no_aplica'): ?>
                                    <span class="text-muted small">
                                        <i class="fas fa-ban me-1"></i>No aplica por salto condicional
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Evidencias -->
                        <?php 
                        $evidenciasActividad = array_filter($evidencias, function($ev) use ($actividad) {
                            return $ev['actividad_id'] == $actividad['id'];
                        });
                        ?>
                        
                        <?php if (!empty($evidenciasActividad)): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <small class="text-muted"><strong>Evidencias:</strong></small>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <?php foreach ($evidenciasActividad as $evidencia): ?>
                                    <a href="<?php echo SITE_URL . $evidencia['ruta']; ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-file-<?php echo $this->getIconoTipoArchivo($evidencia['tipo_archivo']); ?> me-1"></i>
                                        <?php echo htmlspecialchars($evidencia['nombre_original']); ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Historial de Movimientos -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Historial de Movimientos</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Actividad</th>
                                <th>Acción</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $movimiento): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($movimiento['usuario_nombre']); ?></td>
                                <td>
                                    <?php if ($movimiento['numero_paso'] > 0): ?>
                                    Paso <?php echo $movimiento['numero_paso']; ?> - <?php echo htmlspecialchars($movimiento['actividad_nombre']); ?>
                                    <?php else: ?>
                                    Requerimiento
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($movimiento['accion']); ?></span>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars($movimiento['observaciones'] ?? '')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($historial)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    No hay movimientos registrados
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Error de Requisitos -->
<div class="modal fade" id="errorRequisitosModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Requisitos Pendientes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>No puede finalizar la actividad sin cumplir todos los requisitos obligatorios.</p>
                <p class="mb-0">Por favor, verifique que todos los requisitos estén marcados como cumplidos antes de finalizar.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 60px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 25px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #dee2e6;
}

.timeline-item.en_proceso {
    border-left-color: #4e73df;
    background: #f8f9fe;
}

.timeline-item.finalizado {
    border-left-color: #1cc88a;
    background: #f0f9f4;
}

.timeline-item.rechazado {
    border-left-color: #e74a3b;
    background: #fdf4f4;
}

.timeline-item.no_aplica {
    border-left-color: #6c757d;
    background: #f8f9fa;
    opacity: 0.7;
}

.timeline-step {
    position: absolute;
    left: -45px;
    top: 20px;
}

.timeline-step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}

.timeline-step-icon.pendiente { background: #6c757d; }
.timeline-step-icon.en_proceso { background: #4e73df; }
.timeline-step-icon.finalizado { background: #1cc88a; }
.timeline-step-icon.rechazado { background: #e74a3b; }
.timeline-step-icon.no_aplica { background: #6c757d; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>