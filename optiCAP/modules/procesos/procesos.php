<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['super_usuario', 'supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Procesar formulario de nuevo proceso
if ($_POST && isset($_POST['crear_proceso'])) {
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $sla_objetivo = $_POST['sla_objetivo'];
    $tiempo_total_dias = $_POST['tiempo_total_dias'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    try {
        $query = "INSERT INTO procesos (nombre, tipo, sla_objetivo, tiempo_total_dias, activo, fecha_creacion) 
                  VALUES (:nombre, :tipo, :sla_objetivo, :tiempo_total_dias, :activo, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':sla_objetivo', $sla_objetivo);
        $stmt->bindParam(':tiempo_total_dias', $tiempo_total_dias);
        $stmt->bindParam(':activo', $activo);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Proceso creado exitosamente";
            header("Location: procesos.php");
            exit();
        } else {
            $_SESSION['error'] = "Error al crear el proceso";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
    }
}

// Procesar edición de proceso
if ($_POST && isset($_POST['editar_proceso'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $sla_objetivo = $_POST['sla_objetivo'];
    $tiempo_total_dias = $_POST['tiempo_total_dias'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    try {
        $query = "UPDATE procesos SET 
                  nombre = :nombre, 
                  tipo = :tipo, 
                  sla_objetivo = :sla_objetivo, 
                  tiempo_total_dias = :tiempo_total_dias, 
                  activo = :activo 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':sla_objetivo', $sla_objetivo);
        $stmt->bindParam(':tiempo_total_dias', $tiempo_total_dias);
        $stmt->bindParam(':activo', $activo);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Proceso actualizado exitosamente";
            header("Location: procesos.php");
            exit();
        } else {
            $_SESSION['error'] = "Error al actualizar el proceso";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
    }
}

// Procesar eliminación de proceso
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    try {
        // Verificar si el proceso tiene actividades asociadas
        $query_check = "SELECT COUNT(*) as total FROM actividades WHERE proceso_id = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $_SESSION['error'] = "No se puede eliminar el proceso porque tiene actividades asociadas";
        } else {
            $query = "DELETE FROM procesos WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Proceso eliminado exitosamente";
            } else {
                $_SESSION['error'] = "Error al eliminar el proceso";
            }
        }
        header("Location: procesos.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
        header("Location: procesos.php");
        exit();
    }
}

// Obtener proceso para editar
$proceso_editar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $query = "SELECT * FROM procesos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $proceso_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener lista de procesos
$query = "SELECT p.*, 
                 (SELECT COUNT(*) FROM actividades a WHERE a.proceso_id = p.id AND a.activo = 1) as total_actividades
          FROM procesos p 
          ORDER BY p.nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Procesos - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Procesos</h1>
                    <?php if ($_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'super_usuario'): ?>
                    <div>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#nuevoProcesoModal">
                            <i class="fas fa-plus me-1"></i> Nuevo Proceso
                        </button>
                        <a href="actividades.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-tasks me-1"></i> Actividades
                        </a>
                        <a href="sla.php" class="btn btn-outline-info">
                            <i class="fas fa-clock me-1"></i> Configurar SLA
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Mostrar mensajes de éxito/error -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($procesos)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay procesos registrados. 
                                <?php if ($_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'super_usuario'): ?>
                                    <a href="#" class="alert-link" data-bs-toggle="modal" data-bs-target="#nuevoProcesoModal">Crea el primer proceso</a>.
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($procesos as $proceso): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($proceso['nombre']); ?></h5>
                                    <div>
                                        <span class="badge bg-<?php echo $proceso['tipo'] == 'Bien' ? 'info' : 'success'; ?> me-1">
                                            <?php echo htmlspecialchars($proceso['tipo']); ?>
                                        </span>
                                        <?php if ($_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'super_usuario'): ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="?editar=<?php echo $proceso['id']; ?>" 
                                                           data-bs-toggle="modal" data-bs-target="#editarProcesoModal">
                                                            <i class="fas fa-edit me-2"></i>Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="confirmarEliminacion(<?php echo $proceso['id']; ?>, '<?php echo htmlspecialchars($proceso['nombre']); ?>')">
                                                            <i class="fas fa-trash me-2"></i>Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-clock me-1"></i>Tiempo Total:</strong> 
                                        <?php echo $proceso['tiempo_total_dias']; ?> días<br>
                                        <strong><i class="fas fa-tasks me-1"></i>Actividades:</strong> 
                                        <?php echo $proceso['total_actividades']; ?><br>
                                        <strong><i class="fas fa-bullseye me-1"></i>SLA Objetivo:</strong> 
                                        <?php echo $proceso['sla_objetivo']; ?> días
                                    </div>
                                    
                                    <div class="progress mb-3" style="height: 25px;">
                                        <div class="progress-bar" role="progressbar" 
                                            style="width: <?php echo min(100, ($proceso['tiempo_total_dias'] / 60) * 100); ?>%; font-size: 14px;"
                                            aria-valuenow="<?php echo $proceso['tiempo_total_dias']; ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="60">
                                            <strong><?php echo $proceso['tiempo_total_dias']; ?> días</strong>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Estado:</strong> 
                                        <span class="badge bg-<?php echo $proceso['activo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $proceso['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Creado: <?php echo date('d/m/Y', strtotime($proceso['fecha_creacion'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas de Procesos -->
                <?php if ($_SESSION['rol'] == 'supervisor' || $_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'super_usuario'): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas de Procesos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="procesosChart" width="400" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <h6>Resumen por Tipo</h6>
                                <?php
                                $query_estadisticas = "SELECT tipo, COUNT(*) as total, 
                                                      SUM(tiempo_total_dias) as total_dias 
                                                      FROM procesos 
                                                      WHERE activo = 1 
                                                      GROUP BY tipo";
                                $stmt_estadisticas = $db->prepare($query_estadisticas);
                                $stmt_estadisticas->execute();
                                $estadisticas = $stmt_estadisticas->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <ul class="list-group">
                                    <?php foreach ($estadisticas as $est): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-<?php echo $est['tipo'] == 'Bien' ? 'box' : 'cogs'; ?> me-2"></i>
                                            <?php echo $est['tipo'] == 'Bien' ? 'Bienes' : 'Servicios'; ?>
                                        </span>
                                        <div>
                                            <span class="badge bg-primary rounded-pill me-2"><?php echo $est['total']; ?> procesos</span>
                                            <span class="text-muted"><?php echo $est['total_dias']; ?> días totales</span>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal para Nuevo Proceso -->
    <div class="modal fade" id="nuevoProcesoModal" tabindex="-1" aria-labelledby="nuevoProcesoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="nuevoProcesoModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Proceso
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre del Proceso *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required 
                                           placeholder="Ingrese el nombre del proceso">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo *</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="Bien">Bien</option>
                                        <option value="Servicio">Servicio</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sla_objetivo" class="form-label">SLA Objetivo (días) *</label>
                                    <input type="number" class="form-control" id="sla_objetivo" name="sla_objetivo" 
                                           required min="1" max="365" placeholder="Ej: 30">
                                    <div class="form-text">Tiempo objetivo para completar el proceso en días</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tiempo_total_dias" class="form-label">Tiempo Total Estimado (días) *</label>
                                    <input type="number" class="form-control" id="tiempo_total_dias" name="tiempo_total_dias" 
                                           required min="1" max="365" placeholder="Ej: 25">
                                    <div class="form-text">Tiempo total estimado para completar el proceso</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">Proceso activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_proceso" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Guardar Proceso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Proceso -->
    <div class="modal fade" id="editarProcesoModal" tabindex="-1" aria-labelledby="editarProcesoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarProcesoModalLabel">
                            <i class="fas fa-edit me-2"></i>Editar Proceso
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editar_id" value="<?php echo $proceso_editar['id'] ?? ''; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editar_nombre" class="form-label">Nombre del Proceso *</label>
                                    <input type="text" class="form-control" id="editar_nombre" name="nombre" required 
                                           value="<?php echo htmlspecialchars($proceso_editar['nombre'] ?? ''); ?>"
                                           placeholder="Ingrese el nombre del proceso">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editar_tipo" class="form-label">Tipo *</label>
                                    <select class="form-select" id="editar_tipo" name="tipo" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="Bien" <?php echo (isset($proceso_editar['tipo']) && $proceso_editar['tipo'] == 'Bien') ? 'selected' : ''; ?>>Bien</option>
                                        <option value="Servicio" <?php echo (isset($proceso_editar['tipo']) && $proceso_editar['tipo'] == 'Servicio') ? 'selected' : ''; ?>>Servicio</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editar_sla_objetivo" class="form-label">SLA Objetivo (días) *</label>
                                    <input type="number" class="form-control" id="editar_sla_objetivo" name="sla_objetivo" 
                                           required min="1" max="365" 
                                           value="<?php echo $proceso_editar['sla_objetivo'] ?? ''; ?>"
                                           placeholder="Ej: 30">
                                    <div class="form-text">Tiempo objetivo para completar el proceso en días</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editar_tiempo_total_dias" class="form-label">Tiempo Total Estimado (días) *</label>
                                    <input type="number" class="form-control" id="editar_tiempo_total_dias" name="tiempo_total_dias" 
                                           required min="1" max="365" 
                                           value="<?php echo $proceso_editar['tiempo_total_dias'] ?? ''; ?>"
                                           placeholder="Ej: 25">
                                    <div class="form-text">Tiempo total estimado para completar el proceso</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="editar_activo" name="activo" 
                                   <?php echo (isset($proceso_editar['activo']) && $proceso_editar['activo'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="editar_activo">Proceso activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar_proceso" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Actualizar Proceso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/chart.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Gráfico de procesos
        <?php if (isset($estadisticas) && !empty($estadisticas)): ?>
        const ctx = document.getElementById('procesosChart').getContext('2d');
        const procesosChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Bienes', 'Servicios'],
                datasets: [{
                    data: [
                        <?php 
                        $bienes = 0;
                        $servicios = 0;
                        foreach ($estadisticas as $est) {
                            if ($est['tipo'] == 'Bien') $bienes = $est['total'];
                            if ($est['tipo'] == 'Servicio') $servicios = $est['total'];
                        }
                        echo $bienes . ',' . $servicios;
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(46, 204, 113, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Distribución de Procesos por Tipo'
                    }
                }
            }
        });
        <?php endif; ?>

        // Validación del formulario
        document.addEventListener('DOMContentLoaded', function() {
            // Validación para formulario de crear
            const formCrear = document.querySelector('form[action=""]');
            if (formCrear) {
                formCrear.addEventListener('submit', function(e) {
                    const sla = document.getElementById('sla_objetivo')?.value;
                    const tiempoTotal = document.getElementById('tiempo_total_dias')?.value;
                    
                    if (sla && tiempoTotal && parseInt(tiempoTotal) > parseInt(sla)) {
                        e.preventDefault();
                        alert('El tiempo total estimado no puede ser mayor que el SLA objetivo');
                        return false;
                    }
                });
            }

            // Validación para formulario de editar
            const formEditar = document.querySelector('form[action=""]');
            if (formEditar) {
                formEditar.addEventListener('submit', function(e) {
                    const sla = document.getElementById('editar_sla_objetivo')?.value;
                    const tiempoTotal = document.getElementById('editar_tiempo_total_dias')?.value;
                    
                    if (sla && tiempoTotal && parseInt(tiempoTotal) > parseInt(sla)) {
                        e.preventDefault();
                        alert('El tiempo total estimado no puede ser mayor que el SLA objetivo');
                        return false;
                    }
                });
            }

            // Mostrar modal de edición si hay parámetro en URL
            <?php if (isset($_GET['editar']) && $proceso_editar): ?>
                var editarModal = new bootstrap.Modal(document.getElementById('editarProcesoModal'));
                editarModal.show();
            <?php endif; ?>
        });

        // Función para confirmar eliminación
        function confirmarEliminacion(id, nombre) {
            if (confirm('¿Está seguro de que desea eliminar el proceso "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
                window.location.href = '?eliminar=' + id;
            }
        }
    </script>
</body>
</html>