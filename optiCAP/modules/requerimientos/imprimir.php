<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

if (!isset($_GET['id'])) {
    redirectTo('requerimientos.php');
    exit();
}

$requerimiento_id = $_GET['id'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar permisos de visualización
if (!puedeVerRequerimiento($usuario_id, $requerimiento_id)) {
    redirectTo('requerimientos.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener información del requerimiento
$query_requerimiento = "SELECT r.*, a.nombre as area_nombre, p.nombre as proceso_nombre, p.tipo as proceso_tipo, 
                               u.nombre as usuario_solicitante
                        FROM requerimientos r 
                        INNER JOIN areas a ON r.area_id = a.id 
                        INNER JOIN procesos p ON r.proceso_id = p.id 
                        INNER JOIN usuarios u ON r.usuario_solicitante_id = u.id 
                        WHERE r.id = ?";
$stmt_requerimiento = $db->prepare($query_requerimiento);
$stmt_requerimiento->execute([$requerimiento_id]);
$requerimiento = $stmt_requerimiento->fetch(PDO::FETCH_ASSOC);

if (!$requerimiento) {
    redirectTo('requerimientos.php');
    exit();
}

// Obtener seguimiento de actividades
$query_seguimiento = "SELECT sa.*, a.nombre as actividad_nombre, a.orden, a.tiempo_dias, 
                             u.nombre as usuario_nombre
                      FROM seguimiento_actividades sa 
                      INNER JOIN actividades a ON sa.actividad_id = a.id 
                      LEFT JOIN usuarios u ON sa.usuario_id = u.id 
                      WHERE sa.requerimiento_id = ? 
                      ORDER BY a.orden";
$stmt_seguimiento = $db->prepare($query_seguimiento);
$stmt_seguimiento->execute([$requerimiento_id]);
$seguimientos = $stmt_seguimiento->fetchAll(PDO::FETCH_ASSOC);

// Obtener movimientos para cada actividad
$movimientos_por_actividad = [];
foreach ($seguimientos as $seguimiento) {
    $query_movimientos = "SELECT sm.*, u.nombre as usuario_nombre 
                         FROM seguimiento_movimientos sm
                         INNER JOIN usuarios u ON sm.usuario_id = u.id
                         WHERE sm.seguimiento_id = ?
                         ORDER BY sm.fecha_creacion DESC";
    $stmt_movimientos = $db->prepare($query_movimientos);
    $stmt_movimientos->execute([$seguimiento['id']]);
    $movimientos_por_actividad[$seguimiento['id']] = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener configuración del sistema
$query_config = "SELECT nombre_sistema, logo_url FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
$stmt_config = $db->prepare($query_config);
$stmt_config->execute();
$config = $stmt_config->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Requerimiento - <?php echo $requerimiento['codigo']; ?></title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
            .table { border: 1px solid #ddd; }
            .badge { border: 1px solid #000; font-weight: normal; }
            .btn { display: none !important; }
            body { font-size: 12px; line-height: 1.2; }
            .container-fluid { max-width: 100% !important; padding: 0 15px; }
            .card-body { padding: 10px !important; }
            .timeline-print { margin-left: 5px !important; }
        }
        
        @media screen {
            .print-only { display: none; }
        }
        
        .header-print { 
            border-bottom: 3px solid #2c3e50; 
            padding-bottom: 20px; 
            margin-bottom: 25px; 
            text-align: center;
        }
        
        .timeline-print { 
            border-left: 3px solid #3498db; 
            padding-left: 25px; 
            margin-left: 15px; 
            position: relative;
        }
        
        .timeline-item-print { 
            position: relative; 
            margin-bottom: 25px; 
        }
        
        .timeline-marker-print { 
            position: absolute; 
            left: -30px; 
            top: 15px; 
            width: 16px; 
            height: 16px; 
            border-radius: 50%; 
            background: #3498db; 
            border: 3px solid white;
            box-shadow: 0 0 0 2px #3498db;
        }
        
        .completed .timeline-marker-print { 
            background: #27ae60; 
            box-shadow: 0 0 0 2px #27ae60;
        }
        
        .current .timeline-marker-print { 
            background: #f39c12; 
            box-shadow: 0 0 0 2px #f39c12;
        }
        
        .pending .timeline-marker-print { 
            background: #95a5a6; 
            box-shadow: 0 0 0 2px #95a5a6;
        }
        
        .kpi-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
            background: #f8f9fa;
        }
        
        .kpi-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .kpi-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .movimiento-card {
            border-left: 4px solid #3498db;
            padding: 10px;
            margin-bottom: 8px;
            background: #f8f9fa;
            font-size: 11px;
        }
        
        .movimiento-completado { border-left-color: #27ae60; }
        .movimiento-proceso { border-left-color: #f39c12; }
        .movimiento-pendiente { border-left-color: #95a5a6; }
        
        .estado-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .table-condensed th,
        .table-condensed td {
            padding: 6px 8px;
            font-size: 11px;
        }
        
        .signature-area {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 auto;
            padding-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Botón de impresión -->
        <div class="no-print text-center my-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
            <a href="detalle.php?id=<?php echo $requerimiento_id; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Detalle
            </a>
        </div>

        <!-- Encabezado del documento -->
        <div class="header-print">
            <div class="row align-items-center">
                <div class="col-3 text-start">
                    <?php if ($config['logo_url']): ?>
                    <img src="/opticap/assets/uploads/logos/<?php echo $config['logo_url']; ?>" alt="Logo" height="70" class="mb-3">
                    <?php else: ?>
                    <div style="height: 70px;"></div>
                    <?php endif; ?>
                </div>
                <div class="col-6 text-center">
                    <h1 style="color: #2c3e50; margin-bottom: 5px;"><?php echo $config['nombre_sistema'] ?? 'OptiCAP'; ?></h1>
                    <h3 class="text-muted" style="margin-bottom: 5px;">Requerimiento de Adquisición</h3>
                    <h4 style="color: #3498db; font-weight: bold;"><?php echo $requerimiento['codigo']; ?></h4>
                </div>
                <div class="col-3 text-end">
                    <div class="text-muted" style="font-size: 12px;">
                        Generado: <?php echo date('d/m/Y H:i'); ?><br>
                        Página 1 de 1
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs Resumen -->
        <div class="row mb-4">
            <div class="col-md-2 col-6">
                <div class="kpi-card">
                    <div class="kpi-number text-primary"><?php echo count($seguimientos); ?></div>
                    <div class="kpi-label">Total Actividades</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="kpi-card">
                    <div class="kpi-number text-warning">
                        <?php echo count(array_filter($seguimientos, function($s) { return $s['estado'] == 'pendiente'; })); ?>
                    </div>
                    <div class="kpi-label">Pendientes</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="kpi-card">
                    <div class="kpi-number text-info">
                        <?php echo count(array_filter($seguimientos, function($s) { return $s['estado'] == 'en_proceso'; })); ?>
                    </div>
                    <div class="kpi-label">En Proceso</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="kpi-card">
                    <div class="kpi-number text-success">
                        <?php echo count(array_filter($seguimientos, function($s) { return $s['estado'] == 'completado'; })); ?>
                    </div>
                    <div class="kpi-label">Completadas</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="kpi-card">
                    <div class="kpi-number text-danger">
                        <?php 
                        $total_movimientos = 0;
                        foreach ($movimientos_por_actividad as $movimientos) {
                            $total_movimientos += count($movimientos);
                        }
                        echo $total_movimientos;
                        ?>
                    </div>
                    <div class="kpi-label">Total Movimientos</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="kpi-card">
                    <div class="kpi-number text-secondary">
                        <?php 
                        $total_evidencias = 0;
                        foreach ($movimientos_por_actividad as $movimientos) {
                            foreach ($movimientos as $movimiento) {
                                if ($movimiento['archivo_adjunto']) {
                                    $total_evidencias++;
                                }
                            }
                        }
                        echo $total_evidencias;
                        ?>
                    </div>
                    <div class="kpi-label">Evidencias</div>
                </div>
            </div>
        </div>

        <!-- Información del Requerimiento -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h4 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Información del Requerimiento
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered table-condensed">
                            <tr>
                                <th width="40%" class="bg-light">Código:</th>
                                <td><strong class="text-primary"><?php echo $requerimiento['codigo']; ?></strong></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tipo:</th>
                                <td>
                                    <span class="estado-badge bg-<?php echo $requerimiento['proceso_tipo'] == 'Bien' ? 'info' : 'success'; ?> text-white">
                                        <i class="fas fa-<?php echo $requerimiento['proceso_tipo'] == 'Bien' ? 'box' : 'cogs'; ?> me-1"></i>
                                        <?php echo $requerimiento['proceso_tipo']; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Proceso:</th>
                                <td><?php echo $requerimiento['proceso_nombre']; ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Estado:</th>
                                <td>
                                    <span class="estado-badge bg-<?php 
                                        switch($requerimiento['estado']) {
                                            case 'pendiente': echo 'warning'; break;
                                            case 'en_proceso': echo 'info'; break;
                                            case 'completado': echo 'success'; break;
                                            case 'cancelado': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?> text-white">
                                        <i class="fas fa-<?php 
                                            switch($requerimiento['estado']) {
                                                case 'pendiente': echo 'clock'; break;
                                                case 'en_proceso': echo 'sync-alt'; break;
                                                case 'completado': echo 'check-circle'; break;
                                                case 'cancelado': echo 'times-circle'; break;
                                                default: echo 'question-circle';
                                            }
                                        ?> me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $requerimiento['estado'])); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered table-condensed">
                            <tr>
                                <th width="40%" class="bg-light">Área Solicitante:</th>
                                <td><?php echo $requerimiento['area_nombre']; ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Usuario Solicitante:</th>
                                <td><?php echo $requerimiento['usuario_solicitante']; ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Fecha de Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($requerimiento['fecha_creacion'])); ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Documento Generado:</th>
                                <td><?php echo date('d/m/Y H:i'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($requerimiento['observaciones']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5><i class="fas fa-sticky-note me-2"></i>Observaciones:</h5>
                        <div class="border p-3 bg-light" style="font-size: 11px;">
                            <?php echo nl2br(htmlspecialchars($requerimiento['observaciones'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Línea de Tiempo -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h4 class="card-title mb-0">
                    <i class="fas fa-project-diagram me-2"></i>Línea de Tiempo del Proceso
                </h4>
            </div>
            <div class="card-body">
                <div class="timeline-print">
                    <?php foreach ($seguimientos as $seguimiento): 
                        $clase = '';
                        if ($seguimiento['estado'] == 'completado') {
                            $clase = 'completed';
                        } elseif ($seguimiento['estado'] == 'en_proceso') {
                            $clase = 'current';
                        } else {
                            $clase = 'pending';
                        }
                        
                        $movimientos = $movimientos_por_actividad[$seguimiento['id']] ?? [];
                    ?>
                    <div class="timeline-item-print <?php echo $clase; ?>">
                        <div class="timeline-marker-print"></div>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-primary">
                                    <?php echo $seguimiento['orden']; ?>. <?php echo $seguimiento['actividad_nombre']; ?>
                                </h6>
                                
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Estado:</strong> 
                                            <span class="estado-badge bg-<?php 
                                                switch($seguimiento['estado']) {
                                                    case 'pendiente': echo 'secondary'; break;
                                                    case 'en_proceso': echo 'warning'; break;
                                                    case 'completado': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?> text-white">
                                                <i class="fas fa-<?php 
                                                    switch($seguimiento['estado']) {
                                                        case 'pendiente': echo 'clock'; break;
                                                        case 'en_proceso': echo 'sync-alt'; break;
                                                        case 'completado': echo 'check-circle'; break;
                                                        default: echo 'question-circle';
                                                    }
                                                ?> me-1"></i>
                                                <?php echo ucfirst($seguimiento['estado']); ?>
                                            </span>
                                        </p>
                                        
                                        <?php if ($seguimiento['fecha_inicio']): ?>
                                        <p class="mb-1"><strong>Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($seguimiento['fecha_inicio'])); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if ($seguimiento['fecha_fin']): ?>
                                        <p class="mb-1"><strong>Fin:</strong> <?php echo date('d/m/Y H:i', strtotime($seguimiento['fecha_fin'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Tiempo estimado:</strong> <?php echo $seguimiento['tiempo_dias']; ?> días</p>
                                        
                                        <?php if ($seguimiento['usuario_nombre']): ?>
                                        <p class="mb-1"><strong>Responsable:</strong> <?php echo $seguimiento['usuario_nombre']; ?></p>
                                        <?php endif; ?>
                                        
                                        <p class="mb-1"><strong>Movimientos:</strong> <?php echo count($movimientos); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($seguimiento['observaciones']): ?>
                                <div class="mb-2">
                                    <strong>Observaciones:</strong>
                                    <div class="border-top pt-2 mt-2" style="font-size: 11px;">
                                        <?php echo nl2br(htmlspecialchars($seguimiento['observaciones'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Movimientos recientes -->
                                <?php if (!empty($movimientos)): ?>
                                <div class="mt-2">
                                    <strong>Últimos movimientos:</strong>
                                    <div class="mt-1">
                                        <?php 
                                        $movimientos_recientes = array_slice($movimientos, 0, 2); // Mostrar solo los 2 más recientes
                                        foreach ($movimientos_recientes as $movimiento): 
                                            $clase_movimiento = 'movimiento-' . str_replace('_', '-', $movimiento['estado_nuevo']);
                                        ?>
                                        <div class="movimiento-card <?php echo $clase_movimiento; ?>">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo $movimiento['usuario_nombre']; ?></strong>
                                                <small><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_creacion'])); ?></small>
                                            </div>
                                            <div class="mb-1">
                                                <span class="estado-badge bg-secondary"><?php echo ucfirst($movimiento['estado_anterior']); ?></span>
                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                <span class="estado-badge bg-<?php 
                                                    switch($movimiento['estado_nuevo']) {
                                                        case 'pendiente': echo 'secondary'; break;
                                                        case 'en_proceso': echo 'warning'; break;
                                                        case 'completado': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($movimiento['estado_nuevo']); ?></span>
                                            </div>
                                            <?php if ($movimiento['observaciones']): ?>
                                            <div style="font-size: 10px;">
                                                <?php echo substr($movimiento['observaciones'], 0, 100); ?><?php echo strlen($movimiento['observaciones']) > 100 ? '...' : ''; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($movimientos) > 2): ?>
                                        <div class="text-center text-muted" style="font-size: 10px;">
                                            ... y <?php echo count($movimientos) - 2; ?> movimientos más
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Firmas y aprobaciones -->
        <div class="signature-area">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="signature-line">
                        <small>Solicitante</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="signature-line">
                        <small>Supervisor</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="signature-line">
                        <small>Administrador</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="text-center mt-4 pt-4 border-top">
            <p class="text-muted" style="font-size: 10px;">
                <strong><?php echo $config['nombre_sistema'] ?? 'OptiCAP'; ?></strong> - 
                Sistema de Gestión de Requerimientos - 
                Documento generado el <?php echo date('d/m/Y \a \l\a\s H:i'); ?> - 
                Requerimiento: <?php echo $requerimiento['codigo']; ?>
            </p>
        </div>
    </div>

    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-imprimir al cargar la página
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
        
        // Volver al detalle después de imprimir
        window.onafterprint = function() {
            setTimeout(function() {
                if (confirm('¿Desea volver al detalle del requerimiento?')) {
                    window.location.href = 'detalle.php?id=<?php echo $requerimiento_id; ?>';
                }
            }, 500);
        };
    </script>
</body>
</html>