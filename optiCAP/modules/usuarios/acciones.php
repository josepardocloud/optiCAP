<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

if (!isset($_GET['action']) || !isset($_GET['id'])) {
    redirectTo('modules/usuarios/usuarios.php');
    exit();
}

$action = $_GET['action'];
$usuario_id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

try {
    // Obtener nombre del usuario para el mensaje
    $query_nombre = "SELECT nombre FROM usuarios WHERE id = ?";
    $stmt_nombre = $db->prepare($query_nombre);
    $stmt_nombre->execute([$usuario_id]);
    $usuario = $stmt_nombre->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $_SESSION['accion_error'] = "Usuario no encontrado";
    } else {
        $nombre_usuario = $usuario['nombre'];

        switch ($action) {
            case 'desbloquear':
                $nueva_password = password_hash('password123', PASSWORD_DEFAULT);
                $query = "UPDATE usuarios SET bloqueado = 0, intentos_fallidos = 0, fecha_bloqueo = NULL, password = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$nueva_password, $usuario_id])) {
                    $_SESSION['accion_exitosa'] = "Usuario {$nombre_usuario} desbloqueado exitosamente. Nueva contraseña: password123";
                } else {
                    $_SESSION['accion_error'] = "Error al desbloquear al usuario {$nombre_usuario}";
                }
                break;
                
            case 'activar':
                $query = "UPDATE usuarios SET activo = 1, bloqueado = 0, intentos_fallidos = 0 WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$usuario_id])) {
                    $_SESSION['accion_exitosa'] = "Usuario {$nombre_usuario} activado exitosamente";
                } else {
                    $_SESSION['accion_error'] = "Error al activar al usuario {$nombre_usuario}";
                }
                break;
                
            case 'desactivar':
                $query = "UPDATE usuarios SET activo = 0 WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$usuario_id])) {
                    $_SESSION['accion_exitosa'] = "Usuario {$nombre_usuario} desactivado exitosamente";
                } else {
                    $_SESSION['accion_error'] = "Error al desactivar al usuario {$nombre_usuario}";
                }
                break;
                
            case 'reset_password':
                $nueva_password = password_hash('password123', PASSWORD_DEFAULT);
                $query = "UPDATE usuarios SET password = ?, intentos_fallidos = 0, bloqueado = 0, fecha_bloqueo = NULL WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$nueva_password, $usuario_id])) {
                    $_SESSION['accion_exitosa'] = "Contraseña de {$nombre_usuario} reseteada exitosamente. Nueva contraseña: password123";
                } else {
                    $_SESSION['accion_error'] = "Error al resetear la contraseña del usuario {$nombre_usuario}";
                }
                break;
                
            default:
                $_SESSION['accion_error'] = "Acción no válida";
                break;
        }
        
        // Registrar en logs de seguridad solo si no hay error
        if (isset($_SESSION['accion_exitosa'])) {
            $query_log = "INSERT INTO logs_seguridad (usuario_id, ip, accion, resultado, detalles) VALUES (?, ?, ?, 'exito', ?)";
            $stmt_log = $db->prepare($query_log);
            $stmt_log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $action, $_SESSION['accion_exitosa']]);
        }
    }
    
} catch (Exception $e) {
    $_SESSION['accion_error'] = "Error al ejecutar la acción: " . $e->getMessage();
    
    // Registrar error en logs
    $query_log = "INSERT INTO logs_seguridad (usuario_id, ip, accion, resultado, detalles) VALUES (?, ?, ?, 'fallo', ?)";
    $stmt_log = $db->prepare($query_log);
    $stmt_log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $action, $_SESSION['accion_error']]);
}

// Redirigir SIN parámetros en la URL
redirectTo("modules/usuarios/usuarios.php");
exit();
?>