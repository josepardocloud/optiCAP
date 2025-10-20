<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener procesos para el selector
$query_procesos = "SELECT * FROM procesos WHERE activo = 1 ORDER BY nombre";
$stmt_procesos = $db->prepare($query_procesos);
$stmt_procesos->execute();
$procesos = $stmt_procesos->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades con información de procesos
$query_actividades = "SELECT a.*, p.nombre as proceso_nombre, p.tipo as proceso_tipo,
                             (SELECT nombre FROM actividades WHERE id = a.actividad_anterior_id) as actividad_anterior_nombre
                      FROM actividades a 
                      INNER JOIN procesos p ON a.proceso_id = p.id 
                      ORDER BY p.nombre, a.orden";
$stmt_actividades = $db->prepare($query_actividades);
$stmt_actividades->execute();
$actividades = $stmt_actividades->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .acciones-container {
            display: flex;
            gap: 0.3rem;
            flex-wrap: nowrap;
            justify-content: center;
        }
        
        .btn-accion {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.3s ease;
            padding: 0;
        }
        
        .btn-accion:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        
        .kpi-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
        }
        
        .kpi-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        .table-actions {
            min-width: 100px;
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
                    <h1 class="h2">Gestión de Actividades</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalActividad">
                        <i class="fas fa-plus me-1"></i> Nueva Actividad
                    </button>
                </div>

                <!-- KPIs con fondo blanco -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card kpi-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-semibold">Total Actividades</div>
                                        <div class="h4 mb-0 fw-bold text-primary"><?php echo count($actividades); ?></div>
                                    </div>
                                    <div class="kpi-icon bg-primary bg-opacity-10">
                                        <i class="fas fa-tasks text-primary"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-sync-alt me-1"></i>Actualizado ahora
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card kpi-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-semibold">Actividades Activas</div>
                                        <div class="h4 mb-0 fw-bold text-success">
                                            <?php echo count(array_filter($actividades, fn($a) => $a['activo'])); ?>
                                        </div>
                                    </div>
                                    <div class="kpi-icon bg-success bg-opacity-10">
                                        <i class="fas fa-play-circle text-success"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-sync-alt me-1"></i>Actualizado ahora
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card kpi-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-semibold">Con SLA</div>
                                        <div class="h4 mb-0 fw-bold text-warning">
                                            <?php echo count(array_filter($actividades, fn($a) => !empty($a['sla_objetivo']))); ?>
                                        </div>
                                    </div>
                                    <div class="kpi-icon bg-warning bg-opacity-10">
                                        <i class="fas fa-clock text-warning"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-sync-alt me-1"></i>Actualizado ahora
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card kpi-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-semibold">Procesos</div>
                                        <div class="h4 mb-0 fw-bold text-info"><?php echo count($procesos); ?></div>
                                    </div>
                                    <div class="kpi-icon bg-info bg-opacity-10">
                                        <i class="fas fa-sitemap text-info"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-sync-alt me-1"></i>Actualizado ahora
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas -->
                <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaActividades">
                                <thead>
                                    <tr>
                                        <th>Proceso</th>
                                        <th>Orden</th>
                                        <th>Actividad</th>
                                        <th>Tiempo (días)</th>
                                        <th>Actividad Anterior</th>
                                        <th>SLA</th>
                                        <th>Estado</th>
                                        <th class="table-actions">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($actividades as $actividad): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $actividad['proceso_nombre']; ?></strong>
                                            <br><small class="text-muted"><?php echo $actividad['proceso_tipo']; ?></small>
                                        </td>
                                        <td><?php echo $actividad['orden']; ?></td>
                                        <td>
                                            <strong><?php echo $actividad['nombre']; ?></strong>
                                            <?php if ($actividad['descripcion']): ?>
                                            <br><small class="text-muted"><?php echo $actividad['descripcion']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $actividad['tiempo_dias']; ?></td>
                                        <td>
                                            <?php echo $actividad['actividad_anterior_nombre'] ?: 'Ninguna'; ?>
                                        </td>
                                        <td>
                                            <?php if ($actividad['sla_objetivo']): ?>
                                            <span class="badge bg-info"><?php echo $actividad['sla_objetivo']; ?> días</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">No definido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $actividad['activo'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $actividad['activo'] ? 'Activa' : 'Inactiva'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="acciones-container">
                                                <button type="button" class="btn-accion btn btn-outline-primary" 
                                                        onclick="editarActividad(<?php echo htmlspecialchars(json_encode($actividad)); ?>)"
                                                        data-bs-toggle="tooltip" title="Editar actividad">
                                                    <i class="fas fa-edit fa-xs"></i>
                                                </button>
                                                <a href="acciones_actividades.php?action=<?php echo $actividad['activo'] ? 'desactivar' : 'activar'; ?>&id=<?php echo $actividad['id']; ?>" 
                                                   class="btn-accion btn btn-outline-<?php echo $actividad['activo'] ? 'warning' : 'success'; ?>"
                                                   data-bs-toggle="tooltip" 
                                                   title="<?php echo $actividad['activo'] ? 'Desactivar' : 'Activar'; ?> actividad"
                                                   onclick="return confirm('¿Estás seguro de que quieres <?php echo $actividad['activo'] ? 'desactivar' : 'activar'; ?> esta actividad?')">
                                                    <i class="fas fa-<?php echo $actividad['activo'] ? 'pause' : 'play'; ?> fa-xs"></i>
                                                </a>
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

    <!-- Modal para Nueva/Editar Actividad -->
    <div class="modal fade" id="modalActividad" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formActividad" method="POST" action="acciones_actividades.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Nueva Actividad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="actividad_id" name="id">
                        <input type="hidden" name="action" id="formAction" value="crear">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="proceso_id" class="form-label">Proceso *</label>
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
                                    <label for="orden" class="form-label">Orden *</label>
                                    <input type="number" class="form-control" id="orden" name="orden" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Actividad *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tiempo_dias" class="form-label">Tiempo Estimado (días) *</label>
                                    <input type="number" class="form-control" id="tiempo_dias" name="tiempo_dias" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sla_objetivo" class="form-label">SLA Objetivo (días)</label>
                                    <input type="number" class="form-control" id="sla_objetivo" name="sla_objetivo" min="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="actividad_anterior_id" class="form-label">Actividad Anterior</label>
                            <select class="form-select" id="actividad_anterior_id" name="actividad_anterior_id">
                                <option value="">Ninguna (Primera actividad)</option>
                                <!-- Las opciones se cargarán dinámicamente -->
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" checked>
                            <label class="form-check-label" for="activo">Actividad activa</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Actividad</button>
                    </div>
                </form>
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

        // Función para cargar actividades anteriores según el proceso seleccionado
        function cargarActividadesAnteriores(procesoId, actividadActualId = null) {
            const select = document.getElementById('actividad_anterior_id');
            
            if (!procesoId) {
                select.innerHTML = '<option value="">Ninguna (Primera actividad)</option>';
                return;
            }
            
            // Limpiar opciones excepto la primera
            select.innerHTML = '<option value="">Ninguna (Primera actividad)</option>';
            
            // Hacer petición para obtener actividades del proceso
            fetch(`acciones_actividades.php?action=get_actividades&proceso_id=${procesoId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(actividad => {
                        // No permitir seleccionarse a sí misma como anterior
                        if (actividad.id != actividadActualId) {
                            const option = document.createElement('option');
                            option.value = actividad.id;
                            option.textContent = `${actividad.orden}. ${actividad.nombre}`;
                            select.appendChild(option);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error al cargar actividades:', error);
                });
        }
        
        // Event listener para cambio de proceso
        document.getElementById('proceso_id').addEventListener('change', function() {
            cargarActividadesAnteriores(this.value);
        });
        
        // Función para editar actividad
        function editarActividad(actividad) {
            document.getElementById('modalTitle').textContent = 'Editar Actividad';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('actividad_id').value = actividad.id;
            document.getElementById('proceso_id').value = actividad.proceso_id;
            document.getElementById('orden').value = actividad.orden;
            document.getElementById('nombre').value = actividad.nombre;
            document.getElementById('descripcion').value = actividad.descripcion || '';
            document.getElementById('tiempo_dias').value = actividad.tiempo_dias;
            document.getElementById('sla_objetivo').value = actividad.sla_objetivo || '';
            document.getElementById('activo').checked = actividad.activo;
            
            // Cargar actividades anteriores para este proceso
            cargarActividadesAnteriores(actividad.proceso_id, actividad.id);
            
            // Establecer la actividad anterior seleccionada después de cargar las opciones
            setTimeout(() => {
                document.getElementById('actividad_anterior_id').value = actividad.actividad_anterior_id || '';
            }, 500);
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalActividad'));
            modal.show();
        }
        
        // Limpiar formulario cuando se cierre el modal
        document.getElementById('modalActividad').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formActividad').reset();
            document.getElementById('modalTitle').textContent = 'Nueva Actividad';
            document.getElementById('formAction').value = 'crear';
            document.getElementById('actividad_id').value = '';
            document.getElementById('actividad_anterior_id').innerHTML = '<option value="">Ninguna (Primera actividad)</option>';
        });
    </script>
</body>
</html>