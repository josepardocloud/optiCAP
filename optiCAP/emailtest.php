/**
 * Función temporal para testing - guarda email como archivo
 */
function enviarEmailTesting($destinatario, $asunto, $contenido, $config) {
    $email_content = "To: $destinatario\n";
    $email_content .= "Subject: $asunto\n";
    $email_content .= "Content:\n$contenido\n";
    $email_content .= "====================\n";
    
    $test_dir = dirname(dirname(__DIR__)) . '/logs/emails_test/';
    if (!is_dir($test_dir)) {
        mkdir($test_dir, 0755, true);
    }
    
    $filename = $test_dir . 'email_' . date('Y-m-d_H-i-s') . '.txt';
    file_put_contents($filename, $email_content);
    
    error_log("Email guardado para testing: $filename");
    return true;
}