<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['supervisor', 'administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener datos para exportar
$query_requerimientos = "SELECT r.codigo, p.nombre as proceso, p.tipo, a.nombre as area, 
                                u.nombre as solicitante, r.fecha_creacion, r.estado,
                                (SELECT MAX(fecha_fin) FROM seguimiento_actividades WHERE requerimiento_id = r.id AND estado = 'completado') as fecha_completacion,
                                TIMESTAMPDIFF(DAY, r.fecha_creacion, 
                                    COALESCE((SELECT MAX(fecha_fin) FROM seguimiento_actividades WHERE requerimiento_id = r.id AND estado = 'completado'), NOW())
                                ) as duracion_dias
                         FROM requerimientos r 
                         INNER JOIN procesos p ON r.proceso_id = p.id 
                         INNER JOIN areas a ON r.area_id = a.id 
                         INNER JOIN usuarios u ON r.usuario_solicitante_id = u.id 
                         ORDER BY r.fecha_creacion DESC";
$stmt_requerimientos = $db->prepare($query_requerimientos);
$stmt_requerimientos->execute();
$requerimientos = $stmt_requerimientos->fetchAll(PDO::FETCH_ASSOC);

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="requerimientos_opticap_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// En una implementación real, aquí se usaría PhpSpreadsheet
// Por ahora, generamos un CSV simple

$output = fopen('php://output', 'w');

// Escribir headers
fputcsv($output, [
    'Código', 'Proceso', 'Tipo', 'Área', 'Solicitante', 
    'Fecha Creación', 'Estado', 'Fecha Completación', 'Duración (días)'
]);

// Escribir datos
foreach ($requerimientos as $req) {
    fputcsv($output, [
        $req['codigo'],
        $req['proceso'],
        $req['tipo'],
        $req['area'],
        $req['solicitante'],
        date('d/m/Y H:i', strtotime($req['fecha_creacion'])),
        ucfirst(str_replace('_', ' ', $req['estado'])),
        $req['fecha_completacion'] ? date('d/m/Y H:i', strtotime($req['fecha_completacion'])) : 'N/A',
        $req['duracion_dias']
    ]);
}

fclose($output);

// Para una implementación real con PhpSpreadsheet, el código sería similar a:
/*
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Requerimientos');

// Agregar headers
$sheet->fromArray($headers, NULL, 'A1');

// Agregar datos
$sheet->fromArray($requerimientos, NULL, 'A2');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
*/
?>