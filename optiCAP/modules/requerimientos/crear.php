<?php
require_once '../../config/session.php'; // ← AGREGAR ESTA LÍNEA
require_once '../../includes/funciones.php';
verificarSesion();

// Verificar que el usuario puede crear requerimientos
if (!usuarioPuedeCrearRequerimientos($_SESSION['usuario_id'])) {
    redirectTo('modules/requerimientos/requerimientos.php'); // ← CORREGIDO
    exit();
}


$database = new Database();
$db = $database->getConnection();

// Obtener procesos activos
$query_procesos = "SELECT * FROM procesos WHERE activo = 1";
$stmt_procesos = $db->prepare($query_procesos);
$stmt_procesos->execute();
$procesos = $stmt_procesos->fetchAll(PDO::FETCH_ASSOC);

// Obtener áreas
$query_areas = "SELECT * FROM areas WHERE activo = 1";
$stmt_areas = $db->prepare($query_areas);
$stmt_areas->execute();
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

$mensaje = '';
$error = '';

if ($_POST) {
    try {
        $proceso_id = $_POST['proceso_id'];
        $area_id = $_POST['area_id'];
        $observaciones = $_POST['observaciones'];
        
        // Obtener información del proceso
        $query_proceso = "SELECT tipo FROM procesos WHERE id = ?";
        $stmt_proceso = $db->prepare($query_proceso);
        $stmt_proceso->execute([$proceso_id]);
        $proceso = $stmt_proceso->fetch(PDO::FETCH_ASSOC);
        
        // Generar código único
        $codigo = generarCodigoRequerimiento($proceso['tipo']);
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Crear requerimiento
        $query_requerimiento = "INSERT INTO requerimientos (codigo, proceso_id, area_id, usuario_solicitante_id, observaciones) VALUES (?, ?, ?, ?, ?)";
        $stmt_requerimiento = $db->prepare($query_requerimiento);
        $stmt_requerimiento->execute([$codigo, $proceso_id, $area_id, $_SESSION['usuario_id'], $observaciones]);
        $requerimiento_id = $db->lastInsertId();
        
        // Obtener actividades del proceso
        $query_actividades = "SELECT * FROM actividades WHERE proceso_id = ? ORDER BY orden";
        $stmt_actividades = $db->prepare($query_actividades);
        $stmt_actividades->execute([$proceso_id]);
        $actividades = $stmt_actividades->fetchAll(PDO::FETCH_ASSOC);
        
        // Crear seguimiento para cada actividad
        foreach ($actividades as $actividad) {
            $estado = $actividad['orden'] == 1 ? 'pendiente' : 'pendiente';
            
            $query_seguimiento = "INSERT INTO seguimiento_actividades (requerimiento_id, actividad_id, estado) VALUES (?, ?, ?)";
            $stmt_seguimiento = $db->prepare($query_seguimiento);
            $stmt_seguimiento->execute([$requerimiento_id, $actividad['id'], $estado]);
        }
        
        // Confirmar transacción
        $db->commit();
        
        $mensaje = "Requerimiento creado exitosamente: {$codigo}";
        
        // Redirigir al detalle del requerimiento
        redirectTo("modules/requerimientos/detalle.php?id={$requerimiento_id}&mensaje=" . urlencode($mensaje));
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al crear el requerimiento: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Requerimiento - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Nuevo Requerimiento</h1>
                    <a href="requerimientos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="proceso_id" class="form-label">Tipo de Proceso *</label>
                                        <select class="form-select" id="proceso_id" name="proceso_id" required>
                                            <option value="">Seleccionar proceso...</option>
                                            <?php foreach ($procesos as $proceso): ?>
                                            <option value="<?php echo $proceso['id']; ?>">
                                                <?php echo $proceso['nombre']; ?> (<?php echo $proceso['tipo']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="area_id" class="form-label">Área Solicitante *</label>
                                        <select class="form-select" id="area_id" name="area_id" required>
                                            <option value="">Seleccionar área...</option>
                                            <?php foreach ($areas as $area): ?>
                                            <option value="<?php echo $area['id']; ?>" 
                                                <?php echo ($_SESSION['rol'] == 'usuario' && $_SESSION['area_id'] == $area['id']) ? 'selected' : ''; ?>
                                                <?php echo ($_SESSION['rol'] == 'usuario' && $_SESSION['area_id'] != $area['id']) ? 'disabled' : ''; ?>>
                                                <?php echo $area['nombre']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($_SESSION['rol'] == 'usuario'): ?>
                                        <div class="form-text">Solo puede crear requerimientos para su área asignada.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Descripción adicional del requerimiento..."></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Información del Proceso</h6>
                                <p class="mb-0" id="info-proceso">Seleccione un proceso para ver los detalles.</p>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Crear Requerimiento
                                </button>
                                <a href="requerimientos.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Cargar información del proceso seleccionado
        document.getElementById('proceso_id').addEventListener('change', function() {
            const procesoId = this.value;
            const infoProceso = document.getElementById('info-proceso');
            
            if (procesoId) {
                // Aquí se podría hacer una petición AJAX para obtener los detalles del proceso
                infoProceso.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando información...';
                
                // Simulación de carga de información
                setTimeout(() => {
                    const procesosInfo = {
                        '1': 'Proceso de adquisición de bienes con 14 actividades. Tiempo estimado: 45 días.',
                        '2': 'Proceso de adquisición de servicios con 14 actividades. Tiempo estimado: 40 días.'
                    };
                    infoProceso.textContent = procesosInfo[procesoId] || 'Información no disponible.';
                }, 500);
            } else {
                infoProceso.textContent = 'Seleccione un proceso para ver los detalles.';
            }
        });
    </script>
</body>
</html>