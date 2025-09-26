<?php
$pageTitle = "Gestión de Actividades";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gestión de Actividades del Proceso</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>actividades/crear" class="btn btn-primary me-2">
            <i class="fas fa-plus me-2"></i>Nueva Actividad
        </a>
        <button type="button" class="btn btn-outline-secondary" onclick="habilitarReordenamiento()">
            <i class="fas fa-sort me-2"></i>Reordenar
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Flujo del Proceso de Abastecimiento</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($datos['actividades'])): ?>
        <div class="process-flow">
            <?php foreach ($datos['actividades'] as $index => $actividad): ?>
            <div class="process-step <?php echo !$actividad['activo'] ? 'inactive' : ''; ?>" 
                 data-actividad-id="<?php echo $actividad['id']; ?>">
                <div class="step-number"><?php echo $index + 1; ?></div>
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
                <div class="step-actions">
                    <a href="<?php echo BASE_URL; ?>actividades/editar/<?php echo $actividad['id']; ?>" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                    </a>
                    <?php if ($actividad['activo']): ?>
                    <button type="button" class="btn btn-sm btn-outline-warning" 
                            onclick="cambiarEstadoActividad(<?php echo $actividad['id']; ?>, 0)">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-success" 
                            onclick="cambiarEstadoActividad(<?php echo $actividad['id']; ?>, 1)">
                        <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($index < count($datos['actividades']) - 1): ?>
            <div class="process-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
            <?php endif; ?>
            
            <?php endforeach; ?>
        </div>

        <!-- Tabla para reordenamiento -->
        <form method="POST" action="<?php echo BASE_URL; ?>actividades/reordenar" id="formReordenar" style="display: none;">
            <div class="table-responsive mt-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Actividad</th>
                            <th>Tiempo Límite</th>
                            <th>Nuevo Orden</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyReordenar">
                        <?php foreach ($datos['actividades'] as $actividad): ?>
                        <tr>
                            <td><?php echo $actividad['orden']; ?></td>
                            <td><?php echo $actividad['nombre']; ?></td>
                            <td><?php echo $actividad['tiempo_limite']; ?> días</td>
                            <td>
                                <input type="number" name="orden[<?php echo $actividad['id']; ?>]" 
                                       value="<?php echo $actividad['orden']; ?>" 
                                       class="form-control form-control-sm" min="1" 
                                       max="<?php echo count($datos['actividades']); ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save me-1"></i>Guardar Orden
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelarReordenamiento()">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
            </div>
        </form>

        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No hay actividades configuradas</h4>
            <p class="text-muted">Comience creando la primera actividad del proceso.</p>
            <a href="<?php echo BASE_URL; ?>actividades/crear" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Crear Primera Actividad
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
let reordenamientoHabilitado = false;

function habilitarReordenamiento() {
    reordenamientoHabilitado = true;
    document.getElementById('formReordenar').style.display = 'block';
    document.querySelector('.process-flow').style.display = 'none';
    document.querySelector('.card-header h5').textContent = 'Reordenar Actividades';
}

function cancelarReordenamiento() {
    reordenamientoHabilitado = false;
    document.getElementById('formReordenar').style.display = 'none';
    document.querySelector('.process-flow').style.display = 'flex';
    document.querySelector('.card-header h5').textContent = 'Flujo del Proceso de Abastecimiento';
}

function cambiarEstadoActividad(actividadId, nuevoEstado) {
    if (confirm(`¿Está seguro de ${nuevoEstado ? 'activar' : 'desactivar'} esta actividad?`)) {
        const formData = new FormData();
        formData.append('estado', nuevoEstado);
        
        fetch('<?php echo BASE_URL; ?>api/actividades/cambiarEstado/' + actividadId, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error al cambiar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cambiar el estado');
        });
    }
}
</script>

<style>
.process-flow {
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
    position: relative;
}
.process-step.inactive {
    opacity: 0.6;
    background: #e9ecef;
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
    flex-shrink: 0;
}
.step-content {
    flex-grow: 1;
}
.step-content h6 {
    margin-bottom: 5px;
    font-size: 0.9rem;
}
.step-meta {
    margin-top: 5px;
}
.step-actions {
    margin-left: 10px;
    display: flex;
    gap: 5px;
}
.process-arrow {
    color: #6c757d;
    font-size: 1.5rem;
}
@media (max-width: 768px) {
    .process-flow {
        flex-direction: column;
    }
    .process-arrow {
        transform: rotate(90deg);
        margin: 10px 0;
    }
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>