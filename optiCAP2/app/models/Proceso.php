<?php
class Proceso {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function listar($filtros = []) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE p.estado = 'activo'";
        $params = [];
        
        if (!empty($filtros['tipo_proceso_id'])) {
            $where .= " AND p.tipo_proceso_id = ?";
            $params[] = $filtros['tipo_proceso_id'];
        }
        
        $stmt = $pdo->prepare("
            SELECT p.*, tp.nombre as tipo_proceso_nombre, tp.codigo as tipo_proceso_codigo,
                   (SELECT COUNT(*) FROM actividades a WHERE a.proceso_id = p.id AND a.estado = 'activo') as total_actividades
            FROM procesos p
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            $where
            ORDER BY tp.nombre, p.nombre
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPorId($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, tp.nombre as tipo_proceso_nombre, tp.codigo as tipo_proceso_codigo
            FROM procesos p
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPorTipo($tipoProcesoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM procesos 
            WHERE tipo_proceso_id = ? AND estado = 'activo'
            ORDER BY nombre
        ");
        $stmt->execute([$tipoProcesoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function crear($data) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO procesos (tipo_proceso_id, nombre, descripcion, duracion_estimada, estado, creado_en) 
            VALUES (?, ?, ?, ?, 'activo', NOW())
        ");
        
        $stmt->execute([
            $data['tipo_proceso_id'],
            $data['nombre'],
            $data['descripcion'],
            $data['duracion_estimada']
        ]);
        
        return $pdo->lastInsertId();
    }
    
    public function actualizar($id, $data) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE procesos 
            SET nombre = ?, descripcion = ?, duracion_estimada = ?, estado = ?, actualizado_en = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'],
            $data['duracion_estimada'],
            $data['estado'],
            $id
        ]);
        
        return true;
    }
    
    public function obtenerActividades($procesoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM actividades 
            WHERE proceso_id = ? AND estado = 'activo' 
            ORDER BY orden
        ");
        $stmt->execute([$procesoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerEstadisticas($procesoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_requerimientos,
                AVG(progreso) as progreso_promedio,
                AVG(TIMESTAMPDIFF(DAY, fecha_creacion, fecha_completado)) as duracion_promedio_dias,
                SUM(CASE WHEN fecha_salto_condicional IS NOT NULL THEN 1 ELSE 0 END) as con_salto_condicional
            FROM requerimientos 
            WHERE tipo_proceso_id = (SELECT tipo_proceso_id FROM procesos WHERE id = ?)
        ");
        $stmt->execute([$procesoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>