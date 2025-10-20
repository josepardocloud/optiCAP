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
function obtenerKPIsRequerimientos($usuario_id, $rol, $area_id) {
    global $db;
    
    $filtro_rol = obtenerRequerimientosPorRol($usuario_id, $rol, $area_id);
    
    // Contar por estados
    $query = "SELECT estado, COUNT(*) as total 
              FROM requerimientos r 
              $filtro_rol 
              GROUP BY estado";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totales
    $kpis = [
        'total' => 0,
        'pendientes' => 0,
        'en_proceso' => 0,
        'completados' => 0,
        'cancelados' => 0,
        'tasa_exito' => 0
    ];
    
    foreach ($estados as $estado) {
        $kpis['total'] += $estado['total'];
        switch($estado['estado']) {
            case 'pendiente': $kpis['pendientes'] = $estado['total']; break;
            case 'en_proceso': $kpis['en_proceso'] = $estado['total']; break;
            case 'completado': $kpis['completados'] = $estado['total']; break;
            case 'cancelado': $kpis['cancelados'] = $estado['total']; break;
        }
    }
    
    // Calcular tasa de éxito (completados / total excluyendo cancelados)
    $total_valido = $kpis['total'] - $kpis['cancelados'];
    if ($total_valido > 0) {
        $kpis['tasa_exito'] = round(($kpis['completados'] / $total_valido) * 100, 1);
    }
    
    return $kpis;
}
/**
 * Enviar notificación por email
 */
function enviarNotificacionEmail($tipo, $datos, $destinatarios = []) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Obtener configuración SMTP
        $query_config = "SELECT * FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
        $stmt_config = $db->prepare($query_config);
        $stmt_config->execute();
        $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si el email está activo
        if (!$config || !$config['email_activo']) {
            return false;
        }
        
        // Verificar si el evento de notificación está activo
        $query_evento = "SELECT * FROM eventos_notificacion WHERE nombre = ? AND activo = 1";
        $stmt_evento = $db->prepare($query_evento);
        $stmt_evento->execute([$tipo]);
        $evento = $stmt_evento->fetch(PDO::FETCH_ASSOC);
        
        if (!$evento) {
            return false;
        }
        
        // Obtener plantilla de email
        $query_plantilla = "SELECT * FROM plantillas_email WHERE tipo = ? AND activa = 1";
        $stmt_plantilla = $db->prepare($query_plantilla);
        $stmt_plantilla->execute([$tipo]);
        $plantilla = $stmt_plantilla->fetch(PDO::FETCH_ASSOC);
        
        if (!$plantilla) {
            return false;
        }
        
        // Reemplazar variables en la plantilla
        $asunto = $plantilla['asunto'];
        $contenido = $plantilla['contenido'];
        
        foreach ($datos as $key => $value) {
            $asunto = str_replace('{' . $key . '}', $value, $asunto);
            $contenido = str_replace('{' . $key . '}', $value, $contenido);
        }
        
        // Si no hay destinatarios específicos, obtener usuarios que deben recibir notificaciones
        if (empty($destinatarios)) {
            $destinatarios = obtenerDestinatariosNotificacion($tipo, $datos);
        }
        
        // Enviar email a cada destinatario
        $enviados = 0;
        foreach ($destinatarios as $destinatario) {
            if (enviarEmailReal($destinatario, $asunto, $contenido, $config)) {
                $enviados++;
            }
        }
        
        // Guardar en log
        guardarLogNotificacion($tipo, $destinatarios, $asunto, $contenido, $enviados);
        
        return $enviados > 0;
        
    } catch (Exception $e) {
        error_log("Error enviando notificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener destinatarios para notificación
 */
function obtenerDestinatariosNotificacion($tipo, $datos) {
    $database = new Database();
    $db = $database->getConnection();
    $destinatarios = [];
    
    switch ($tipo) {
        case 'nuevo_requerimiento':
            // Notificar a super usuarios y administradores
            $query = "SELECT email FROM usuarios WHERE rol IN ('administrador', 'super_usuario') AND activo = 1 AND email IS NOT NULL";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $destinatarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
            break;
            
        case 'actividad_asignada':
            // Notificar al usuario asignado
            if (isset($datos['usuario_email'])) {
                $destinatarios[] = $datos['usuario_email'];
            }
            break;
    }
    
    return $destinatarios;
}

/**
 * Enviar email real usando PHPMailer
 */
function enviarEmailReal($destinatario, $asunto, $contenido, $config) {
    try {
        // DEBUG: Verificar configuración
        error_log("=== DEBUG EMAIL INICIADO ===");
        error_log("Destinatario: $destinatario");
        error_log("SMTP Host: " . ($config['smtp_host'] ?? 'No configurado'));
        error_log("SMTP User: " . ($config['smtp_user'] ?? 'No configurado'));
        error_log("SMTP Port: " . ($config['smtp_port'] ?? 'No configurado'));
        
        // Verificar que tenemos configuración mínima
        if (empty($config['smtp_host']) || empty($config['smtp_user']) || empty($config['smtp_pass'])) {
            error_log("ERROR: Configuración SMTP incompleta");
            return false;
        }
        
        // Incluir PHPMailer
        require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/phpmailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_pass'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'] ?? 587;
        
        // Configuración para debug
        $mail->SMTPDebug = 2; // Habilita debug detallado
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug (Nivel $level): $str");
        };
        
        // Configuración del email
        $from_email = $config['from_email'] ?? 'sistema@opticap.com';
        $from_name = $config['from_name'] ?? 'Sistema OptiCAP';
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($destinatario);
        $mail->addReplyTo($from_email, $from_name);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        
        // Template HTML mejorado
        $contenido_html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>$asunto</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .footer { background: #343a40; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .info-box { background: white; padding: 20px; border-radius: 5px; border-left: 4px solid #007bff; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>" . ($config['nombre_sistema'] ?? 'OptiCAP') . "</h2>
                    <p>Sistema de Gestión de Requerimientos</p>
                </div>
                <div class='content'>
                    $contenido
                    <div class='info-box'>
                        <p><strong>Fecha:</strong> " . date('d/m/Y H:i') . "</p>
                        <p><strong>Sistema:</strong> " . ($config['nombre_sistema'] ?? 'OptiCAP') . "</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no responda a este email.</p>
                    <p>&copy; " . date('Y') . " " . ($config['nombre_sistema'] ?? 'OptiCAP') . " - Todos los derechos reservados</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $contenido_html;
        $mail->AltBody = strip_tags($contenido);
        
        // Enviar email
        error_log("Intentando enviar email a: $destinatario");
        $mail->send();
        error_log("EMAIL ENVIADO EXITOSAMENTE a: $destinatario");
        return true;
        
    } catch (Exception $e) {
        error_log("ERROR PHPMailer: " . $e->getMessage());
        return false;
    }
}

/**
 * Guardar log de notificación
 */
function guardarLogNotificacion($tipo, $destinatarios, $asunto, $contenido, $enviados) {
    $log = "[" . date('Y-m-d H:i:s') . "] Notificación: $tipo\n";
    $log .= "Destinatarios: " . implode(', ', $destinatarios) . "\n";
    $log .= "Asunto: $asunto\n";
    $log .= "Contenido: " . strip_tags($contenido) . "\n";
    $log .= "Emails enviados: $enviados\n";
    $log .= "-------------------------\n";
    
    // Asegurar que el directorio de logs existe - CORREGIDO
    $log_dir = dirname(dirname(__DIR__)) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/notificaciones.log';
    file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);
    
    // También mostrar en error_log para debugging
    error_log("Notificación registrada: $tipo - Enviados: $enviados");
}
?>