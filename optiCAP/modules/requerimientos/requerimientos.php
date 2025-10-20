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

// Obtener KPIs de requerimientos
$kpis = obtenerKPIsRequerimientos($usuario_id, $rol, $area_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requerimientos - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

                <!-- KPIs -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Total</div>
                                        <div class="h5 mb-0 text-primary"><?php echo $kpis['total']; ?></div>
                                    </div>
                                    <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Pendientes</div>
                                        <div class="h5 mb-0 text-warning"><?php echo $kpis['pendientes']; ?></div>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">En Proceso</div>
                                        <div class="h5 mb-0 text-info"><?php echo $kpis['en_proceso']; ?></div>
                                    </div>
                                    <i class="fas fa-sync-alt fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Completados</div>
                                        <div class="h5 mb-0 text-success"><?php echo $kpis['completados']; ?></div>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-danger h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Cancelados</div>
                                        <div class="h5 mb-0 text-danger"><?php echo $kpis['cancelados']; ?></div>
                                    </div>
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-muted small">Tasa Éxito</div>
                                        <div class="h5 mb-0 text-secondary"><?php echo $kpis['tasa_exito']; ?>%</div>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x text-secondary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3" id="formFiltros">
                            <div class="col-md-3">
                                <label for="codigo" class="form-label">Código</label>
                                <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Buscar por código...">
                            </div>
                            <div class="col-md-2">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en_proceso">En Proceso</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos</option>
                                    <option value="Bien">Bien</option>
                                    <option value="Servicio">Servicio</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" id="limpiarFiltros" class="btn btn-outline-secondary w-100" title="Limpiar filtros">
                                    <i class="fas fa-eraser"></i>
                                </button>
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
                                        <td>
                                            <strong><?php echo $req['codigo']; ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $req['proceso_tipo'] == 'Bien' ? 'info' : 'success'; ?>">
                                                <i class="fas fa-<?php echo $req['proceso_tipo'] == 'Bien' ? 'box' : 'cogs'; ?> me-1"></i>
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
                                            ?>">
                                                <i class="fas fa-<?php 
                                                    switch($req['estado']) {
                                                        case 'pendiente': echo 'clock'; break;
                                                        case 'en_proceso': echo 'sync-alt'; break;
                                                        case 'completado': echo 'check-circle'; break;
                                                        case 'cancelado': echo 'times-circle'; break;
                                                        default: echo 'question-circle';
                                                    }
                                                ?> me-1"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $req['estado'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="/opticap/modules/requerimientos/detalle.php?id=<?php echo $req['id']; ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Ver Detalle"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/opticap/modules/requerimientos/imprimir.php?id=<?php echo $req['id']; ?>" 
                                                   class="btn btn-outline-secondary" 
                                                   title="Imprimir" 
                                                   target="_blank"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <?php if (puedeVerRequerimiento($usuario_id, $req['id'])): ?>
                                                
                                                <?php endif; ?>
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

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    <script>
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Función para filtrar la tabla - FILTROS INDEPENDIENTES
        function filtrarTabla() {
            // Usar setTimeout para asegurar que se ejecute después de cualquier otro código
            setTimeout(() => {
                const codigo = document.getElementById('codigo').value.toLowerCase().trim();
                const estado = document.getElementById('estado').value.toLowerCase();
                const tipo = document.getElementById('tipo').value;
                const fechaDesde = document.getElementById('fecha_desde').value;
                const fechaHasta = document.getElementById('fecha_hasta').value;
                
                const filas = document.querySelectorAll('#tablaRequerimientos tbody tr');
                
                // DEBUG: Ver qué estamos filtrando
                console.log('Filtros activos:', { codigo, estado, tipo, fechaDesde, fechaHasta });
                
                filas.forEach(fila => {
                    let mostrar = true;
                    
                    // Obtener valores de la fila
                    const codigoFila = fila.cells[0].textContent.toLowerCase().trim();
                    
                    // NUEVA CORRECCIÓN: Obtener el tipo del data attribute o del PHP directamente
                    const tipoBadge = fila.cells[1].querySelector('.badge');
                    let tipoFila = '';
                    if (tipoBadge) {
                        // Extraer solo la palabra "Bien" o "Servicio" del texto completo
                        const textoCompleto = tipoBadge.textContent;
                        // Buscar la palabra exacta usando regex
                        const matchBien = textoCompleto.match(/Bien/);
                        const matchServicio = textoCompleto.match(/Servicio/);
                        
                        if (matchBien) {
                            tipoFila = 'Bien';
                        } else if (matchServicio) {
                            tipoFila = 'Servicio';
                        }
                    }
                    
                    const estadoBadge = fila.cells[5].querySelector('.badge');
                    const estadoFila = estadoBadge ? estadoBadge.textContent.toLowerCase().trim() : '';
                    
                    // Fecha en formato dd/mm/yyyy - convertir a yyyy-mm-dd
                    const fechaTexto = fila.cells[4].textContent.trim().split(' ')[0];
                    const fechaPartes = fechaTexto.split('/');
                    const fechaFila = fechaPartes.length === 3 ? 
                        `${fechaPartes[2]}-${fechaPartes[1]}-${fechaPartes[0]}` : '';
                    
                    // DEBUG: Ver valores extraídos de la primera fila
                    if (fila === filas[0]) {
                        console.log('Primera fila - valores extraídos:', {
                            codigoFila,
                            tipoFila,
                            estadoFila,
                            fechaFila
                        });
                    }
                    
                    // FILTRO POR CÓDIGO (independiente)
                    if (codigo && !codigoFila.includes(codigo)) {
                        mostrar = false;
                    }
                    
                    // FILTRO POR TIPO (independiente) - Comparación exacta
                    if (tipo && tipoFila !== tipo) {
                        mostrar = false;
                    }
                    
                    // FILTRO POR ESTADO (independiente)
                    if (estado) {
                        // Normalizar estado para comparación
                        const estadoNormalizado = estado.replace('_', ' ');
                        if (!estadoFila.includes(estadoNormalizado)) {
                            mostrar = false;
                        }
                    }
                    
                    // FILTRO POR FECHA DESDE (independiente)
                    if (fechaDesde && fechaFila && fechaFila < fechaDesde) {
                        mostrar = false;
                    }
                    
                    // FILTRO POR FECHA HASTA (independiente)
                    if (fechaHasta && fechaFila && fechaFila > fechaHasta) {
                        mostrar = false;
                    }
                    
                    // Mostrar u ocultar fila - FORZAR con !important mediante removeAttribute
                    if (mostrar) {
                        fila.removeAttribute('style'); // Eliminar completamente el atributo style
                        fila.classList.remove('d-none');
                    } else {
                        fila.removeAttribute('style'); // Eliminar completamente el atributo style
                        fila.classList.add('d-none');
                    }
                });
                
                // DEBUG: Contar cuántas filas quedaron visibles
                const filasVisibles = Array.from(filas).filter(f => !f.classList.contains('d-none')).length;
                console.log('Filas visibles después del filtro:', filasVisibles);
            }, 100); // Esperar 100ms para ejecutar después de otros scripts
        }

        // Función para limpiar filtros
        function limpiarFiltros() {
            document.getElementById('codigo').value = '';
            document.getElementById('estado').value = '';
            document.getElementById('tipo').value = '';
            document.getElementById('fecha_desde').value = '';
            document.getElementById('fecha_hasta').value = '';
            
            // Mostrar todas las filas - limpiar estilos inline y clases
            const filas = document.querySelectorAll('#tablaRequerimientos tbody tr');
            filas.forEach(fila => {
                fila.style.display = ''; // Limpiar estilos inline
                fila.classList.remove('d-none');
            });
        }

        // Event listeners para filtros en tiempo real
        document.getElementById('codigo').addEventListener('input', filtrarTabla);
        document.getElementById('estado').addEventListener('change', filtrarTabla);
        document.getElementById('tipo').addEventListener('change', filtrarTabla);
        document.getElementById('fecha_desde').addEventListener('change', filtrarTabla);
        document.getElementById('fecha_hasta').addEventListener('change', filtrarTabla);
        document.getElementById('limpiarFiltros').addEventListener('click', limpiarFiltros);

        // NO filtrar al cargar la página - dejar todas las filas visibles inicialmente
        document.addEventListener('DOMContentLoaded', function() {
            // Asegurarse de que todas las filas sean visibles al inicio
            const filas = document.querySelectorAll('#tablaRequerimientos tbody tr');
            filas.forEach(fila => {
                fila.style.display = ''; // Limpiar cualquier estilo inline
                fila.classList.remove('d-none');
            });
        });
    </script>
</body>
</html>