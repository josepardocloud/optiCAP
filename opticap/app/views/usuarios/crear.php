<?php
$pageTitle = "Crear Usuario";
require_once 'app/views/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo $_POST['nombre'] ?? ''; ?>" required>
                            <div class="invalid-feedback">Por favor ingrese el nombre del usuario.</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                            <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="id_area" class="form-label">Área/Oficina</label>
                            <select class="form-select" id="id_area" name="id_area" required>
                                <option value="">Seleccionar área...</option>
                                <?php foreach ($datos['areas'] as $area): ?>
                                <option value="<?php echo $area['id']; ?>" 
                                    <?php echo isset($_POST['id_area']) && $_POST['id_area'] == $area['id'] ? 'selected' : ''; ?>>
                                    <?php echo $area['nombre']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Por favor seleccione un área.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="">Seleccionar rol...</option>
                                <option value="usuario" <?php echo isset($_POST['rol']) && $_POST['rol'] == 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                <option value="proceso" <?php echo isset($_POST['rol']) && $_POST['rol'] == 'proceso' ? 'selected' : ''; ?>>Usuario Proceso</option>
                                <option value="supervisor" <?php echo isset($_POST['rol']) && $_POST['rol'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="admin" <?php echo isset($_POST['rol']) && $_POST['rol'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                            <div class="invalid-feedback">Por favor seleccione un rol.</div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Información Importante</h6>
                                <ul class="mb-0 small">
                                    <li>Al crear el usuario, se le asignará una contraseña temporal</li>
                                    <li>El usuario deberá cambiar su contraseña en el primer inicio de sesión</li>
                                    <li>Los permisos específicos por actividad se configuran después de crear el usuario</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo BASE_URL; ?>usuarios" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Usuario
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>