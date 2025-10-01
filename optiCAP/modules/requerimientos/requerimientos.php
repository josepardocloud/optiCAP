<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$area_id = $_SESSION['area_id'];

// Obtener requerimientos según el rol
$filtro_rol = obtenerRequerimientosPorRol($usuario_id, $rol, $area_id);
$query = "SELECT r.*, a.nombre as area_nombre, p.nombre as proceso_nombre, p.tipo as proceso_tipo 
          FROM requerimientos r 
          INNER JOIN areas a ON r.area_id = a.id 
          INNER JOIN procesos p ON r.proceso_id = p.id 
          $filtro_rol 
          ORDER BY r.fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$requerimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requerimientos - OptiCAP</title>
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
                    <h1 class="h2">Requerimientos</h1>
                    <?php if (usuarioPuedeCrearRequerimientos($usuario_id)): ?>
                    <a href="/opticap/modules/requerimientos/crear.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Requerimiento
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en_proceso">En Proceso</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos</option>
                                    <option value="Bien">Bien</option>
                                    <option value="Servicio">Servicio</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Requerimientos -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaRequerimientos">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Proceso</th>
                                        <th>Área</th>
                                        <th>Fecha Creación</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requerimientos as $req): ?>
                                    <tr>
                                        <td><?php echo $req['codigo']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $req['proceso_tipo'] == 'Bien' ? 'info' : 'success'; ?>">
                                                <?php echo $req['proceso_tipo']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $req['proceso_nombre']; ?></td>
                                        <td><?php echo $req['area_nombre']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($req['fecha_creacion'])); ?></td>
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
                                            <a href="/opticap/modules/requerimientos/detalle.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver Detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/opticap/modules/requerimientos/imprimir.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Imprimir" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if (puedeVerRequerimiento($usuario_id, $req['id'])): ?>
                                            <a href="/opticap/modules/requerimientos/seguimiento.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-info" title="Seguimiento">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                            <?php endif; ?>
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

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
</body>
</html>