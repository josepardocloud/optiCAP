<?php
class Actividad {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function obtenerPorId($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, p.nombre as proceso_nombre, tp.nombre as tipo_proceso_nombre
            FROM actividades a
            JOIN procesos p ON a.proceso_id = p.id
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function listarPorProceso($procesoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM actividades 
            WHERE proceso_id = ? AND estado = 'activo' 
            ORDER BY orden
        ");
        $stmt->execute([$procesoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function actualizar($id, $data) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE actividades 
            SET nombre = ?, descripcion = ?, requisitos_obligatorios = ?, 
                duracion_estimada = ?, orden = ?, estado = ?, actualizado_en = NOW() 
            WHERE id = ?
        ");
        
        $requisitos = isset($data['requisitos_obligatorios']) ? 
            json_encode($data['requisitos_obligatorios']) : '[]';
        
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'],
            $requisitos,
            $data['duracion_estimada'],
            $data['orden'],
            $data['estado'],
            $id
        ]);
        
        return true;
    }
    
    public function obtenerRequisitosObligatorios($actividadId) {
        $actividad = $this->obtenerPorId($actividadId);
        return json_decode($actividad['requisitos_obligatorios'] ?? '[]', true);
    }
    
    public function validarRequisitos($actividadId, $requisitosCumplidos) {
        $requisitosObligatorios = $this->obtenerRequisitosObligatorios($actividadId);
        
        foreach ($requisitosObligatorios as $requisito) {
            if (!isset($requisitosCumplidos[$requisito]) || !$requisitosCumplidos[$requisito]) {
                return false;
            }
        }
        
        return true;
    }
}
?>