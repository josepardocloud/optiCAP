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

// Configurar headers para descarga de archivo TXT
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename=logs_seguridad_' . date('Y-m-d_H-i') . '.txt');

// Encabezado del archivo
echo "=============================================\n";
echo "LOGS DE SEGURIDAD - OPTICAP\n";
echo "Exportado: " . date('d/m/Y H:i:s') . "\n";
echo "Total de registros: " . count($logs) . "\n";
echo "=============================================\n\n";

// Llenar con datos
foreach ($logs as $index => $log) {
    echo "REGISTRO #" . ($index + 1) . "\n";
    echo "-----------\n";
    echo "Fecha: " . $log['fecha'] . "\n";
    echo "Usuario: " . ($log['usuario_nombre'] ?: 'Sistema') . "\n";
    echo "Acción: " . $log['accion'] . "\n";
    echo "IP: " . $log['ip'] . "\n";
    echo "User Agent: " . ($log['user_agent'] ?: 'N/A') . "\n";
    echo "Resultado: " . ucfirst($log['resultado']) . "\n";
    echo "Detalles: " . ($log['detalles'] ?: 'N/A') . "\n";
    echo "---------------------------------------------\n\n";
}

// Pie del archivo
echo "=============================================\n";
echo "FIN DEL REPORTE\n";
echo "=============================================\n";

exit;
?>