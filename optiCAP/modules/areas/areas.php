<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener lista de áreas
$query = "SELECT a.*, 
                 (SELECT COUNT(*) FROM usuarios u WHERE u.area_id = a.id AND u.activo = 1) as total_usuarios,
                 (SELECT COUNT(*) FROM requerimientos r WHERE r.area_id = a.id) as total_requerimientos
          FROM areas a 
          ORDER BY a.nombre";
$stmt = $db->prepare($query);
$stmt->execute();
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Áreas - OptiCAP</title>
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
                    <h1 class="h2">Gestión de Áreas</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalArea">
                        <i class="fas fa-plus me-1"></i> Nueva Área
                    </button>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="row">
                    <?php foreach ($areas as $area): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo $area['nombre']; ?></h5>
                                <span class="badge bg-<?php echo $area['activo'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $area['activo'] ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <?php if ($area['descripcion']): ?>
                                <p class="card-text"><?php echo $area['descripcion']; ?></p>
                                <?php endif; ?>
                                
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border rounded p-2 bg-light">
                                            <h4 class="text-primary mb-0"><?php echo $area['total_usuarios']; ?></h4>
                                            <small class="text-muted">Usuarios</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2 bg-light">
                                            <h4 class="text-info mb-0"><?php echo $area['total_requerimientos']; ?></h4>
                                            <small class="text-muted">Requerimientos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editarArea(<?php echo htmlspecialchars(json_encode($area)); ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <a href="acciones_areas.php?action=<?php echo $area['activo'] ? 'desactivar' : 'activar'; ?>&id=<?php echo $area['id']; ?>" 
                                       class="btn btn-sm btn-outline-<?php echo $area['activo'] ? 'warning' : 'success'; ?>">
                                        <i class="fas fa-<?php echo $area['activo'] ? 'pause' : 'play'; ?>"></i>
                                        <?php echo $area['activo'] ? 'Desactivar' : 'Activar'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resumen Estadístico -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Resumen por Áreas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Área</th>
                                        <th>Usuarios Activos</th>
                                        <th>Requerimientos Totales</th>
                                        <th>Requerimientos Pendientes</th>
                                        <th>Requerimientos Completados</th>
                                        <th>Tasa de Completación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $query_estadisticas = "SELECT 
                                        a.nombre,
                                        COUNT(DISTINCT u.id) as usuarios_activos,
                                        COUNT(r.id) as requerimientos_totales,
                                        SUM(CASE WHEN r.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                                        SUM(CASE WHEN r.estado = 'completado' THEN 1 ELSE 0 END) as completados
                                        FROM areas a 
                                        LEFT JOIN usuarios u ON a.id = u.area_id AND u.activo = 1 
                                        LEFT JOIN requerimientos r ON a.id = r.area_id 
                                        WHERE a.activo = 1 
                                        GROUP BY a.id, a.nombre 
                                        ORDER BY a.nombre";
                                    $stmt_estadisticas = $db->prepare($query_estadisticas);
                                    $stmt_estadisticas->execute();
                                    $estadisticas = $stmt_estadisticas->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($estadisticas as $est):
                                        $tasa_completacion = $est['requerimientos_totales'] > 0 ? 
                                            round(($est['completados'] / $est['requerimientos_totales']) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $est['nombre']; ?></strong></td>
                                        <td><?php echo $est['usuarios_activos']; ?></td>
                                        <td><?php echo $est['requerimientos_totales']; ?></td>
                                        <td>
                                            <span class="badge bg-warning"><?php echo $est['pendientes']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $est['completados']; ?></span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $tasa_completacion; ?>%"
                                                     aria-valuenow="<?php echo $tasa_completacion; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo $tasa_completacion; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Nueva/Editar Área -->
    <div class="modal fade" id="modalArea" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formArea" method="POST" action="acciones_areas.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Nueva Área</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="area_id" name="id">
                        <input type="hidden" name="action" id="formAction" value="crear">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Área *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" checked>
                            <label class="form-check-label" for="activo">Área activa</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Área</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Función para editar área
        function editarArea(area) {
            document.getElementById('modalTitle').textContent = 'Editar Área';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('area_id').value = area.id;
            document.getElementById('nombre').value = area.nombre;
            document.getElementById('descripcion').value = area.descripcion || '';
            document.getElementById('activo').checked = area.activo;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalArea'));
            modal.show();
        }
        
        // Limpiar formulario cuando se cierre el modal
        document.getElementById('modalArea').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formArea').reset();
            document.getElementById('modalTitle').textContent = 'Nueva Área';
            document.getElementById('formAction').value = 'crear';
            document.getElementById('area_id').value = '';
        });
    </script>
</body>
</html>