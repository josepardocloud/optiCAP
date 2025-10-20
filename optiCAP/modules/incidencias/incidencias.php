<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$area_id = $_SESSION['area_id'];

// Construir filtro según el rol
$filtro_rol = obtenerRequerimientosPorRol($usuario_id, $rol, $area_id);
$where_clause = str_replace('WHERE', 'AND', $filtro_rol);

// Obtener incidencias según el rol
if ($rol == 'administrador') {
    // Administradores ven todas las incidencias
    $query = "SELECT i.*, r.codigo, r.area_id, 
                     u.nombre as usuario_reporta_nombre,
                     ur.nombre as usuario_resuelve_nombre,
                     a.nombre as area_nombre
              FROM incidencias i 
              INNER JOIN requerimientos r ON i.requerimiento_id = r.id 
              INNER JOIN usuarios u ON i.usuario_reporta_id = u.id 
              LEFT JOIN usuarios ur ON i.usuario_resuelve_id = ur.id
              INNER JOIN areas a ON r.area_id = a.id 
              ORDER BY i.fecha_reporte DESC";
} else {
    // Otros roles solo ven sus propias incidencias
    $query = "SELECT i.*, r.codigo, r.area_id, 
                     u.nombre as usuario_reporta_nombre,
                     ur.nombre as usuario_resuelve_nombre,
                     a.nombre as area_nombre
              FROM incidencias i 
              INNER JOIN requerimientos r ON i.requerimiento_id = r.id 
              INNER JOIN usuarios u ON i.usuario_reporta_id = u.id 
              LEFT JOIN usuarios ur ON i.usuario_resuelve_id = ur.id
              INNER JOIN areas a ON r.area_id = a.id 
              WHERE i.usuario_reporta_id = ?
              ORDER BY i.fecha_reporte DESC";
}

