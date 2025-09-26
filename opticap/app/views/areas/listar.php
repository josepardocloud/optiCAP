<?php
$pageTitle = "Gestión de Áreas";
require_once 'app/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gestión de Áreas y Oficinas</h1>
    <a href="<?php echo BASE_URL; ?>areas/crear" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nueva Área
    </a>
</div>

<div class="row">
    <?php foreach ($datos['areas'] as $area): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title"><?php echo $area['nombre']; ?></h5>
                    <span class="badge <?php echo $area['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo $area['activo'] ? 'Activa' : 'Inactiva'; ?>
                    </span>
                </div>
                
                <?php if ($area['descripcion']): ?>
                <p class="card-text text-muted small"><?php echo $area['descripcion']; ?></p>
                <?php else: ?>
                <p class="card-text text-muted small"><em>Sin descripción</em></p>
                <?php endif; ?>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Creada: <?php echo date('d/m/Y', strtotime($area['fecha_creacion'])); ?>
                    </small>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="btn-group w-100">
                    <a href="<?php echo BASE_URL; ?>areas/editar/<?php echo $area['id']; ?>" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    <?php if ($area['activo']): ?>
                    <button type="button" class="btn btn-outline-warning btn-sm" 
                            onclick="cambiarEstadoArea(<?php echo $area['id']; ?>, 0)">
                        <i class="fas fa-times me-1"></i>Desactivar
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-outline-success btn-sm" 
                            onclick="cambiarEstadoArea(<?php echo $area['id']; ?>, 1)">
                        <i class="fas fa-check me-1"></i>Activar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($datos['areas'])): ?>
<div class="text-center py-5">
    <i class="fas fa-building fa-3x text-muted mb-3"></i>
    <h4 class="text-muted">No hay áreas registradas</h4>
    <p class="text-muted">Comience creando la primera área del sistema.</p>
    <a href="<?php echo BASE_URL; ?>areas/crear" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Crear Primera Área
    </a>
</div>
<?php endif; ?>

<script>
function cambiarEstadoArea(areaId, nuevoEstado) {
    if (confirm(`¿Está seguro de ${nuevoEstado ? 'activar' : 'desactivar'} esta área?`)) {
        const formData = new FormData();
        formData.append('estado', nuevoEstado);
        
        fetch('<?php echo BASE_URL; ?>api/areas/cambiarEstado/' + areaId, {
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

<?php require_once 'app/views/layouts/footer.php'; ?>