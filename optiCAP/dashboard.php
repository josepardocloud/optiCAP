<?php
require_once 'config/session.php';
require_once 'includes/funciones.php';
verificarSesion();

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas según el rol
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$area_id = $_SESSION['area_id'];

// Inicializar variables
$total_requerimientos = 0;
$total_pendientes = 0;
$requerimientos_recientes = [];

try {
    // Consultas diferentes según el rol
    if ($rol == 'usuario') {
        $query_requerimientos = "SELECT COUNT(*) as total FROM requerimientos WHERE area_id = ?";
        $stmt = $db->prepare($query_requerimientos);
        $stmt->execute([$area_id]);
        
        $query_pendientes = "SELECT COUNT(*) as total FROM requerimientos r 
                            INNER JOIN seguimiento_actividades sa ON r.id = sa.requerimiento_id 
                            WHERE r.area_id = ? AND sa.estado = 'pendiente'";
        $stmt_pendientes = $db->prepare($query_pendientes);
        $stmt_pendientes->execute([$area_id]);
    } else {
        $query_requerimientos = "SELECT COUNT(*) as total FROM requerimientos";
        $stmt = $db->prepare($query_requerimientos);
        $stmt->execute();
        
        $query_pendientes = "SELECT COUNT(*) as total FROM seguimiento_actividades WHERE estado = 'pendiente'";
        $stmt_pendientes = $db->prepare($query_pendientes);
        $stmt_pendientes->execute();
    }

    $total_requerimientos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pendientes = $stmt_pendientes->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtener requerimientos recientes
    $filtro_rol = obtenerRequerimientosPorRol($usuario_id, $rol, $area_id);
    $query_recientes = "SELECT r.*, a.nombre as area_nombre, p.nombre as proceso_nombre 
                       FROM requerimientos r 
                       INNER JOIN areas a ON r.area_id = a.id 
                       INNER JOIN procesos p ON r.proceso_id = p.id 
                       $filtro_rol 
                       ORDER BY r.fecha_creacion DESC LIMIT 5";
    $stmt_recientes = $db->prepare($query_recientes);
    $stmt_recientes->execute();
    $requerimientos_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error al cargar datos del dashboard: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OptiCAP</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <br><small>Si el problema persiste, ejecuta el script de instalación.</small>
                </div>
                <?php endif; ?>

                <!-- Estadísticas con fondo blanco -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title text-muted">Total Requerimientos</h5>
                                        <h2 class="text-primary"><?php echo $total_requerimientos; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Actualizado ahora
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title text-muted">Actividades Pendientes</h5>
                                        <h2 class="text-warning"><?php echo $total_pendientes; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tasks fa-2x text-warning"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Actualizado ahora
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title text-muted">Mi Área</h5>
                                        <h2 class="text-success"><?php 
                                            // Obtener nombre del área
                                            if (isset($_SESSION['area_id'])) {
                                                $query_area = "SELECT nombre FROM areas WHERE id = ?";
                                                $stmt_area = $db->prepare($query_area);
                                                $stmt_area->execute([$_SESSION['area_id']]);
                                                $area = $stmt_area->fetch(PDO::FETCH_ASSOC);
                                                echo $area ? $area['nombre'] : 'N/A';
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-building fa-2x text-success"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo $_SESSION['rol'] ?? 'Usuario'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requerimientos Recientes -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Requerimientos Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($requerimientos_recientes)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Proceso</th>
                                        <th>Área</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requerimientos_recientes as $req): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary"><?php echo $req['codigo']; ?></strong>
                                        </td>
                                        <td><?php echo $req['proceso_nombre']; ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?php echo $req['area_nombre']; ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($req['fecha_creacion'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($req['estado']) {
                                                    case 'pendiente': echo 'warning'; break;
                                                    case 'en_proceso': echo 'info'; break;
                                                    case 'completado': echo 'success'; break;
                                                    case 'cancelado': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $req['estado'])); ?></span>
                                        </td>
                                        <td>
                                            <a href="modules/requerimientos/detalle.php?id=<?php echo $req['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay requerimientos recientes</h5>
                            <p class="text-muted">Los requerimientos nuevos aparecerán aquí.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h6 class="card-title mb-0">Acciones Rápidas</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <a href="modules/requerimientos/crear.php" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Nuevo Requerimiento
                                        </a>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <a href="modules/requerimientos/requerimientos.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-list me-2"></i>Ver Todos
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="modules/seguimiento/seguimiento.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-tasks me-2"></i>Mis Actividades
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="modules/incidencias/incidencias.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Incidencias
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h6 class="card-title mb-0">Resumen del Sistema</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php
                                    try {
                                        // Obtener más estadísticas
                                        $query_usuarios = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1";
                                        $stmt_usuarios = $db->prepare($query_usuarios);
                                        $stmt_usuarios->execute();
                                        $total_usuarios = $stmt_usuarios->fetch(PDO::FETCH_ASSOC)['total'];

                                        $query_procesos = "SELECT COUNT(*) as total FROM procesos WHERE activo = 1";
                                        $stmt_procesos = $db->prepare($query_procesos);
                                        $stmt_procesos->execute();
                                        $total_procesos = $stmt_procesos->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Usuarios Activos
                                        <span class="badge bg-primary rounded-pill"><?php echo $total_usuarios; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Procesos Configurados
                                        <span class="badge bg-success rounded-pill"><?php echo $total_procesos; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Requerimientos Activos
                                        <span class="badge bg-info rounded-pill"><?php echo $total_requerimientos; ?></span>
                                    </div>
                                    <?php } catch (Exception $e) { ?>
                                    <div class="list-group-item text-center text-muted">
                                        No se pudieron cargar las estadísticas
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>