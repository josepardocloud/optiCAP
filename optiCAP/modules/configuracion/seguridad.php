<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener logs de seguridad recientes
$query_logs = "SELECT ls.*, u.nombre as usuario_nombre 
               FROM logs_seguridad ls 
               LEFT JOIN usuarios u ON ls.usuario_id = u.id 
               ORDER BY ls.fecha DESC 
               LIMIT 50";
$stmt_logs = $db->prepare($query_logs);
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios bloqueados
$query_bloqueados = "SELECT * FROM usuarios WHERE bloqueado = 1 ORDER BY fecha_bloqueo DESC";
$stmt_bloqueados = $db->prepare($query_bloqueados);
$stmt_bloqueados->execute();
$usuarios_bloqueados = $stmt_bloqueados->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas de seguridad
$query_estadisticas = "SELECT 
    COUNT(*) as total_logs,
    SUM(CASE WHEN resultado = 'exito' THEN 1 ELSE 0 END) as exitos,
    SUM(CASE WHEN resultado = 'fallo' THEN 1 ELSE 0 END) as fallos,
    COUNT(DISTINCT ip) as ips_unicas
    FROM logs_seguridad 
    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stmt_estadisticas = $db->prepare($query_estadisticas);
$stmt_estadisticas->execute();
$estadisticas = $stmt_estadisticas->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Seguridad - OptiCAP</title>
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
                    <h1 class="h2">Configuración de Seguridad</h1>
                    <div class="btn-group">
                        <a href="sistema.php" class="btn btn-outline-secondary">Sistema</a>
                        <a href="email.php" class="btn btn-outline-primary">Email</a>
                    </div>
                </div>

                <!-- Estadísticas de Seguridad -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title text-primary">Eventos (30 días)</h5>
                                        <h2 class="text-dark"><?php echo $estadisticas['total_logs']; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shield-alt fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title text-success">Exitosos</h5>
                                        <h2 class="text-dark"><?php echo $estadisticas['exitos']; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title text-danger">Fallidos</h5>
                                        <h2 class="text-dark"><?php echo $estadisticas['fallos']; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title text-info">IPs Únicas</h5>
                                        <h2 class="text-dark"><?php echo $estadisticas['ips_unicas']; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-network-wired fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <!-- Usuarios Bloqueados -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-lock me-2"></i>
                                    Usuarios Bloqueados
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($usuarios_bloqueados)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Email</th>
                                                    <th>Bloqueado</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($usuarios_bloqueados as $usuario): ?>
                                                <tr>
                                                    <td><?php echo $usuario['nombre']; ?></td>
                                                    <td><?php echo $usuario['email']; ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_bloqueo'])); ?></td>
                                                    <td>
                                                        <a href="../usuarios/acciones.php?action=desbloquear&id=<?php echo $usuario['id']; ?>" 
                                                           class="btn btn-sm btn-success">
                                                            <i class="fas fa-unlock me-1"></i> Desbloquear
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No hay usuarios bloqueados en este momento.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Configuración de Seguridad -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Configuración de Seguridad</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label class="form-label">Máximo de intentos de login</label>
                                        <input type="number" class="form-control" value="4" readonly>
                                        <div class="form-text">Configurado en 4 intentos como máximo.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Duración de sesión</label>
                                        <select class="form-select">
                                            <option selected>30 minutos</option>
                                            <option>1 hora</option>
                                            <option>2 horas</option>
                                            <option>8 horas</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="force_ssl" checked>
                                        <label class="form-check-label" for="force_ssl">
                                            Forzar conexión HTTPS
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="log_activity" checked>
                                        <label class="form-check-label" for="log_activity">
                                            Registrar actividad de usuarios
                                        </label>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Guardar Configuración
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Logs de Seguridad -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Logs de Seguridad Recientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Usuario</th>
                                                <th>Acción</th>
                                                <th>IP</th>
                                                <th>Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo date('H:i', strtotime($log['fecha'])); ?></td>
                                                <td>
                                                    <?php echo $log['usuario_nombre'] ?: 'Sistema'; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $log['accion']; ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo $log['ip']; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $log['resultado'] == 'exito' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($log['resultado']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <a href="exportar_logs.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-download me-1"></i> Exportar Logs Completos
                                    </a>
                                </div>
                            </div>
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