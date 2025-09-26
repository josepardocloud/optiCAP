<?php
$pageTitle = "Editar Requerimiento";
$pageScript = "requerimientos.js";
require_once 'app/views/layouts/header.php';

$req = $datos['requerimiento'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Editar Requerimiento: <?php echo $req['codigo']; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="titulo" class="form-label">Título del Requerimiento *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo htmlspecialchars($req['titulo']); ?>" required maxlength="200">
                            <div class="invalid-feedback">Por favor ingrese un título para el requerimiento.</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="descripcion" class="form-label">Descripción Detallada *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5" 
                                      required maxlength="1000"><?php echo htmlspecialchars($req['descripcion']); ?></textarea>
                            <div class="invalid-feedback">Por favor ingrese una descripción detallada.</div>
                            <div class="form-text">
                                <span id="contadorCaracteres">0</span> / 1000 caracteres
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" class="form-control" value="<?php echo $req['codigo']; ?>" disabled>
                            <div class="form-text">El código del requerimiento no puede modificarse.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado Actual</label>
                            <input type="text" class="form-control estado-<?php echo $req['estado']; ?>" 
                                   value="<?php echo ucfirst(str_replace('_', ' ', $req['estado'])); ?>" disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área Solicitante</label>
                            <input type="text" class="form-control" value="<?php echo $req['area_nombre']; ?>" disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Creación</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo date('d/m/Y H:i', strtotime($req['fecha_creacion'])); ?>" disabled>
                        </div>

                        <?php if (AuthHelper::hasRole('admin')): ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Acciones Avanzadas</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="forzar_actualizacion" name="forzar_actualizacion">
                                    <label class="form-check-label" for="forzar_actualizacion">
                                        Forzar actualización incluso si el requerimiento está en proceso
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>requerimientos/detalle/<?php echo $req['id']; ?>" 
                                   class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                    <?php if (AuthHelper::hasRole('admin') && $req['estado'] != 'cancelado'): ?>
                                    <button type="button" class="btn btn-outline-danger ms-2" 
                                            onclick="mostrarModalCancelacion()">
                                        <i class="fas fa-times me-2"></i>Cancelar Requerimiento
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historial de Cambios -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Historial de Modificaciones
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Simulación de historial - en un sistema real esto vendría de la base de datos
                            $historial = [
                                [
                                    'fecha' => $req['fecha_creacion'],
                                    'usuario' => $req['usuario_solicitante'],
                                    'accion' => 'CREAR',
                                    'descripcion' => 'Requerimiento creado'
                                ]
                            ];
                            
                            foreach ($historial as $registro): 
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($registro['fecha'])); ?></td>
                                <td><?php echo $registro['usuario']; ?></td>
                                <td><span class="badge bg-info"><?php echo $registro['accion']; ?></span></td>
                                <td><?php echo $registro['descripcion']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cancelación -->
<div class="modal fade" id="modalCancelacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>requerimientos/cancelar/<?php echo $req['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Cancelar Requerimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo_cancelacion" class="form-label">Motivo de la Cancelación *</label>
                        <textarea class="form-control" id="motivo_cancelacion" name="motivo_cancelacion" 
                                  rows="3" required placeholder="Describa el motivo de la cancelación..."></textarea>
                        <div class="invalid-feedback">Por favor ingrese el motivo de la cancelación.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
                </div>
            </form>
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
});

function mostrarModalCancelacion() {
    const modal = new bootstrap.Modal(document.getElementById('modalCancelacion'));
    modal.show();
}
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>