$stmt = $db->prepare($query);
if ($rol == 'administrador') {
    $stmt->execute();
} else {
    $stmt->execute([$usuario_id]);
}
$incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Incidencias - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .kpi-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .kpi-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .kpi-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }
        .kpi-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
        }
        .btn-action {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            margin: 2px;
            transition: all 0.2s ease;
        }
        .btn-action:hover {
            transform: scale(1.1);
        }
        .action-group {
            display: flex;
            gap: 4px;
        }
        .modal-img {
            max-height: 500px;
            width: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .info-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Incidencias</h1>
                    <?php if ($rol != 'administrador'): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalIncidencia">
                        <i class="fas fa-plus me-1"></i> Reportar Incidencia
                    </button>
                    <?php else: ?>
                    <span class="badge bg-info info-badge">
                        <i class="fas fa-info-circle me-1"></i>Modo Administrador
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Información de permisos -->
                <?php if ($rol != 'administrador'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> Solo puedes ver y reportar tus propias incidencias. Los administradores se encargarán de resolverlas.
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Modo Administrador:</strong> Puedes ver todas las incidencias y resolverlas agregando una solución.
                </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3" id="formFiltros">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <option value="reportada">Reportada</option>
                                    <option value="en_revision">En Revisión</option>
                                    <option value="resuelta">Resuelta</option>
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
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-primary" onclick="filtrarTabla()">Filtrar</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">Limpiar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tarjetas de Resumen -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card kpi-card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">TOTAL INCIDENCIAS</p>
                                        <h2 class="kpi-number text-primary"><?php echo count($incidencias); ?></h2>
                                    </div>
                                    <div class="kpi-icon text-primary">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card kpi-card border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">REPORTADAS</p>
                                        <h2 class="kpi-number text-warning"><?php echo count(array_filter($incidencias, function($i) { return $i['estado'] == 'reportada'; })); ?></h2>
                                    </div>
                                    <div class="kpi-icon text-warning">
                                        <i class="fas fa-flag"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card kpi-card border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">EN REVISIÓN</p>
                                        <h2 class="kpi-number text-info"><?php echo count(array_filter($incidencias, function($i) { return $i['estado'] == 'en_revision'; })); ?></h2>
                                    </div>
                                    <div class="kpi-icon text-info">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card kpi-card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="kpi-title">RESUELTAS</p>
                                        <h2 class="kpi-number text-success"><?php echo count(array_filter($incidencias, function($i) { return $i['estado'] == 'resuelta'; })); ?></h2>
                                    </div>
                                    <div class="kpi-icon text-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Incidencias -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Lista de Incidencias
                            <?php if ($rol == 'administrador'): ?>
                            <span class="badge bg-primary ms-2">Total: <?php echo count($incidencias); ?></span>
                            <?php else: ?>
                            <span class="badge bg-primary ms-2">Mis incidencias: <?php echo count($incidencias); ?></span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($incidencias)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay incidencias</h4>
                            <?php if ($rol != 'administrador'): ?>
                            <p class="text-muted">No has reportado ninguna incidencia aún.</p>
                            <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalIncidencia">
                                <i class="fas fa-plus me-1"></i> Reportar Primera Incidencia
                            </button>
                            <?php else: ?>
                            <p class="text-muted">No hay incidencias reportadas en el sistema.</p>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaIncidencias">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Requerimiento</th>
                                        <th>Descripción</th>
                                        <th>Reportada por</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidencias as $incidencia): ?>
                                    <tr>
                                        <td><strong>#<?php echo $incidencia['id']; ?></strong></td>
                                        <td>
                                            <strong class="text-primary"><?php echo $incidencia['codigo']; ?></strong>
                                            <br><small class="text-muted"><?php echo $incidencia['area_nombre']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo strlen($incidencia['descripcion']) > 100 ? 
                                                substr($incidencia['descripcion'], 0, 100) . '...' : 
                                                $incidencia['descripcion']; ?>
                                        </td>
                                        <td><?php echo $incidencia['usuario_reporta_nombre']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($incidencia['fecha_reporte'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($incidencia['estado']) {
                                                    case 'reportada': echo 'warning'; break;
                                                    case 'en_revision': echo 'info'; break;
                                                    case 'resuelta': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $incidencia['estado'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="action-group">
                                                <button type="button" class="btn btn-action btn-outline-primary" 
                                                        onclick="verIncidencia(<?php echo $incidencia['id']; ?>)"
                                                        title="Ver Detalles"
                                                        data-bs-toggle="tooltip">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($rol == 'administrador' && $incidencia['estado'] != 'resuelta'): ?>
                                                <button type="button" class="btn btn-action btn-outline-success" 
                                                        onclick="mostrarModalResolver(<?php echo $incidencia['id']; ?>)"
                                                        title="Resolver Incidencia"
                                                        data-bs-toggle="tooltip">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Reportar Incidencia -->
    <?php if ($rol != 'administrador'): ?>
    <div class="modal fade" id="modalIncidencia" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="acciones_incidencias.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Reportar Nueva Incidencia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        
                        <div class="mb-3">
                            <label for="requerimiento_id" class="form-label">Requerimiento *</label>
                            <select class="form-select" id="requerimiento_id" name="requerimiento_id" required>
                                <option value="">Seleccionar requerimiento...</option>
                                <?php
                                // Obtener requerimientos del usuario actual
                                $query_requerimientos = "SELECT r.id, r.codigo, a.nombre as area_nombre 
                                                       FROM requerimientos r 
                                                       INNER JOIN areas a ON r.area_id = a.id 
                                                       WHERE r.area_id = ?
                                                       ORDER BY r.fecha_creacion DESC";
                                $stmt_requerimientos = $db->prepare($query_requerimientos);
                                $stmt_requerimientos->execute([$area_id]);
                                $requerimientos = $stmt_requerimientos->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($requerimientos as $req):
                                ?>
                                <option value="<?php echo $req['id']; ?>">
                                    <?php echo $req['codigo']; ?> - <?php echo $req['area_nombre']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción de la Incidencia *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5" 
                                      placeholder="Describa detalladamente la incidencia encontrada..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="evidencia" class="form-label">Evidencia (Imagen)</label>
                            <input type="file" class="form-control" id="evidencia" name="evidencia" 
                                   accept="image/*" 
                                   onchange="previewImage(this)">
                            <div class="form-text">
                                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                            </div>
                            
                            <!-- Vista previa de la imagen -->
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img id="preview" src="#" alt="Vista previa" class="img-thumbnail" style="max-height: 200px;">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeImage()">
                                    <i class="fas fa-times"></i> Eliminar imagen
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Reportar Incidencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal para Ver Incidencia -->
    <div class="modal fade" id="modalVerIncidencia" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Incidencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detallesIncidencia">
                    <!-- Los detalles se cargarán aquí dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Resolver Incidencia -->
    <div class="modal fade" id="modalResolverIncidencia" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resolver Incidencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoResolverIncidencia">
                    <!-- El formulario de solución se cargará aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Inicializar tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Función para ver detalles de incidencia
        function verIncidencia(incidenciaId) {
            fetch(`acciones_incidencias.php?action=ver&id=${incidenciaId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('detallesIncidencia').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('modalVerIncidencia'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error al cargar incidencia:', error);
                    alert('Error al cargar los detalles de la incidencia: ' + error.message);
                });
        }

        // Función para mostrar modal de resolver incidencia
        function mostrarModalResolver(incidenciaId) {
            fetch(`acciones_incidencias.php?action=mostrar_modal_resolver&id=${incidenciaId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenidoResolverIncidencia').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('modalResolverIncidencia'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el formulario de resolución');
                });
        }

        // Función para enviar la solución
        function enviarSolucion(event, incidenciaId) {
            event.preventDefault();
            
            const solucion = document.getElementById('solucion').value;
            
            if (!solucion.trim()) {
                alert('Por favor, ingrese la solución de la incidencia');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'resolver');
            formData.append('id', incidenciaId);
            formData.append('solucion', solucion);
            
            fetch('acciones_incidencias.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Cerrar ambos modales
                    const modalResolver = bootstrap.Modal.getInstance(document.getElementById('modalResolverIncidencia'));
                    modalResolver.hide();
                    const modalVer = bootstrap.Modal.getInstance(document.getElementById('modalVerIncidencia'));
                    if (modalVer) modalVer.hide();
                    // Recargar la página
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al resolver la incidencia');
            });
        }
        
        // Vista previa de imagen
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeImage() {
            document.getElementById('evidencia').value = '';
            document.getElementById('imagePreview').style.display = 'none';
        }
        
        // Filtrar tabla de incidencias
        function filtrarTabla() {
            const estado = document.getElementById('estado').value;
            const fechaDesde = document.getElementById('fecha_desde').value;
            const fechaHasta = document.getElementById('fecha_hasta').value;
            
            const filas = document.querySelectorAll('#tablaIncidencias tbody tr');
            let filasVisibles = 0;
            
            filas.forEach(fila => {
                let mostrar = true;
                const celdas = fila.getElementsByTagName('td');
                
                // Filtrar por estado
                if (estado) {
                    const estadoFila = celdas[5].textContent.toLowerCase().trim();
                    if (!estadoFila.includes(estado)) {
                        mostrar = false;
                    }
                }
                
                // Filtrar por fecha
                if (fechaDesde || fechaHasta) {
                    const fechaFila = celdas[4].textContent.split(' ')[0].split('/').reverse().join('-');
                    const fechaFilaDate = new Date(fechaFila);
                    
                    if (fechaDesde) {
                        const desdeDate = new Date(fechaDesde);
                        if (fechaFilaDate < desdeDate) {
                            mostrar = false;
                        }
                    }
                    
                    if (fechaHasta) {
                        const hastaDate = new Date(fechaHasta);
                        if (fechaFilaDate > hastaDate) {
                            mostrar = false;
                        }
                    }
                }
                
                fila.style.display = mostrar ? '' : 'none';
                if (mostrar) filasVisibles++;
            });
            
            // Actualizar contador
            const titulo = document.querySelector('.card-title');
            const badgeExistente = titulo.querySelector('.badge');
            if (badgeExistente) {
                badgeExistente.textContent = `Mostrando: ${filasVisibles} de ${filas.length}`;
            }
        }
        
        function limpiarFiltros() {
            document.getElementById('estado').value = '';
            document.getElementById('fecha_desde').value = '';
            document.getElementById('fecha_hasta').value = '';
            filtrarTabla();
        }
    </script>
</body>
</html>