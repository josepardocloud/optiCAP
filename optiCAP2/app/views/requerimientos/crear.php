<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/requerimientos">Requerimientos</a>
                </li>
                <li class="breadcrumb-item active">Crear Nuevo</li>
            </ol>
        </nav>
        
        <h1 class="h3 mb-0">Crear Nuevo Requerimiento</h1>
        <p class="text-muted">Complete la información para crear un nuevo requerimiento de adquisición</p>
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
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Requerimiento</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo_proceso_id" class="form-label">
                                    <i class="fas fa-sitemap me-2"></i>Tipo de Proceso
                                </label>
                                <select name="tipo_proceso_id" id="tipo_proceso_id" class="form-control" required>
                                    <option value="">Seleccione un tipo</option>
                                    <?php foreach ($tiposProceso as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>" 
                                            <?php echo ($_POST['tipo_proceso_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">
                                    Cada tipo tiene 14 actividades específicas del proceso
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="area_id" class="form-label">
                                    <i class="fas fa-building me-2"></i>Área Solicitante
                                </label>
                                <select name="area_id" id="area_id" class="form-control" 
                                    <?php echo $user['rol_nombre'] === 'Usuario' ? 'disabled' : 'required'; ?>>
                                    <option value="">Seleccione un área</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>" 
                                            <?php echo (($user['rol_nombre'] === 'Usuario' && $area['id'] == $user['area_id']) || 
                                                      ($_POST['area_id'] ?? '') == $area['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($area['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($user['rol_nombre'] === 'Usuario'): ?>
                                <input type="hidden" name="area_id" value="<?php echo $user['area_id']; ?>">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    El área se asigna automáticamente según su perfil
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo" class="form-label">
                            <i class="fas fa-file-alt me-2"></i>Motivo del Requerimiento
                        </label>
                        <textarea name="motivo" id="motivo" class="form-control" rows="5" 
                                  placeholder="Describa detalladamente el motivo del requerimiento..." 
                                  required><?php echo htmlspecialchars($_POST['motivo'] ?? ''); ?></textarea>
                        <small class="text-muted">
                            Incluya información relevante como: bienes o servicios requeridos, justificación, urgencia, etc.
                        </small>
                    </div>
                    
                    <!-- Información de Procesos -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>Información del Proceso
                                </h6>
                                <p class="mb-2">Al crear el requerimiento, el sistema automáticamente:</p>
                                <ul class="mb-0">
                                    <li>Generará un código único (BIEN-AAAA-NNNN / SERV-AAAA-NNNN)</li>
                                    <li>Asignará las 14 actividades del proceso correspondiente</li>
                                    <li>Habilitará la Actividad 01 (Paso 01) inicialmente</li>
                                    <li>Aplicará la lógica condicional del Paso 01</li>
                                    <li>Notificará a los responsables de cada actividad</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo SITE_URL; ?>/requerimientos" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Requerimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Información de Tipos de Proceso -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tipos de Proceso Disponibles</h6>
            </div>
            <div class="card-body">
                <?php foreach ($tiposProceso as $tipo): ?>
                <div class="mb-3 p-3 border rounded">
                    <h6 class="mb-2">
                        <span class="badge badge-<?php echo $tipo['nombre'] == 'Bienes' ? 'primary' : 'success'; ?> me-2">
                            <?php echo htmlspecialchars($tipo['codigo']); ?>
                        </span>
                        <?php echo htmlspecialchars($tipo['nombre']); ?>
                    </h6>
                    <p class="small text-muted mb-2"><?php echo htmlspecialchars($tipo['descripcion']); ?></p>
                    <div class="small">
                        <i class="fas fa-list-ol me-1"></i>14 actividades secuenciales
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recordatorios Importantes -->
        <div class="card shadow">
            <div class="card-header py-3 bg-warning">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-exclamation-triangle me-2"></i>Recordatorios Importantes
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-2">
                        <strong>Lógica Condicional (Paso 01):</strong>
                        <ul class="mt-1 mb-0">
                            <li>Si el bien/servicio existe en el cuadro multianual, se salta a Paso 05</li>
                            <li>Si no existe, se sigue la secuencia normal</li>
                        </ul>
                    </div>
                    <div class="mb-2">
                        <strong>Requisitos Obligatorios:</strong>
                        <ul class="mt-1 mb-0">
                            <li>Paso 02: Disponibilidad presupuestal</li>
                            <li>Paso 05: Especificaciones técnicas / Términos de referencia</li>
                            <li>Paso 06: PCA y Priorización</li>
                            <li>Paso 12/13: Conformidad de recepción/servicio</li>
                            <li>Paso 14: Informe de conformidad y documentación</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Secuencia Estricta:</strong>
                        <p class="mb-0 mt-1">Cada actividad requiere que la anterior esté finalizada</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoProcesoSelect = document.getElementById('tipo_proceso_id');
    const motivoTextarea = document.getElementById('motivo');
    
    // Auto-focus en el primer campo
    tipoProcesoSelect.focus();
    
    // Validación en tiempo real
    tipoProcesoSelect.addEventListener('change', function() {
        validarFormulario();
    });
    
    motivoTextarea.addEventListener('input', function() {
        validarFormulario();
    });
    
    function validarFormulario() {
        const tipoValido = tipoProcesoSelect.value !== '';
        const motivoValido = motivoTextarea.value.trim().length >= 10;
        
        if (tipoValido && motivoValido) {
            document.querySelector('button[type="submit"]').disabled = false;
        } else {
            document.querySelector('button[type="submit"]').disabled = true;
        }
    }
    
    // Inicializar validación
    validarFormulario();
});
</script>