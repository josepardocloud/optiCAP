<?php
class Area {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function obtenerTodas() {
        $stmt = $this->db->query("SELECT * FROM areas ORDER BY nombre");
        return $stmt->fetchAll();
    }
    
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM areas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function obtenerActivas() {
        $stmt = $this->db->query("SELECT * FROM areas WHERE activo = 1 ORDER BY nombre");
        return $stmt->fetchAll();
    }
    
    public function crear($datos) {
        $stmt = $this->db->prepare("INSERT INTO areas (nombre, descripcion) VALUES (?, ?)");
        return $stmt->execute([$datos['nombre'], $datos['descripcion']]);
    }
    
    public function actualizar($id, $datos) {
        $stmt = $this->db->prepare("UPDATE areas SET nombre = ?, descripcion = ?, activo = ? WHERE id = ?");
        return $stmt->execute([$datos['nombre'], $datos['descripcion'], $datos['activo'], $id]);
    }
    
    public function eliminar($id) {
        // Verificar si hay usuarios asociados
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_area = ?");
        $stmt->execute([$id]);
        $usuarios = $stmt->fetch()['total'];
        
        if ($usuarios > 0) {
            return false; // No se puede eliminar si hay usuarios asociados
        }
        
        $stmt = $this->db->prepare("DELETE FROM areas WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>