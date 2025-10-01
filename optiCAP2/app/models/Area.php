<?php
class Area {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function listar($filtros = []) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE estado = 'activo'";
        $params = [];
        
        if (!empty($filtros['nombre'])) {
            $where .= " AND nombre LIKE ?";
            $params[] = '%' . $filtros['nombre'] . '%';
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM areas 
            $where 
            ORDER BY nombre
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPorId($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM areas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function crear($data) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO areas (nombre, codigo, descripcion, estado, creado_en) 
            VALUES (?, ?, ?, 'activo', NOW())
        ");
        
        $stmt->execute([
            $data['nombre'],
            $data['codigo'],
            $data['descripcion']
        ]);
        
        return $pdo->lastInsertId();
    }
    
    public function actualizar($id, $data) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE areas 
            SET nombre = ?, codigo = ?, descripcion = ?, estado = ?, actualizado_en = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['nombre'],
            $data['codigo'],
            $data['descripcion'],
            $data['estado'],
            $id
        ]);
        
        return true;
    }
    
    public function obtenerUsuarios($areaId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT id, nombre, email, estado 
            FROM usuarios 
            WHERE area_id = ? AND estado = 'activo'
            ORDER BY nombre
        ");
        $stmt->execute([$areaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>