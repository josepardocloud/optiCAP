<?php
class Notificacion {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function enviarEmail($destinatario, $asunto, $mensaje, $adjuntos = []) {
        // Configurar PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            // Destinatarios
            $mail->setFrom(SMTP_USER, SITE_NAME);
            $mail->addAddress($destinatario);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $this->crearPlantillaEmail($mensaje);
            $mail->AltBody = strip_tags($mensaje);
            
            // Adjuntos
            foreach ($adjuntos as $adjunto) {
                $mail->addAttachment($adjunto['ruta'], $adjunto['nombre']);
            }
            
            $mail->send();
            $this->registrarNotificacionEmail($destinatario, $asunto, 'enviado');
            
        } catch (Exception $e) {
            $this->registrarNotificacionEmail($destinatario, $asunto, 'error', $e->getMessage());
            throw new Exception("Error al enviar email: " . $e->getMessage());
        }
    }
    
    public function notificarUsuario($usuarioId, $asunto, $mensaje, $enlace = null) {
        $pdo = $this->db->getConnection();
        
        // Obtener email del usuario
        $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $email = $stmt->fetchColumn();
        
        if ($email) {
            $this->enviarEmail($email, $asunto, $mensaje);
        }
        
        // Crear notificación interna
        $this->crearNotificacionInterna($usuarioId, $asunto, $mensaje, $enlace);
    }
    
    public function notificarRoles($roles, $asunto, $mensaje, $enlace = null) {
        $pdo = $this->db->getConnection();
        
        // Obtener usuarios con los roles especificados
        $placeholders = str_repeat('?,', count($roles) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT u.id, u.email 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE r.nombre IN ($placeholders) AND u.estado = 'activo'
        ");
        $stmt->execute($roles);
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($usuarios as $usuario) {
            if ($usuario['email']) {
                $this->enviarEmail($usuario['email'], $asunto, $mensaje);
            }
            $this->crearNotificacionInterna($usuario['id'], $asunto, $mensaje, $enlace);
        }
    }
    
    public function notificarAdministradores($asunto, $mensaje, $enlace = null) {
        $this->notificarRoles(['Administrador'], $asunto, $mensaje, $enlace);
    }
    
    private function crearNotificacionInterna($usuarioId, $titulo, $mensaje, $enlace = null) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO notificaciones (usuario_id, titulo, mensaje, enlace, fecha_envio) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$usuarioId, $titulo, $mensaje, $enlace]);
    }
    
    private function crearPlantillaEmail($mensaje) {
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
                    .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>" . SITE_NAME . "</h1>
                        <p>Sistema de Gestión de Procesos de Adquisición</p>
                    </div>
                    <div class='content'>
                        " . nl2br(htmlspecialchars($mensaje)) . "
                    </div>
                    <div class='footer'>
                        <p>Este es un mensaje automático del sistema " . SITE_NAME . ".</p>
                        <p>Por favor no responda a este correo.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
    
    private function registrarNotificacionEmail($destinatario, $asunto, $estado, $error = null) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_emails (destinatario, asunto, estado, error, fecha_envio) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$destinatario, $asunto, $estado, $error]);
    }
    
    public function obtenerNotificacionesUsuario($usuarioId, $noLeidas = false) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE usuario_id = ?";
        $params = [$usuarioId];
        
        if ($noLeidas) {
            $where .= " AND leida = 0";
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM notificaciones 
            $where 
            ORDER BY fecha_envio DESC 
            LIMIT 50
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function marcarComoLeida($notificacionId, $usuarioId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE notificaciones 
            SET leida = 1, fecha_leida = NOW() 
            WHERE id = ? AND usuario_id = ?
        ");
        
        $stmt->execute([$notificacionId, $usuarioId]);
        return $stmt->rowCount() > 0;
    }
    
    public function marcarTodasComoLeidas($usuarioId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE notificaciones 
            SET leida = 1, fecha_leida = NOW() 
            WHERE usuario_id = ? AND leida = 0
        ");
        
        $stmt->execute([$usuarioId]);
        return $stmt->rowCount();
    }
}
?>