<?php
$pageTitle = "Nuevo Requerimiento";
$pageScript = "requerimientos.js";
require_once 'app/views/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Requerimiento
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="titulo" class="form-label">Título del Requerimiento *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo $_POST['titulo'] ?? ''; ?>" required maxlength="200">
                            <div class="invalid-feedback">Por favor ingrese un título para el requerimiento.</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="descripcion" class="form-label">Descripción Detallada *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5" 
                                      required maxlength="1000"><?php echo $_POST['descripcion'] ?? ''; ?></textarea>
                            <div class="invalid-feedback">Por favor ingrese una descripción detallada.</div>
                            <div class="form-text">
                                <span id="contadorCaracteres">0</span> / 1000 caracteres
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área Solicitante</label>
                            <input type="text" class="form-control" value="<?php echo $_SESSION['user_area_nombre'] ?? 'Mi Área'; ?>" disabled>
                            <input type="hidden" name="id_area_solicitante" value="<?php echo $_SESSION['user_area']; ?>">
                            <div class="form-text">El requerimiento se creará para su área actual.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Solicitante</label>
                            <input type="text" class="form-control" value="<?php echo $_SESSION['user_nombre']; ?>" disabled>
                            <input type="hidden" name="id_usuario_solicitante" value="<?php echo $_SESSION['user_id']; ?>">
                        </div>

                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Flujo del Proceso
                                    </h6>
                                    <p class="card-text small">
                                        Al crear el requerimiento, se generará automáticamente el siguiente flujo de actividades:
                                    </p>
                                    <ol class="small">
                                        <?php
                                        $actividadModel = new Actividad();
                                        $actividades = $actividadModel->obtenerActivas();
                                        $tiempoTotal = 0;
                                        foreach ($actividades as $actividad) {
                                            $tiempoTotal += $actividad['tiempo_limite'];
                                            echo "<li><strong>{$actividad['nombre']}</strong> - {$actividad['tiempo_limite']} días</li>";
                                        }
                                        ?>
                                    </ol>
                                    <p class="card-text small mb-0">
                                        <strong>Tiempo total estimado: <?php echo $tiempoTotal; ?> días</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>requerimientos" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Requerimiento
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>