<?php
$pageTitle = "Editar Actividad";
require_once 'app/views/layouts/header.php';

$actividad = $datos['actividad'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Editar Actividad: <?php echo $actividad['nombre']; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre" class="form-label">Nombre de la Actividad *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($actividad['nombre']); ?>" required maxlength="100">
                            <div class="invalid-feedback">Por favor ingrese el nombre de la actividad.</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                      maxlength="500" placeholder="Describa en detalle en qué consiste esta actividad..."><?php echo htmlspecialchars($actividad['descripcion']); ?></textarea>
                            <div class="form-text">
                                <span id="contadorCaracteres">0</span> / 500 caracteres
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tiempo_limite" class="form-label">Tiempo Límite (días) *</label>
                            <input type="number" class="form-control" id="tiempo_limite" name="tiempo_limite" 
                                   value="<?php echo $actividad['tiempo_limite']; ?>" min="1" max="365" required>
                            <div class="invalid-feedback">El tiempo límite debe ser entre 1 y 365 días.</div>
                            <div class="form-text">
                                Tiempo máximo en días para completar esta actividad.
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="orden" class="form-label">Orden en el Proceso *</label>
                            <input type="number" class="form-control" id="orden" name="orden" 
                                   value="<?php echo $actividad['orden']; ?>" min="1" max="20" required>
                            <div class="invalid-feedback">El orden debe ser un número entre 1 y 20.</div>
                            <div class="form-text">
                                Posición de esta actividad en el flujo del proceso.
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                       <?php echo $actividad['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Actividad activa en el sistema
                                </label>
                            </div>
                            <div class="form-text">
                                Las actividades inactivas no estarán disponibles para nuevos requerimientos.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Información de la Actividad
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Fecha de creación:</small><br>
                                            <strong><?php echo date('d/m/Y H:i', strtotime($actividad['fecha_creacion'])); ?></strong>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Última actualización:</small><br>
                                            <strong><?php echo date('d/m/Y H:i', strtotime($actividad['fecha_actualizacion'] ?? $actividad['fecha_creacion'])); ?></strong>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <small class="text-muted">Estadísticas de uso:</small><br>
                                            <?php
                                            $seguimientoModel = new Seguimiento();
                                            // Simulación de estadísticas
                                            $totalUsos = 150;
                                            $completados = 120;
                                            $porcentajeCompletados = $totalUsos > 0 ? round(($completados / $totalUsos) * 100) : 0;
                                            ?>
                                            <span class="badge bg-primary"><?php echo $totalUsos; ?> usos</span>
                                            <span class="badge bg-success"><?php echo $porcentajeCompletados; ?>% completados</span>
                                            <span class="badge bg-info">Tiempo avg: 2.3 días</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>actividades" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-warning me-2" 
                                            onclick="probarActividad()">
                                        <i class="fas fa-test-tube me-2"></i>Probar Flujo
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Impacto de los Cambios -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Impacto de los Cambios
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">Consideraciones importantes al modificar una actividad:</h6>
                    <ul class="mb-0">
                        <li><strong>Requerimientos existentes:</strong> Los cambios no afectarán a los requerimientos ya creados</li>
                        <li><strong>Nuevos requerimientos:</strong> Los cambios se aplicarán a todos los requerimientos creados después de la modificación</li>
                        <li><strong>Tiempo límite:</strong> Modificar el tiempo límite afectará las fechas estimadas de nuevos requerimientos</li>
                        <li><strong>Orden:</strong> Cambiar el orden puede alterar la secuencia del proceso para nuevos requerimientos</li>
                    </ul>
                </div>
                
                <div class="mt-3">
                    <h6>Requerimientos afectados por esta actividad:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-success">Completados</span></td>
                                    <td>120</td>
                                    <td>80%</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">En Proceso</span></td>
                                    <td>20</td>
                                    <td>13.3%</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning">Pendientes</span></td>
                                    <td>10</td>
                                    <td>6.7%</td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td><strong>150</strong></td>
                                    <td><strong>100%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista Previa del Flujo Actualizado -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-project-diagram me-2"></i>Vista Previa del Flujo Actualizado
                </h5>
            </div>
            <div class="card-body">
                <?php
                $actividadModel = new Actividad();
                $actividades = $actividadModel->obtenerTodas();
                ?>
                
                <div class="process-preview">
                    <?php foreach ($actividades as $index => $act): ?>
                    <div class="process-step <?php echo !$act['activo'] ? 'inactive' : ''; ?> 
                         <?php echo $act['id'] == $actividad['id'] ? 'current-activity' : ''; ?>">
                        <div class="step-number"><?php echo $act['orden']; ?></div>
                        <div class="step-content">
                            <h6>
                                <?php echo $act['nombre']; ?>
                                <?php if ($act['id'] == $actividad['id']): ?>
                                <span class="badge bg-warning">Editando</span>
                                <?php endif; ?>
                            </h6>
                            <p class="small text-muted mb-1"><?php echo $act['descripcion']; ?></p>
                            <div class="step-meta">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo $act['id'] == $actividad['id'] ? 
                                        ($_POST['tiempo_limite'] ?? $actividad['tiempo_limite']) : 
                                        $act['tiempo_limite']; ?> días
                                </span>
                                <?php if (!$act['activo']): ?>
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
});

function actualizarVistaPrevia() {
    // Esta función actualizaría la vista previa en tiempo real
    console.log('Actualizando vista previa...');
    // En una implementación real, aquí se actualizarían los elementos de la vista previa
}

function probarActividad() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Probando...';
    btn.disabled = true;
    
    // Simular prueba del flujo
    setTimeout(() => {
        alert('Flujo probado correctamente. La actividad se integra adecuadamente en el proceso.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 2000);
}
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
.process-step.current-activity {
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
.process-step.current-activity .step-number {
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