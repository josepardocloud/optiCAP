<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Manejar peticiones AJAX para obtener actividades
if (isset($_GET['action']) && $_GET['action'] == 'get_actividades' && isset($_GET['proceso_id'])) {
    $proceso_id = $_GET['proceso_id'];
    
    $query = "SELECT id, orden, nombre FROM actividades WHERE proceso_id = ? AND activo = 1 ORDER BY orden";
    $stmt = $db->prepare($query);
    $stmt->execute([$proceso_id]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($actividades);
    exit();
}

$mensaje = '';
$error = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        $db->beginTransaction();
        
        if ($action == 'crear') {
            // Crear nueva actividad
            $query = "INSERT INTO actividades (proceso_id, nombre, descripcion, orden, tiempo_dias, actividad_anterior_id, sla_objetivo, activo) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['proceso_id'],
                $_POST['nombre'],
                $_POST['descripcion'] ?? null,
                $_POST['orden'],
                $_POST['tiempo_dias'],
                $_POST['actividad_anterior_id'] ?: null,
                $_POST['sla_objetivo'] ?: null,
                isset($_POST['activo']) ? 1 : 0
            ]);
            
            $mensaje = "Actividad creada exitosamente";
            
        } elseif ($action == 'editar' && isset($_POST['id'])) {
            // Editar actividad existente
            $query = "UPDATE actividades SET 
                     proceso_id = ?, nombre = ?, descripcion = ?, orden = ?, tiempo_dias = ?, 
                     actividad_anterior_id = ?, sla_objetivo = ?, activo = ? 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['proceso_id'],
                $_POST['nombre'],
                $_POST['descripcion'] ?? null,
                $_POST['orden'],
                $_POST['tiempo_dias'],
                $_POST['actividad_anterior_id'] ?: null,
                $_POST['sla_objetivo'] ?: null,
                isset($_POST['activo']) ? 1 : 0,
                $_POST['id']
            ]);
            
            $mensaje = "Actividad actualizada exitosamente";
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al guardar la actividad: " . $e->getMessage();
    }
    
    // Redirección corregida - usar ruta completa
    $redirect_url = "/opticap/modules/procesos/actividades.php";
    if ($mensaje) {
        $redirect_url .= "?mensaje=" . urlencode($mensaje);
    } elseif ($error) {
        $redirect_url .= "?error=" . urlencode($error);
    }
    
    header("Location: $redirect_url");
    exit();
}

// Manejar acciones GET (activar/desactivar)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $actividad_id = $_GET['id'];
    
    try {
        if ($action == 'activar') {
            $query = "UPDATE actividades SET activo = 1 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$actividad_id]);
            $mensaje = "Actividad activada exitosamente";
            
        } elseif ($action == 'desactivar') {
            $query = "UPDATE actividades SET activo = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$actividad_id]);
            $mensaje = "Actividad desactivada exitosamente";
        }
        
    } catch (Exception $e) {
        $error = "Error al ejecutar la acción: " . $e->getMessage();
    }
    
    // Redirección corregida - usar ruta completa
    $redirect_url = "/opticap/modules/procesos/actividades.php";
    if ($mensaje) {
        $redirect_url .= "?mensaje=" . urlencode($mensaje);
    } elseif ($error) {
        $redirect_url .= "?error=" . urlencode($error);
    }
    
    header("Location: $redirect_url");
    exit();
}

// Si llega aquí sin ninguna acción válida, redirigir a actividades.php
header("Location: /opticap/modules/procesos/actividades.php");
exit();
?>