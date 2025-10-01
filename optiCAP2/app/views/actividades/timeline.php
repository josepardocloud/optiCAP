<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/requerimientos">Requerimientos</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/requerimientos/detalle/<?php echo $requerimiento['id']; ?>">
                        <?php echo htmlspecialchars($requerimiento['codigo']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active">Línea de Tiempo</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Línea de Tiempo</h1>
                <p class="text-muted">Visualización gráfica del progreso del requerimiento</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/requerimientos/detalle/<?php echo $requerimiento['id']; ?>" 
                   class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Detalle
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Progreso del Requerimiento: <?php echo htmlspecialchars($requerimiento['codigo']); ?>
                </h6>
            </div>
            <div class="card-body">
                <!-- Timeline Vertical -->
                <div class="timeline-vertical">
                    <?php foreach ($actividades as $index => $actividad): ?>
                    <div class="timeline-item-vertical <?php echo $actividad['estado']; ?>" 
                         data-activity-id="<?php echo $actividad['id']; ?>">
                        
                        <div class="timeline-marker">
                            <div class="timeline-marker-icon">
                                <?php if ($actividad['estado'] === 'finalizado'): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php elseif ($actividad['estado'] === 'en_proceso'): ?>
                                    <i class="fas fa-play-circle"></i>
                                <?php elseif ($actividad['estado'] === 'pendiente'): ?>
                                    <i class="fas fa-clock"></i>
                                <?php elseif ($actividad['estado'] === 'rechazado'): ?>
                                    <i class="fas fa-times-circle"></i>
                                <?php elseif ($actividad['estado'] === 'no_aplica'): ?>
                                    <i class="fas fa-ban"></i>
                                <?php endif; ?>
                            </div>
                            <?php if ($index < count($actividades) - 1): ?>
                            <div class="timeline-connector"></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h5 class="mb-1">
                                    Paso <?php echo $actividad['numero_paso']; ?>: 
                                    <?php echo htmlspecialchars($actividad['nombre']); ?>
                                </h5>
                                <div class="timeline-status">
                                    <?php echo Helpers::getBadgeEstadoActividad($actividad['estado']); ?>
                                    
                                    <?php if ($actividad['salto_condicional']): ?>
                                    <span class="badge badge-info ms-2">
                                        <i class="fas fa-fast-forward me-1"></i>Salto Condicional
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="timeline-body">
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                
                                <!-- Información de Fechas -->
                                <div class="timeline-dates">
                                    <?php if ($actividad['fecha_inicio']): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-play me-1"></i>
                                        Inicio: <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_inicio'])); ?>
                                    </small>
                                    <?php endif; ?>
                                    
                                    <?php if ($actividad['fecha_fin']): ?>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-flag-checkered me-1"></i>
                                        Fin: <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_fin'])); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Requisitos -->
                                <?php 
                                $requisitos = json_decode($actividad['requisitos_obligatorios'], true);
                                $requisitosCumplidos = $actividad['requisitos_cumplidos'] ? 
                                    json_decode($actividad['requisitos_cumplidos'], true) : [];
                                ?>
                                
                                <?php if (!empty($requisitos)): ?>
                                <div class="timeline-requirements mt-2">
                                    <small class="text-muted d-block mb-1"><strong>Requisitos:</strong></small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($requisitos as $requisito): ?>
                                        <span class="badge badge-<?php echo isset($requisitosCumplidos[$requisito]) && $requisitosCumplidos[$requisito] ? 'success' : 'secondary'; ?>">
                                            <i class="fas fa-<?php echo isset($requisitosCumplidos[$requisito]) && $requisitosCumplidos[$requisito] ? 'check' : 'times'; ?> me-1"></i>
                                            <?php echo Helpers::getNombreRequisito($requisito); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Observaciones -->
                                <?php if (!empty($actividad['observaciones'])): ?>
                                <div class="timeline-observations mt-2">
                                    <small class="text-muted d-block mb-1"><strong>Observaciones:</strong></small>
                                    <p class="small mb-0"><?php echo nl2br(htmlspecialchars($actividad['observaciones'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Acciones -->
                                <div class="timeline-actions mt-3">
                                    <?php if ($actividad['estado'] === 'en_proceso' && 
                                            Helpers::puedeEditarActividad($actividad, $user)): ?>
                                    <a href="<?php echo SITE_URL; ?>/actividades/editar/<?php echo $actividad['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i>Editar Actividad
                                    </a>
                                    <?php elseif ($actividad['estado'] === 'pendiente'): ?>
                                    <span class="text-muted small">
                                        <i class="fas fa-lock me-1"></i>Esperando actividad anterior
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Resumen del Progreso -->
                <div class="row mt-5">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Resumen del Progreso</h6>
                                <div class="progress-summary">
                                    <?php
                                    $actividadesCompletadas = array_filter($actividades, function($a) {
                                        return $a['estado'] === 'finalizado';
                                    });
                                    $actividadesEnProceso = array_filter($actividades, function($a) {
                                        return $a['estado'] === 'en_proceso';
                                    });
                                    $actividadesNoAplica = array_filter($actividades, function($a) {
                                        return $a['estado'] === 'no_aplica';
                                    });
                                    $actividadesAplicables = count($actividades) - count($actividadesNoAplica);
                                    $porcentajeCompletado = $actividadesAplicables > 0 ? 
                                        (count($actividadesCompletadas) / $actividadesAplicables) * 100 : 0;
                                    ?>
                                    
                                    <div class="mb-3">
                                        <strong>Progreso General:</strong>
                                        <div class="progress mt-2" style="height: 20px;">
                                            <div class="progress-bar 
                                                <?php echo $porcentajeCompletado >= 100 ? 'bg-success' : 
                                                       ($porcentajeCompletado >= 50 ? 'bg-warning' : 'bg-info'); ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo $porcentajeCompletado; ?>%;"
                                                aria-valuenow="<?php echo $porcentajeCompletado; ?>" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                <?php echo number_format($porcentajeCompletado, 1); ?>%
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="stat-box">
                                                <div class="stat-number text-success">
                                                    <?php echo count($actividadesCompletadas); ?>
                                                </div>
                                                <div class="stat-label">Completadas</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-box">
                                                <div class="stat-number text-primary">
                                                    <?php echo count($actividadesEnProceso); ?>
                                                </div>
                                                <div class="stat-label">En Proceso</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-box">
                                                <div class="stat-number text-info">
                                                    <?php echo count($actividadesNoAplica); ?>
                                                </div>
                                                <div class="stat-label">No Aplica</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Información del Requerimiento</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Código</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($requerimiento['codigo']); ?></dd>
                                    
                                    <dt class="col-sm-4">Tipo</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge badge-<?php echo $requerimiento['tipo_proceso_nombre'] == 'Bienes' ? 'primary' : 'success'; ?>">
                                            <?php echo htmlspecialchars($requerimiento['tipo_proceso_nombre']); ?>
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Área</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($requerimiento['area_nombre']); ?></dd>
                                    
                                    <dt class="col-sm-4">Estado</dt>
                                    <dd class="col-sm-8">
                                        <?php echo Helpers::getBadgeEstado($requerimiento['estado_general']); ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Fecha Creación</dt>
                                    <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($requerimiento['fecha_creacion'])); ?></dd>
                                    
                                    <?php if ($requerimiento['fecha_salto_condicional']): ?>
                                    <dt class="col-sm-4">Salto Condicional</dt>
                                    <dd class="col-sm-8">
                                        <?php echo date('d/m/Y H:i', strtotime($requerimiento['fecha_salto_condicional'])); ?>
                                    </dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-vertical {
    position: relative;
    padding-left: 60px;
}

.timeline-item-vertical {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -60px;
    top: 0;
    width: 40px;
    height: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.timeline-marker-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    z-index: 2;
}

.timeline-connector {
    width: 2px;
    flex-grow: 1;
    background: #e9ecef;
    margin-top: 5px;
}

.timeline-content {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.timeline-header h5 {
    flex: 1;
    margin: 0;
}

.timeline-status {
    margin-left: 15px;
}

.timeline-dates {
    margin-bottom: 10px;
}

/* Estados de las actividades */
.timeline-item-vertical.finalizado .timeline-marker-icon {
    background: #d4edda;
    color: #155724;
    border: 2px solid #155724;
}

.timeline-item-vertical.en_proceso .timeline-marker-icon {
    background: #d1ecf1;
    color: #0c5460;
    border: 2px solid #0c5460;
}

.timeline-item-vertical.pendiente .timeline-marker-icon {
    background: #fff3cd;
    color: #856404;
    border: 2px solid #856404;
}

.timeline-item-vertical.rechazado .timeline-marker-icon {
    background: #f8d7da;
    color: #721c24;
    border: 2px solid #721c24;
}

.timeline-item-vertical.no_aplica .timeline-marker-icon {
    background: #e2e3e5;
    color: #383d41;
    border: 2px solid #383d41;
}

/* Estilos para el resumen */
.stat-box {
    padding: 10px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 5px;
}

.progress-summary .progress {
    background-color: #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
    .timeline-vertical {
        padding-left: 40px;
    }
    
    .timeline-marker {
        left: -40px;
        width: 30px;
    }
    
    .timeline-marker-icon {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
    
    .timeline-header {
        flex-direction: column;
    }
    
    .timeline-status {
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animación suave al hacer scroll a una actividad
    const timelineItems = document.querySelectorAll('.timeline-item-vertical');
    
    timelineItems.forEach(item => {
        item.addEventListener('click', function() {
            const activityId = this.getAttribute('data-activity-id');
            // Aquí puedes agregar funcionalidad para mostrar detalles de la actividad
            console.log('Actividad clickeada:', activityId);
        });
    });
    
    // Resaltar actividades en proceso
    const activitiesInProcess = document.querySelectorAll('.timeline-item-vertical.en_proceso');
    activitiesInProcess.forEach(activity => {
        activity.style.boxShadow = '0 0 0 2px #4e73df';
    });
});
</script>