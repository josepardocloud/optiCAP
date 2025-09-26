<?php
class EmailHelper {
    
    public function enviarNotificacion($destinatario, $asunto, $mensaje, $adjuntos = []) {
        // Configuración básica de PHPMailer o función mail() de PHP
        // Esta es una implementación básica usando mail()
        
        $headers = "From: " . Config::SMTP_FROM . "\r\n";
        $headers .= "Reply-To: " . Config::SMTP_FROM . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $mensajeHTML = "
        <html>
        <head>
            <title>{$asunto}</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background: #f8f9fa; padding: 10px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>" . Config::APP_NAME . "</h2>
                </div>
                <div class='content'>
                    {$mensaje}
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no responda a este correo.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return mail($destinatario, $asunto, $mensajeHTML, $headers);
    }
    
    public function notificarNuevoRequerimiento($requerimientoId, $usuariosNotificar) {
        $requerimientoModel = new Requerimiento();
        $requerimiento = $requerimientoModel->obtenerPorId($requerimientoId);
        
        $asunto = "Nuevo Requerimiento - " . $requerimiento['codigo'];
        $mensaje = "
        <h3>Se ha creado un nuevo requerimiento</h3>
        <p><strong>Código:</strong> {$requerimiento['codigo']}</p>
        <p><strong>Título:</strong> {$requerimiento['titulo']}</p>
        <p><strong>Área Solicitante:</strong> {$requerimiento['area_nombre']}</p>
        <p><strong>Descripción:</strong> {$requerimiento['descripcion']}</p>
        <p>Puede ver el requerimiento en el siguiente enlace:</p>
        <p><a href='" . Config::APP_URL . "requerimientos/detalle/{$requerimientoId}'>Ver Requerimiento</a></p>
        ";
        
        foreach ($usuariosNotificar as $usuario) {
            $this->enviarNotificacion($usuario['email'], $asunto, $mensaje);
        }
    }
    
    public function notificarActividadPendiente($seguimientoId, $usuario) {
        $seguimientoModel = new Seguimiento();
        $seguimiento = $seguimientoModel->obtenerPorId($seguimientoId);
        
        $asunto = "Actividad Pendiente - " . $seguimiento['codigo'];
        $mensaje = "
        <h3>Tienes una actividad pendiente</h3>
        <p><strong>Requerimiento:</strong> {$seguimiento['codigo']} - {$seguimiento['titulo']}</p>
        <p><strong>Actividad:</strong> {$seguimiento['actividad_nombre']}</p>
        <p><strong>Fecha Límite:</strong> " . date('d/m/Y', strtotime($seguimiento['fecha_fin_estimada'])) . "</p>
        <p>Puede ver la actividad en el siguiente enlace:</p>
        <p><a href='" . Config::APP_URL . "requerimientos/detalle/{$seguimiento['id_requerimiento']}'>Ver Actividad</a></p>
        ";
        
        $this->enviarNotificacion($usuario['email'], $asunto, $mensaje);
    }
}
?>