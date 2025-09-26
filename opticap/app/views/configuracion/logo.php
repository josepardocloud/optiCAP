<?php
$pageTitle = "Configuración del Logo";
require_once 'app/views/layouts/header.php';

$config = $datos['configuracion'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-image me-2"></i>Configuración del Logo del Sistema
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Vista Previa del Logo Actual -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Logo Actual</h6>
                                </div>
                                <div class="card-body text-center">
                                    <?php if ($config['logo'] && file_exists(UPLOAD_PATH . $config['logo'])): ?>
                                    <img src="<?php echo BASE_URL . 'assets/uploads/' . $config['logo']; ?>" 
                                         alt="Logo actual" class="img-fluid mb-3" style="max-height: 150px;">
                                    <p class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Logo actual del sistema
                                    </p>
                                    <?php else: ?>
                                    <div class="py-4">
                                        <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay logo configurado</p>
                                        <p class="text-muted small">
                                            Se mostrará el nombre del sistema por defecto
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($config['logo']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" 
                                            onclick="confirmarEliminacionLogo()">
                                        <i class="fas fa-trash me-1"></i>Eliminar Logo
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Formulario de Carga -->
                            <div class="mb-3">
                                <label for="logo" class="form-label">Seleccionar Nuevo Logo *</label>
                                <input type="file" class="form-control" id="logo" name="logo" 
                                       accept=".png,.jpg,.jpeg,.gif" required>
                                <div class="invalid-feedback">Por favor seleccione un archivo de imagen.</div>
                                <div class="form-text">
                                    Formatos aceptados: PNG, JPG, JPEG, GIF. Tamaño máximo: 2MB.
                                </div>
                            </div>

                            <!-- Vista Previa del Nuevo Logo -->
                            <div class="mb-3">
                                <label class="form-label">Vista Previa</label>
                                <div id="preview-container" class="border rounded p-3 text-center" 
                                     style="min-height: 150px; display: none;">
                                    <img id="preview-logo" class="img-fluid" style="max-height: 120px;">
                                    <p class="text-muted small mt-2" id="preview-info"></p>
                                </div>
                                <div id="no-preview" class="text-center py-4 text-muted">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                    <p>Seleccione un archivo para ver la vista previa</p>
                                </div>
                            </div>

                            <!-- Especificaciones Técnicas -->
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-ruler-combined me-2"></i>Recomendaciones Técnicas
                                </h6>
                                <ul class="mb-0 small">
                                    <li><strong>Dimensiones ideales:</strong> 200x60 píxeles</li>
                                    <li><strong>Formato preferido:</strong> PNG con fondo transparente</li>
                                    <li><strong>Tamaño máximo:</strong> 2MB</li>
                                    <li><strong>Relación de aspecto:</strong> Horizontal (3:1 recomendado)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>configuracion/sistema" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver a Configuración
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Subir Nuevo Logo
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logos Predefinidos -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-palette me-2"></i>Logos Predefinidos
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Seleccione uno de nuestros logos predefinidos si no tiene un logo personalizado:</p>
                
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="card logo-option">
                            <div class="card-body">
                                <div class="logo-preview bg-primary text-white rounded p-2 mb-2" 
                                     style="height: 60px; display: flex; align-items: center; justify-content: center;">
                                    <h5 class="mb-0">OPTICAP</h5>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="seleccionarLogoPredefinido('default')">
                                    <i class="fas fa-check me-1"></i>Seleccionar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card logo-option">
                            <div class="card-body">
                                <div class="logo-preview bg-gradient-primary text-white rounded p-2 mb-2" 
                                     style="height: 60px; display: flex; align-items: center; justify-content: center;">
                                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>OPTICAP</h5>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="seleccionarLogoPredefinido('icon')">
                                    <i class="fas fa-check me-1"></i>Seleccionar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card logo-option">
                            <div class="card-body">
                                <div class="logo-preview bg-dark text-white rounded p-2 mb-2" 
                                     style="height: 60px; display: flex; align-items: center; justify-content: center;">
                                    <h6 class="mb-0">SISTEMA DE<br>ABASTECIMIENTO</h6>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="seleccionarLogoPredefinido('texto')">
                                    <i class="fas fa-check me-1"></i>Seleccionar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card logo-option">
                            <div class="card-body">
                                <div class="logo-preview bg-success text-white rounded p-2 mb-2" 
                                     style="height: 60px; display: flex; align-items: center; justify-content: center;">
                                    <h5 class="mb-0">OCAP</h5>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="seleccionarLogoPredefinido('simple')">
                                    <i class="fas fa-check me-1"></i>Seleccionar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="modalConfirmarEliminacionLogo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación del Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Advertencia:</strong> ¿Está seguro de que desea eliminar el logo actual?
                </div>
                <p>El sistema volverá a mostrar el nombre textual por defecto.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="eliminarLogo()">Eliminar Logo</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vista previa del logo seleccionado
    const logoInput = document.getElementById('logo');
    const previewContainer = document.getElementById('preview-container');
    const previewLogo = document.getElementById('preview-logo');
    const previewInfo = document.getElementById('preview-info');
    const noPreview = document.getElementById('no-preview');
    
    logoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewLogo.src = e.target.result;
                previewInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;
                previewContainer.style.display = 'block';
                noPreview.style.display = 'none';
            };
            
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
            noPreview.style.display = 'block';
        }
    });
    
    // Validación del tamaño del archivo
    logoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file && file.size > 2 * 1024 * 1024) { // 2MB
            alert('El archivo es demasiado grande. El tamaño máximo permitido es 2MB.');
            this.value = '';
            previewContainer.style.display = 'none';
            noPreview.style.display = 'block';
        }
    });
});

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function confirmarEliminacionLogo() {
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminacionLogo'));
    modal.show();
}

function eliminarLogo() {
    fetch('<?php echo BASE_URL; ?>api/configuracion/eliminarLogo', {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Logo eliminado correctamente.');
            location.reload();
        } else {
            alert('Error al eliminar el logo: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar el logo');
    });
}

function seleccionarLogoPredefinido(tipo) {
    if (confirm('¿Está seguro de que desea cambiar a este logo predefinido?')) {
        // Simular cambio de logo predefinido
        alert('Logo predefinido seleccionado. Esta característica está en desarrollo.');
    }
}
</script>

<style>
.logo-option {
    transition: transform 0.2s ease-in-out;
    cursor: pointer;
}
.logo-option:hover {
    transform: translateY(-2px);
}
.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}
</style>

<?php require_once 'app/views/layouts/footer.php'; ?>