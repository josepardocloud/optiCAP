<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener datos para exportar
$query = "SELECT r.codigo, p.nombre as proceso, p.tipo, a.nombre as area, 
                 u.nombre as solicitante, r.fecha_creacion, r.estado, r.observaciones,
                 (SELECT MAX(fecha_fin) FROM seguimiento_actividades WHERE requerimiento_id = r.id AND estado = 'completado') as fecha_completacion
          FROM requerimientos r 
          INNER JOIN procesos p ON r.proceso_id = p.id 
          INNER JOIN areas a ON r.area_id = a.id 
          INNER JOIN usuarios u ON r.usuario_solicitante_id = u.id 
          ORDER BY r.fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="requerimientos_opticap_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Escribir BOM para UTF-8
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Escribir headers
fputcsv($output, [
    'Código', 'Proceso', 'Tipo', 'Área', 'Solicitante', 
    'Fecha Creación', 'Estado', 'Fecha Completación', 'Observaciones'
], ';');

// Escribir datos
foreach ($datos as $fila) {
    fputcsv($output, [
        $fila['codigo'],
        $fila['proceso'],
        $fila['tipo'],
        $fila['area'],
        $fila['solicitante'],
        date('d/m/Y H:i', strtotime($fila['fecha_creacion'])),
        ucfirst(str_replace('_', ' ', $fila['estado'])),
        $fila['fecha_completacion'] ? date('d/m/Y H:i', strtotime($fila['fecha_completacion'])) : 'N/A',
        $fila['observaciones'] ?: 'N/A'
    ], ';');
}

fclose($output);
exit();
?>