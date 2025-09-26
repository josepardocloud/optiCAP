<?php
$pageTitle = "Crear Nueva Área";
require_once 'app/views/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Crear Nueva Área/Oficina
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre" class="form-label">Nombre del Área *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo $_POST['nombre'] ?? ''; ?>" required maxlength="100">
                            <div class="invalid-feedback">Por favor ingrese el nombre del área.</div>
                            <div class="form-text">
                                Ejemplo: "Departamento de Finanzas", "Área de Compras", etc.
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                      maxlength="500" placeholder="Describa las funciones principales del área..."><?php echo $_POST['descripcion'] ?? ''; ?></textarea>
                            <div class="form-text">
                                <span id="contadorCaracteres">0</span> / 500 caracteres
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Información Importante</h6>
                                <ul class="mb-0 small">
                                    <li>El área se creará en estado "Activo" por defecto</li>
                                    <li>Podrá asignar usuarios a esta área después de crearla</li>
                                    <li>Las áreas inactivas no aparecerán en los listados de selección</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>areas" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Área
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Áreas Existentes -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Áreas Existentes
                </h5>
            </div>
            <div class="card-body">
                <?php
                $areaModel = new Area();
                $areasExistentes = $areaModel->obtenerTodas();
                ?>
                
                <?php if (!empty($areasExistentes)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($areasExistentes as $area): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo $area['nombre']; ?></h6>
                            <?php if ($area['descripcion']): ?>
                            <p class="mb-1 small text-muted"><?php echo $area['descripcion']; ?></p>
                            <?php endif; ?>
                            <small class="text-muted">
                                Creada: <?php echo date('d/m/Y', strtotime($area['fecha_creacion'])); ?>
                            </small>
                        </div>
                        <span class="badge <?php echo $area['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $area['activo'] ? 'Activa' : 'Inactiva'; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="fas fa-building fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No hay áreas registradas en el sistema.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contador de caracteres para la descripción
    const descripcion = document.getElementById('descripcion');
    const contador = document.getElementById('contadorCaracteres');
    
    descripcion.addEventListener('input', function() {
        contador.textContent = this.value.length;
    });
    
    // Inicializar contador
    contador.textContent = descripcion.value.length;
    
    // Sugerencia de nombre basado en patrones comunes
    const nombreInput = document.getElementById('nombre');
    nombreInput.addEventListener('blur', function() {
        if (this.value && !document.getElementById('descripcion').value) {
            // Generar descripción sugerida basada en el nombre
            const nombre = this.value.toLowerCase();
            let descripcionSugerida = '';
            
            if (nombre.includes('finanza') || nombre.includes('contabilidad')) {
                descripcionSugerida = 'Área responsable de la gestión financiera, contabilidad y presupuestos de la organización.';
            } else if (nombre.includes('compra') || nombre.includes('adquisicion')) {
                descripcionSugerida = 'Área encargada de las adquisiciones, compras y gestión de proveedores.';
            } else if (nombre.includes('logística') || nombre.includes('almacén')) {
                descripcionSugerida = 'Área responsable de la gestión logística, almacenamiento y distribución.';
            } else if (nombre.includes('recursos humano') || nombre.includes('personal')) {
                descripcionSugerida = 'Área encargada de la gestión del talento humano y desarrollo organizacional.';
            } else if (nombre.includes('tecnología') || nombre.includes('sistema') || nombre.includes('informática')) {
                descripcionSugerida = 'Área responsable de la infraestructura tecnológica y sistemas de información.';
            } else {
                descripcionSugerida = 'Área funcional de la organización responsable de sus actividades específicas.';
            }
            
            document.getElementById('descripcion').value = descripcionSugerida;
            descripcion.dispatchEvent(new Event('input'));
        }
    });
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>