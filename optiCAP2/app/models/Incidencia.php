<?php
class Incidencia {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function crear($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Generar código único
            $codigo = 'INC-' . date('Y') . '-' . str_pad($this->obtenerProximoSecuencial(), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO incidencias (codigo, requerimiento_id, usuario_reportero_id, 
                                       titulo, descripcion, tipo, prioridad, fecha_reporte) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $codigo,
                $data['requerimiento_id'],
                $data['usuario_reportero_id'],
                $data['titulo'],
                $data['descripcion'],
                $data['tipo'],
                $data['prioridad']
            ]);
            
            $incidenciaId = $pdo->lastInsertId();
            
            $pdo->commit();
            return $incidenciaId;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public function obtenerPorId($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT i.*, u.nombre as usuario_reportero_nombre,
                   ur.nombre as usuario_resolutor_nombre,
                   r.codigo as requerimiento_codigo, r.motivo as requerimiento_motivo
            FROM incidencias i
            JOIN usuarios u ON i.usuario_reportero_id = u.id
            LEFT JOIN usuarios ur ON i.usuario_resolutor_id = ur.id
            LEFT JOIN requerimientos r ON i.requerimiento_id = r.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function listar($filtros = []) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['usuario_reportero_id'])) {
            $where .= " AND i.usuario_reportero_id = ?";
            $params[] = $filtros['usuario_reportero_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $where .= " AND i.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['prioridad'])) {
            $where .= " AND i.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }
        
        $stmt = $pdo->prepare("
            SELECT i.*, u.nombre as usuario_reportero_nombre,
                   ur.nombre as usuario_resolutor_nombre,
                   r.codigo as requerimiento_codigo
            FROM incidencias i
            JOIN usuarios u ON i.usuario_reportero_id = u.id
            LEFT JOIN usuarios ur ON i.usuario_resolutor_id = ur.id
            LEFT JOIN requerimientos r ON i.requerimiento_id = r.id
            $where
            ORDER BY i.fecha_reporte DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function resolver($id, $usuarioResolutorId, $solucion) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE incidencias 
            SET estado = 'resuelto', usuario_resolutor_id = ?, solucion = ?, fecha_resolucion = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([$usuarioResolutorId, $solucion, $id]);
        
        return $stmt->rowCount() > 0;
    }
    
    public function obtenerEstadisticas() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'resuelto' THEN 1 ELSE 0 END) as resueltas,
                AVG(TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)) as tiempo_resolucion_promedio
            FROM incidencias
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function obtenerProximoSecuencial() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM incidencias WHERE YEAR(fecha_reporte) = YEAR(NOW())");
        return $stmt->fetchColumn() + 1;
    }
}
?>