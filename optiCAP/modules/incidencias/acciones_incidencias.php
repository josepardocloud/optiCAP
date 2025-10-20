<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

$database = new Database();
$db = $database->getConnection();

// Configuración de rutas
$directorio_base = $_SERVER['DOCUMENT_ROOT'] . '/opticap/';
$directorio_uploads = $directorio_base . 'uploads/incidencias/';
$url_base = '/opticap/';
$url_uploads = $url_base . 'uploads/incidencias/';

// Verificar y crear directorio de uploads si no existe
if (!is_dir($directorio_uploads)) {
    mkdir($directorio_uploads, 0755, true);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'crear':
        crearIncidencia($db, $directorio_uploads);
        break;
    case 'ver':
        verIncidencia($db, $url_uploads, $directorio_uploads);
        break;
    case 'resolver':
        resolverIncidencia($db);
        break;
    case 'mostrar_modal_resolver':
        mostrarModalResolver($db);
        break;
    default:
        redirect('modules/incidencias/incidencias.php?error=Acción no válida');
}

function crearIncidencia($db, $directorio_uploads) {
    // Verificar que solo usuarios normales puedan reportar
    if ($_SESSION['rol'] == 'administrador') {
        redirect('modules/incidencias/incidencias.php?error=Los administradores no pueden reportar incidencias');
    }
    
    $requerimiento_id = $_POST['requerimiento_id'];
    $descripcion = $_POST['descripcion'];
    $usuario_id = $_SESSION['usuario_id'];
    $evidencia_archivo = '';
    
    // Manejar subida de archivo (código existente)
    if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['evidencia'];
        
        // Validaciones
        $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $tamano_maximo = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            redirect('modules/incidencias/incidencias.php?error=Tipo de archivo no permitido. Solo se aceptan imágenes JPG, PNG o GIF');
        }
        
        if ($archivo['size'] > $tamano_maximo) {
            redirect('modules/incidencias/incidencias.php?error=La imagen no debe superar los 2MB');
        }
        
        // Generar nombre único para el archivo
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $evidencia_archivo = 'incidencia_' . time() . '_' . uniqid() . '.' . $extension;
        $ruta_destino = $directorio_uploads . $evidencia_archivo;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            redirect('modules/incidencias/incidencias.php?error=Error al subir la imagen');
        }
    }
    
    $query = "INSERT INTO incidencias (requerimiento_id, usuario_reporta_id, descripcion, evidencia_archivo, estado) 
              VALUES (?, ?, ?, ?, 'reportada')";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$requerimiento_id, $usuario_id, $descripcion, $evidencia_archivo])) {
        redirect('modules/incidencias/incidencias.php?mensaje=Incidencia reportada correctamente');
    } else {
        // Si hay error, eliminar archivo subido
        if ($evidencia_archivo && file_exists($ruta_destino)) {
            unlink($ruta_destino);
        }
        redirect('modules/incidencias/incidencias.php?error=Error al reportar la incidencia');
    }
}

