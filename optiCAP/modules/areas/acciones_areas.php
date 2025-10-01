<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

$mensaje = '';
$error = '';

// Procesar formulario de creación/edición
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action == 'crear') {
            // Crear nueva área
            $query = "INSERT INTO areas (nombre, descripcion, activo) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['nombre'],
                $_POST['descripcion'] ?? null,
                isset($_POST['activo']) ? 1 : 0
            ]);
            
            $mensaje = "Área creada exitosamente";
            
        } elseif ($action == 'editar' && isset($_POST['id'])) {
            // Editar área existente
            $query = "UPDATE areas SET nombre = ?, descripcion = ?, activo = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['nombre'],
                $_POST['descripcion'] ?? null,
                isset($_POST['activo']) ? 1 : 0,
                $_POST['id']
            ]);
            
            $mensaje = "Área actualizada exitosamente";
        }
        
    } catch (Exception $e) {
        $error = "Error al guardar el área: " . $e->getMessage();
    }
    
    $redirect_url = "areas.php";
    if ($mensaje) {
        $redirect_url .= "?mensaje=" . urlencode($mensaje);
    } elseif ($error) {
        $redirect_url .= "?error=" . urlencode($error);
    }
    
    redirectTo($redirect_url);
    exit();
}

// Manejar acciones GET (activar/desactivar)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $area_id = $_GET['id'];
    
    try {
        if ($action == 'activar') {
            $query = "UPDATE areas SET activo = 1 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$area_id]);
            $mensaje = "Área activada exitosamente";
            
        } elseif ($action == 'desactivar') {
            $query = "UPDATE areas SET activo = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$area_id]);
            $mensaje = "Área desactivada exitosamente";
        }
        
    } catch (Exception $e) {
        $error = "Error al ejecutar la acción: " . $e->getMessage();
    }
    
    $redirect_url = "areas.php";
    if ($mensaje) {
        $redirect_url .= "?mensaje=" . urlencode($mensaje);
    } elseif ($error) {
        $redirect_url .= "?error=" . urlencode($error);
    }
    
    redirectTo($redirect_url);
    exit();
}
?>