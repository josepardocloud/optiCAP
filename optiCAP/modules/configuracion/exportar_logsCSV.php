<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener todos los logs de seguridad
$query_logs = "SELECT ls.*, u.nombre as usuario_nombre 
               FROM logs_seguridad ls 
               LEFT JOIN usuarios u ON ls.usuario_id = u.id 
               ORDER BY ls.fecha DESC";
$stmt_logs = $db->prepare($query_logs);
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para descarga de archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=logs_seguridad_' . date('Y-m-d_H-i') . '.csv');

// Crear output stream
$output = fopen('php://output', 'w');

// Agregar BOM para UTF-8 (ayuda con Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados del CSV
$headers = array('Fecha', 'Usuario', 'Acción', 'IP', 'User Agent', 'Resultado', 'Detalles');
fputcsv($output, $headers);

// Llenar con datos
foreach ($logs as $log) {
    $fila = array(
        $log['fecha'],
        $log['usuario_nombre'] ?: 'Sistema',
        $log['accion'],
        $log['ip'],
        $log['user_agent'] ?: 'N/A',
        ucfirst($log['resultado']),
        $log['detalles'] ?: 'N/A'
    );
    fputcsv($output, $fila);
}

fclose($output);
exit;
?>