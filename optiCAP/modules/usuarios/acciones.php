<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

if (!isset($_GET['action']) || !isset($_GET['id'])) {
    redirectTo('usuarios.php');
    exit();
}

$action = $_GET['action'];
$usuario_id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

$mensaje = '';
$error = '';

try {
    // Obtener nombre del usuario para el mensaje
    $query_nombre = "SELECT nombre FROM usuarios WHERE id = ?";
    $stmt_nombre = $db->prepare($query_nombre);
    $stmt_nombre->execute([$usuario_id]);
    $usuario = $stmt_nombre->fetch(PDO::FETCH_ASSOC);
    $nombre_usuario = $usuario ? $usuario['nombre'] : 'Usuario';

    switch ($action) {
        case 'desbloquear':
            $nueva_password = password_hash('password123', PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET bloqueado = 0, intentos_fallidos = 0, fecha_bloqueo = NULL, password = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$nueva_password, $usuario_id]);
            $mensaje = "Usuario {$nombre_usuario} desbloqueado exitosamente. Nueva contraseña: password123";
            break;
            
        case 'activar':
            $query = "UPDATE usuarios SET activo = 1, bloqueado = 0, intentos_fallidos = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$usuario_id]);
            $mensaje = "Usuario {$nombre_usuario} activado exitosamente";
            break;
            
        case 'desactivar':
            $query = "UPDATE usuarios SET activo = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$usuario_id]);
            $mensaje = "Usuario {$nombre_usuario} desactivado exitosamente";
            break;
            
        case 'reset_password':
            $nueva_password = password_hash('password123', PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET password = ?, intentos_fallidos = 0, bloqueado = 0, fecha_bloqueo = NULL WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$nueva_password, $usuario_id]);
            $mensaje = "Contraseña de {$nombre_usuario} reseteada exitosamente. Nueva contraseña: password123";
            break;
            
        default:
            $error = "Acción no válida";
            break;
    }
    
    // Registrar en logs de seguridad
    $query_log = "INSERT INTO logs_seguridad (usuario_id, ip, accion, resultado, detalles) VALUES (?, ?, ?, 'exito', ?)";
    $stmt_log = $db->prepare($query_log);
    $stmt_log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $action, $mensaje]);
    
} catch (Exception $e) {
    $error = "Error al ejecutar la acción: " . $e->getMessage();
    
    // Registrar error en logs
    $query_log = "INSERT INTO logs_seguridad (usuario_id, ip, accion, resultado, detalles) VALUES (?, ?, ?, 'fallo', ?)";
    $stmt_log = $db->prepare($query_log);
    $stmt_log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $action, $error]);
}

// Redirigir con parámetros GET en lugar de sesión
$redirect_url = "usuarios.php?";
if ($mensaje) {
    $redirect_url .= "accion_exitosa=" . urlencode($mensaje);
} elseif ($error) {
    $redirect_url .= "accion_error=" . urlencode($error);
}

redirectTo($redirect_url);
exit();
?>