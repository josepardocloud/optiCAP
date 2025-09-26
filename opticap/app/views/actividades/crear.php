<?php
$pageTitle = "Crear Nueva Actividad";
require_once 'app/views/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Crear Nueva Actividad del Proceso
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre" class="form-label">Nombre de la Actividad *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo $_POST['nombre'] ?? ''; ?>" required maxlength="100">
                            <div class="invalid-feedback">Por favor ingrese el nombre de la actividad.</div>
                            <div class="form-text">
                                Use un nombre claro y descriptivo para la actividad.
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                      maxlength="500" placeholder="Describa en detalle en qué consiste esta actividad..."><?php echo $_POST['descripcion'] ?? ''; ?></textarea>
                            <div class="form-text">
                                <span id="contadorCaracteres">0</span> / 500 caracteres
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tiempo_limite" class="form-label">Tiempo Límite (días) *</label>
                            <input type="number" class="form-control" id="tiempo_limite" name="tiempo_limite" 
                                   value="<?php echo $_POST['tiempo_limite'] ?? '1'; ?>" min="1" max="365" required>
                            <div class="invalid-feedback">El tiempo límite debe ser entre 1 y 365 días.</div>
                            <div class="form-text">
                                Tiempo máximo en días para completar esta actividad.
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="orden" class="form-label">Orden en el Proceso *</label>
                            <input type="number" class="form-control" id="orden" name="orden" 
                                   value="<?php echo $_POST['orden'] ?? $datos['siguienteOrden']; ?>" 
                                   min="1" max="20" required>
                            <div class="invalid-feedback">El orden debe ser un número entre 1 y 20.</div>
                            <div class="form-text">
                                Posición de esta actividad en el flujo del proceso.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Consideraciones Importantes</h6>
                                <ul class="mb-0 small">
                                    <li>El tiempo límite se usa para calcular fechas estimadas de finalización</li>
                                    <li>El orden determina la secuencia de actividades en el proceso</li>
                                    <li>Las actividades se pueden reordenar posteriormente si es necesario</li>
                                    <li>Los cambios afectarán a los requerimientos creados después de la modificación</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>actividades" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Actividad
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Vista Previa del Flujo -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-project-diagram me-2"></i>Vista Previa del Flujo del Proceso
                </h5>
            </div>
            <div class="card-body">
                <?php
                $actividadModel = new Actividad();
                $actividades = $actividadModel->obtenerTodas();
                $nuevoOrden = $_POST['orden'] ?? $datos['siguienteOrden'];
                ?>
                
                <div class="process-preview">
                    <?php
                    $mostrarNueva = false;
                    foreach ($actividades as $index => $actividad):
                        if ($actividad['orden'] >= $nuevoOrden && !$mostrarNueva):
                            $mostrarNueva = true;
                    ?>
                    <div class="process-step new-activity">
                        <div class="step-number"><?php echo $nuevoOrden; ?></div>
                        <div class="step-content">
                            <h6><?php echo $_POST['nombre'] ?? 'Nueva Actividad'; ?></h6>
                            <p class="small text-muted mb-1"><?php echo $_POST['descripcion'] ?? 'Descripción de la nueva actividad'; ?></p>
                            <div class="step-meta">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo ($_POST['tiempo_limite'] ?? '1'); ?> días
                                </span>
                                <span class="badge bg-warning ms-1">Nueva</span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($index < count($actividades) - 1): ?>
                    <div class="process-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                    
                    <div class="process-step <?php echo !$actividad['activo'] ? 'inactive' : ''; ?>">
                        <div class="step-number"><?php echo $actividad['orden']; ?></div>
                        <div class="step-content">
                            <h6><?php echo $actividad['nombre']; ?></h6>
                            <p class="small text-muted mb-1"><?php echo $actividad['descripcion']; ?></p>
                            <div class="step-meta">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-clock me-1"></i><?php echo $actividad['tiempo_limite']; ?> días
                                </span>
                                <?php if (!$actividad['activo']): ?>
                                <span class="badge bg-secondary ms-1">Inactiva</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($index < count($actividades) - 1): ?>
                    <div class="process-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <?php endif; ?>
                    
                    <?php endforeach; ?>
                    
                    <?php if (!$mostrarNueva && $nuevoOrden > count($actividades)): ?>
                    <div class="process-step new-activity">
                        <div class="step-number"><?php echo $nuevoOrden; ?></div>
                        <div class="step-content">
                            <h6><?php echo $_POST['nombre'] ?? 'Nueva Actividad'; ?></h6>
                            <p class="small text-muted mb-1"><?php echo $_POST['descripcion'] ?? 'Descripción de la nueva actividad'; ?></p>
                            <div class="step-meta">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo ($_POST['tiempo_limite'] ?? '1'); ?> días
                                </span>
                                <span class="badge bg-warning ms-1">Nueva</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contador de caracteres
    const descripcion = document.getElementById('descripcion');
    const contador = document.getElementById('contadorCaracteres');
    
    descripcion.addEventListener('input', function() {
        contador.textContent = this.value.length;
    });
    
    // Inicializar contador
    contador.textContent = descripcion.value.length;
    
    // Actualizar vista previa en tiempo real
    const inputs = ['nombre', 'descripcion', 'tiempo_limite', 'orden'];
    inputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', actualizarVistaPrevia);
        }
    });
    
    function actualizarVistaPrevia() {
        // Esta función actualizaría la vista previa en tiempo real
        console.log('Actualizando vista previa...');
    }
});
</script>

<style>
.process-preview {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}
.process-step {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    padding: 15px;
    min-width: 250px;
}
.process-step.inactive {
    opacity: 0.6;
    background: #e9ecef;
}
.process-step.new-activity {
    border-color: #ffc107;
    background: #fff3cd;
}
.step-number {
    background: #007bff;
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
}
.process-step.new-activity .step-number {
    background: #ffc107;
}
.step-content h6 {
    margin-bottom: 5px;
    font-size: 0.9rem;
}
.step-meta {
    margin-top: 5px;
}
.process-arrow {
    color: #6c757d;
    font-size: 1.5rem;
}
@media (max-width: 768px) {
    .process-preview {
        flex-direction: column;
    }
    .process-arrow {
        transform: rotate(90deg);
        margin: 10px 0;
    }
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>