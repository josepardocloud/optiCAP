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

// Obtener incidencias - COLUMNA CORREGIDA
$query = "SELECT i.*, r.codigo, r.area_id, 
                 u.nombre as usuario_reporta_nombre,
                 ur.nombre as usuario_resuelve_nombre,  -- Nombre corregido
                 a.nombre as area_nombre
          FROM incidencias i 
          INNER JOIN requerimientos r ON i.requerimiento_id = r.id 
          INNER JOIN usuarios u ON i.usuario_reporta_id = u.id 
          LEFT JOIN usuarios ur ON i.usuario_resuelve_id = ur.id   -- COLUMNA CORREGIDA
          INNER JOIN areas a ON r.area_id = a.id 
          WHERE 1=1 $where_clause
          ORDER BY i.fecha_reporte DESC";
$stmt = $db->prepare($query);
$stmt->execute();
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
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Incidencias</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalIncidencia">
                        <i class="fas fa-plus me-1"></i> Reportar Incidencia
                    </button>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
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
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                    <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tarjetas de Resumen -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total</h5>
                                        <h2><?php echo count($incidencias); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Reportadas</h5>
                                        <h2><?php echo count(array_filter($incidencias, function($i) { return $i['estado'] == 'reportada'; })); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-flag fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">En Revisión</h5>
                                        <h2><?php echo count(array_filter($incidencias, function($i) { return $i['estado'] == 'en_revision'; })); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-search fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Resueltas</h5>
                                        <h2><?php echo count(array_filter($incidencias, function($i) { return $i['estado'] == 'resuelta'; })); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Incidencias -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaIncidencias">
                                <thead>
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
                                        <td>#<?php echo $incidencia['id']; ?></td>
                                        <td>
                                            <strong><?php echo $incidencia['codigo']; ?></strong>
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
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="verIncidencia(<?php echo $incidencia['id']; ?>)"
                                                    title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (($_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'supervisor') && $incidencia['estado'] != 'resuelta'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="resolverIncidencia(<?php echo $incidencia['id']; ?>)"
                                                    title="Resolver Incidencia">
                                                <i class="fas fa-check"></i>
                                            </button>
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

    <!-- Modal para Reportar Incidencia -->
  <!-- Modal para Reportar Incidencia -->
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
                            // Obtener requerimientos según el rol
                            $filtro_rol = obtenerRequerimientosPorRol($usuario_id, $rol, $area_id);
                            $query_requerimientos = "SELECT r.id, r.codigo, a.nombre as area_nombre 
                                                   FROM requerimientos r 
                                                   INNER JOIN areas a ON r.area_id = a.id 
                                                   $filtro_rol 
                                                   ORDER BY r.fecha_creacion DESC";
                            $stmt_requerimientos = $db->prepare($query_requerimientos);
                            $stmt_requerimientos->execute();
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

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Función para ver detalles de incidencia
        function verIncidencia(incidenciaId) {
            fetch(`acciones_incidencias.php?action=ver&id=${incidenciaId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detallesIncidencia').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('modalVerIncidencia'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error al cargar incidencia:', error);
                    alert('Error al cargar los detalles de la incidencia');
                });
        }
        
        // Función para resolver incidencia
        function resolverIncidencia(incidenciaId) {
            if (confirm('¿Está seguro de que desea marcar esta incidencia como resuelta?')) {
                const formData = new FormData();
                formData.append('action', 'resolver');
                formData.append('id', incidenciaId);
                
                fetch('acciones_incidencias.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Incidencia marcada como resuelta');
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
        }
        
        // Filtrar tabla de incidencias
        document.addEventListener('DOMContentLoaded', function() {
            const filtroEstado = document.getElementById('estado');
            const filtroFechaDesde = document.getElementById('fecha_desde');
            const filtroFechaHasta = document.getElementById('fecha_hasta');
            
            function filtrarTabla() {
                const estado = filtroEstado.value;
                const fechaDesde = filtroFechaDesde.value;
                const fechaHasta = filtroFechaHasta.value;
                
                const filas = document.querySelectorAll('#tablaIncidencias tbody tr');
                
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
                });
            }
            
            // Aplicar filtros cuando cambien los valores
            if (filtroEstado) filtroEstado.addEventListener('change', filtrarTabla);
            if (filtroFechaDesde) filtroFechaDesde.addEventListener('change', filtrarTabla);
            if (filtroFechaHasta) filtroFechaHasta.addEventListener('change', filtrarTabla);
        });
    </script>
</body>
</html>