<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'crear':
        crearIncidencia($db);
        break;
    case 'ver':
        verIncidencia($db);
        break;
    case 'resolver':
        resolverIncidencia($db);
        break;
    default:
        redirect('modules/incidencias/incidencias.php?error=Acción no válida');
}

function crearIncidencia($db) {
    $requerimiento_id = $_POST['requerimiento_id'];
    $descripcion = $_POST['descripcion'];
    $usuario_id = $_SESSION['usuario_id'];
    $evidencia_nombre = '';
    
    // Manejar subida de archivo
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
        
        // Crear directorio si no existe
        $directorio_uploads = dirname(dirname(__FILE__)) . '/uploads/incidencias/';
        if (!is_dir($directorio_uploads)) {
            mkdir($directorio_uploads, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $evidencia_nombre = 'incidencia_' . time() . '_' . uniqid() . '.' . $extension;
        $ruta_destino = $directorio_uploads . $evidencia_nombre;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            redirect('modules/incidencias/incidencias.php?error=Error al subir la imagen');
        }
    }
    
    $query = "INSERT INTO incidencias (requerimiento_id, usuario_reporta_id, descripcion, evidencia_url, estado) 
              VALUES (?, ?, ?, ?, 'reportada')";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$requerimiento_id, $usuario_id, $descripcion, $evidencia_nombre])) {
        redirect('modules/incidencias/incidencias.php?mensaje=Incidencia reportada correctamente');
    } else {
        // Si hay error, eliminar archivo subido
        if ($evidencia_nombre && file_exists($ruta_destino)) {
            unlink($ruta_destino);
        }
        redirect('modules/incidencias/incidencias.php?error=Error al reportar la incidencia');
    }
}

function verIncidencia($db) {
    $incidencia_id = $_GET['id'];
    
    $query = "SELECT i.*, r.codigo, u.nombre as usuario_reporta_nombre, 
                     ur.nombre as usuario_resuelve_nombre, a.nombre as area_nombre
              FROM incidencias i 
              INNER JOIN requerimientos r ON i.requerimiento_id = r.id 
              INNER JOIN usuarios u ON i.usuario_reporta_id = u.id 
              LEFT JOIN usuarios ur ON i.usuario_resuelve_id = ur.id 
              INNER JOIN areas a ON r.area_id = a.id 
              WHERE i.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$incidencia_id]);
    $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($incidencia) {
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
                <p>' . nl2br(htmlspecialchars($incidencia['descripcion'])) . '</p>
            </div>
        </div>';
        
        if ($incidencia['evidencia_url']) {
            echo '
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Evidencia</h6>
                    <p>' . htmlspecialchars($incidencia['evidencia_url']) . '</p>
                </div>
            </div>';
        }
        
        if ($incidencia['solucion']) {
            echo '
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Solución</h6>
                    <p>' . nl2br(htmlspecialchars($incidencia['solucion'])) . '</p>
                    <p><strong>Resuelta por:</strong> ' . $incidencia['usuario_resuelve_nombre'] . '</p>
                    <p><strong>Fecha resolución:</strong> ' . date('d/m/Y H:i', strtotime($incidencia['fecha_resolucion'])) . '</p>
                </div>
            </div>';
        }
    } else {
        echo '<p>Incidencia no encontrada</p>';
    }
}

function resolverIncidencia($db) {
    $incidencia_id = $_POST['id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    $query = "UPDATE incidencias SET estado = 'resuelta', usuario_resuelve_id = ?, fecha_resolucion = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$usuario_id, $incidencia_id])) {
        echo json_encode(['success' => true, 'message' => 'Incidencia resuelta correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al resolver la incidencia']);
    }
}
?>