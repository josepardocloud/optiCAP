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
                <li class="breadcrumb-item active">Editar Actividad</li>
            </ol>
        </nav>
        
        <h1 class="h3 mb-0">Editar Actividad</h1>
        <p class="text-muted">Paso <?php echo $actividad['numero_paso']; ?> - <?php echo htmlspecialchars($actividad['nombre']); ?></p>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información de la Actividad</h6>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-control" required>
                                <option value="en_proceso" 
                                    <?php echo $actividad['estado'] === 'en_proceso' ? 'selected' : ''; ?>>
                                    En Proceso
                                </option>
                                <option value="finalizado" 
                                    <?php echo $actividad['estado'] === 'finalizado' ? 'selected' : ''; ?>>
                                    Finalizado
                                </option>
                                <option value="rechazado" 
                                    <?php echo $actividad['estado'] === 'rechazado' ? 'selected' : ''; ?>>
                                    Rechazado
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo $actividad['fecha_inicio'] ? date('d/m/Y H:i', strtotime($actividad['fecha_inicio'])) : 'No iniciada'; ?>" 
                                   readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                    </div>
                    
                    <!-- Requisitos Obligatorios -->
                    <?php 
                    $requisitos = json_decode($actividad['requisitos_obligatorios'], true);
                    $requisitosCumplidos = $actividad['requisitos_cumplidos'] ? 
                        json_decode($actividad['requisitos_cumplidos'], true) : [];
                    ?>
                    
                    <?php if (!empty($requisitos)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Requisitos Obligatorios</label>
                        <div class="border rounded p-3 bg-light">
                            <?php foreach ($requisitos as $requisito): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" 
                                       name="requisitos[<?php echo $requisito; ?>]" 
                                       id="req_<?php echo $requisito; ?>"
                                       value="1"
                                       <?php echo isset($requisitosCumplidos[$requisito]) && $requisitosCumplidos[$requisito] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="req_<?php echo $requisito; ?>">
                                    <?php echo $this->getNombreRequisito($requisito); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Todos los requisitos deben estar cumplidos para finalizar la actividad
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="4" 
                                  placeholder="Ingrese observaciones sobre el progreso de la actividad..."><?php echo htmlspecialchars($actividad['observaciones'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Evidencias -->
                    <div class="mb-3">
                        <label class="form-label">Evidencias Documentales</label>
                        <input type="file" name="evidencias[]" class="form-control" multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        <small class="text-muted">
                            Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Tamaño máximo: 10MB por archivo.
                        </small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo SITE_URL; ?>/requerimientos/detalle/<?php echo $requerimiento['id']; ?>" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Detalle
                        </a>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Información del Requerimiento -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Requerimiento</h6>
            </div>
            <div class="card-body">
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
                        <?php echo $this->getBadgeEstado($requerimiento['estado_general']); ?>
                    </dd>
                    
                    <dt class="col-sm-4">Progreso</dt>
                    <dd class="col-sm-8">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $requerimiento['progreso']; ?>%;"
                                 aria-valuenow="<?php echo $requerimiento['progreso']; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted"><?php echo number_format($requerimiento['progreso'], 1); ?>%</small>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Evidencias Existentes -->
        <?php if (!empty($evidencias)): ?>
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Evidencias Existentes</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($evidencias as $evidencia): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-<?php echo $this->getIconoTipoArchivo($evidencia['tipo_archivo']); ?> me-2 text-muted"></i>
                            <small><?php echo htmlspecialchars($evidencia['nombre_original']); ?></small>
                        </div>
                        <div>
                            <a href="<?php echo SITE_URL . $evidencia['ruta']; ?>" 
                               target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar requisitos antes de finalizar
    const estadoSelect = document.querySelector('select[name="estado"]');
    const requisitosCheckboxes = document.querySelectorAll('input[name^="requisitos"]');
    
    estadoSelect.addEventListener('change', function() {
        if (this.value === 'finalizado') {
            const todosCumplidos = Array.from(requisitosCheckboxes).every(cb => cb.checked);
            if (!todosCumplidos) {
                alert('Para finalizar la actividad, todos los requisitos obligatorios deben estar cumplidos.');
                this.value = 'en_proceso';
            }
        }
    });
    
    // Validar formulario antes de enviar
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (estadoSelect.value === 'finalizado') {
            const todosCumplidos = Array.from(requisitosCheckboxes).every(cb => cb.checked);
            if (!todosCumplidos) {
                e.preventDefault();
                alert('No puede finalizar la actividad sin cumplir todos los requisitos obligatorios.');
                return false;
            }
        }
    });
});
</script>