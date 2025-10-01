<?php
// Corregir rutas relativas
$root_path = dirname(dirname(__FILE__));
require_once $root_path . '/config/database.php';

function obtenerUsuario($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function generarCodigoRequerimiento($tipo) {
    $database = new Database();
    $db = $database->getConnection();
    
    $anio = date('Y');
    $prefijo = $tipo == 'Bien' ? 'BIEN' : 'SERV';
    
    $query = "SELECT COUNT(*) as total FROM requerimientos WHERE codigo LIKE ? AND YEAR(fecha_creacion) = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$prefijo . '-' . $anio . '-%', $anio]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $numero = str_pad($resultado['total'] + 1, 4, '0', STR_PAD_LEFT);
    return $prefijo . '-' . $anio . '-' . $numero;
}

function puedeModificarActividad($usuario_id, $actividad_id, $requerimiento_id) {
    return tienePermisoModificar($usuario_id, $actividad_id) && 
           actividadHabilitadaPorSecuencia($actividad_id, $requerimiento_id) && 
           usuarioPuedeCrearRequerimientos($usuario_id);
}

function tienePermisoModificar($usuario_id, $actividad_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as total FROM permisos_actividades 
              WHERE usuario_id = ? AND actividad_id = ? AND permiso_modificar = 1 AND activo = 1 
              AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_id, $actividad_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['total'] > 0;
}

function actividadHabilitadaPorSecuencia($actividad_id, $requerimiento_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener actividad anterior
    $query = "SELECT actividad_anterior_id FROM actividades WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$actividad_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad['actividad_anterior_id']) {
        return true; // Primera actividad
    }
    
    // Verificar si la actividad anterior está completada
    $query = "SELECT estado FROM seguimiento_actividades 
              WHERE requerimiento_id = ? AND actividad_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$requerimiento_id, $actividad['actividad_anterior_id']]);
    $seguimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $seguimiento && $seguimiento['estado'] == 'completado';
}

function usuarioPuedeCrearRequerimientos($usuario_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT rol FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return in_array($usuario['rol'], ['usuario', 'super_usuario', 'supervisor']);
}

function puedeVerRequerimiento($usuario_id, $requerimiento_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT rol, area_id FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario['rol'] == 'usuario') {
        $query = "SELECT area_id FROM requerimientos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$requerimiento_id]);
        $requerimiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $usuario['area_id'] == $requerimiento['area_id'];
    }
    
    return in_array($usuario['rol'], ['super_usuario', 'supervisor', 'administrador']);
}

function obtenerRequerimientosPorRol($usuario_id, $rol, $area_id) {
    switch($rol) {
        case 'usuario':
            return "WHERE r.area_id = $area_id";
        case 'super_usuario':
        case 'supervisor':
        case 'administrador':
            return ""; // Sin filtro
        default:
            return "WHERE 1=0"; // Sin acceso
    }
}

// Función para obtener la ruta base
function getBasePath() {
    return dirname(dirname(__FILE__));
}

// Función para generar rutas absolutas
function getAbsolutePath($relative_path) {
    $base_path = '/opticap/';
    return $base_path . ltrim($relative_path, '/');
}

// Función para redireccionar con rutas absolutas
function redirect($path) {
    $absolute_path = getAbsolutePath($path);
    header("Location: " . $absolute_path);
    exit();
}

// Función para incluir archivos de forma segura con rutas absolutas
function requireSafe($path) {
    $root_path = dirname(dirname(__FILE__));
    $full_path = $root_path . '/' . ltrim($path, '/');
    if (file_exists($full_path)) {
        require_once $full_path;
    } else {
        throw new Exception("Archivo no encontrado: " . $full_path);
    }
}

?>