function verIncidencia($db, $url_uploads, $directorio_uploads) {
    $incidencia_id = $_GET['id'];
    
    // Verificar permisos de visualización
    $usuario_id = $_SESSION['usuario_id'];
    $rol = $_SESSION['rol'];
    
    $query = "SELECT i.*, r.codigo, u.nombre as usuario_reporta_nombre, 
                     ur.nombre as usuario_resuelve_nombre, a.nombre as area_nombre
              FROM incidencias i 
              INNER JOIN requerimientos r ON i.requerimiento_id = r.id 
              INNER JOIN usuarios u ON i.usuario_reporta_id = u.id 
              LEFT JOIN usuarios ur ON i.usuario_resuelve_id = ur.id 
              INNER JOIN areas a ON r.area_id = a.id 
              WHERE i.id = ?";
    
    // Si no es administrador, solo puede ver sus propias incidencias
    if ($rol != 'administrador') {
        $query .= " AND i.usuario_reporta_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$incidencia_id, $usuario_id]);
    } else {
        $stmt = $db->prepare($query);
        $stmt->execute([$incidencia_id]);
    }
    
    $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($incidencia) {
        // ... (código existente para mostrar detalles)
        echo '
        <div class="row">
            <div class="col-md-6">
                <h6>Información General</h6>
                <p><strong>Requerimiento:</strong> ' . $incidencia['codigo'] . '</p>
                <p><strong>Área:</strong> ' . $incidencia['area_nombre'] . '</p>
                <p><strong>Reportada por:</strong> ' . $incidencia['usuario_reporta_nombre'] . '</p>
                <p><strong>Fecha:</strong> ' . date('d/m/Y H:i', strtotime($incidencia['fecha_reporte'])) . '</p>
            </div>
            <div class="col-md-6">
                <h6>Estado</h6>
                <span class="badge bg-' . ($incidencia['estado'] == 'reportada' ? 'warning' : ($incidencia['estado'] == 'en_revision' ? 'info' : 'success')) . '">
                    ' . ucfirst(str_replace('_', ' ', $incidencia['estado'])) . '
                </span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Descripción</h6>
                <div class="border rounded p-3 bg-light">
                    ' . nl2br(htmlspecialchars($incidencia['descripcion'])) . '
                </div>
            </div>
        </div>';
        
        // Mostrar evidencia (código existente)
        if ($incidencia['evidencia_archivo']) {
            $ruta_archivo = $directorio_uploads . $incidencia['evidencia_archivo'];
            $ruta_web = $url_uploads . $incidencia['evidencia_archivo'];
            
            $archivo_existe = file_exists($ruta_archivo);
            
            echo '
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Evidencia Adjunta</h6>
                    <div class="border rounded p-3">';
            
            if ($archivo_existe) {
                echo '
                        <div class="text-center">
                            <img src="' . $ruta_web . '" class="img-fluid rounded shadow-sm modal-img" alt="Evidencia de incidencia">
                            <div class="mt-3">
                                <small class="text-muted d-block">Archivo: ' . htmlspecialchars($incidencia['evidencia_archivo']) . '</small>
                                <div class="mt-2">
                                    <a href="' . $ruta_web . '" download="' . $incidencia['evidencia_archivo'] . '" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Descargar Imagen
                                    </a>
                                    <a href="' . $ruta_web . '" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-external-link-alt me-1"></i> Abrir en nueva pestaña
                                    </a>
                                </div>
                            </div>
                        </div>';
            } else {
                echo '
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Archivo no encontrado</strong><br>
                            El archivo de evidencia existe en la base de datos pero no se encuentra en el servidor.
                        </div>';
            }
            
            echo '
                    </div>
                </div>
            </div>';
        }
        
        if ($incidencia['solucion']) {
            echo '
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Solución</h6>
                    <div class="border rounded p-3 bg-light">
                        ' . nl2br(htmlspecialchars($incidencia['solucion'])) . '
                    </div>
                    <div class="mt-2">
                        <p><strong>Resuelta por:</strong> ' . $incidencia['usuario_resuelve_nombre'] . '</p>
                        <p><strong>Fecha resolución:</strong> ' . date('d/m/Y H:i', strtotime($incidencia['fecha_resolucion'])) . '</p>
                    </div>
                </div>
            </div>';
        }
        
        // Botón para resolver (solo administrador y si no está resuelta)
        if ($_SESSION['rol'] == 'administrador' && $incidencia['estado'] != 'resuelta') {
            echo '
            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-success" onclick="mostrarModalResolver(' . $incidencia['id'] . ')">
                        <i class="fas fa-check me-1"></i> Resolver Incidencia
                    </button>
                </div>
            </div>';
        }
        
    } else {
        echo '<div class="alert alert-danger">Incidencia no encontrada o no tienes permisos para verla</div>';
    }
}

function mostrarModalResolver($db) {
    $incidencia_id = $_GET['id'];
    
    // Verificar que sea administrador
    if ($_SESSION['rol'] != 'administrador') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta acción']);
        exit;
    }
    
    $query = "SELECT i.*, r.codigo FROM incidencias i 
              INNER JOIN requerimientos r ON i.requerimiento_id = r.id 
              WHERE i.id = ? AND i.estado != 'resuelta'";
    $stmt = $db->prepare($query);
    $stmt->execute([$incidencia_id]);
    $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($incidencia) {
        echo '
        <form id="formResolverIncidencia" onsubmit="enviarSolucion(event, ' . $incidencia_id . ')">
            <div class="mb-3">
                <label for="solucion" class="form-label">Solución de la Incidencia *</label>
                <textarea class="form-control" id="solucion" name="solucion" rows="6" 
                          placeholder="Describa detalladamente la solución aplicada para resolver esta incidencia..." required></textarea>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Al guardar, la incidencia será marcada como <strong>resuelta</strong> y no podrá ser modificada.
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Marcar como Resuelta
                </button>
            </div>
        </form>';
    } else {
        echo '<div class="alert alert-danger">Incidencia no encontrada o ya está resuelta</div>';
    }
}

function resolverIncidencia($db) {
    // Verificar que solo administradores puedan resolver
    if ($_SESSION['rol'] != 'administrador') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para resolver incidencias']);
        exit;
    }
    
    $incidencia_id = $_POST['id'];
    $solucion = $_POST['solucion'];
    $usuario_id = $_SESSION['usuario_id'];
    
    if (empty($solucion)) {
        echo json_encode(['success' => false, 'message' => 'La solución es obligatoria']);
        exit;
    }
    
    $query = "UPDATE incidencias SET estado = 'resuelta', usuario_resuelve_id = ?, 
              fecha_resolucion = NOW(), solucion = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$usuario_id, $solucion, $incidencia_id])) {
        echo json_encode(['success' => true, 'message' => 'Incidencia resuelta correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al resolver la incidencia']);
    }
}
?>