<?php
class Actividad {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function obtenerTodas() {
        $stmt = $this->db->query("SELECT * FROM actividades ORDER BY orden");
        return $stmt->fetchAll();
    }
    
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM actividades WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function obtenerActivas() {
        $stmt = $this->db->query("SELECT * FROM actividades WHERE activo = 1 ORDER BY orden");
        return $stmt->fetchAll();
    }
    
    public function obtenerMaximoOrden() {
        $stmt = $this->db->query("SELECT MAX(orden) as max_orden FROM actividades");
        $result = $stmt->fetch();
        return $result['max_orden'] ?: 0;
    }
    
    public function crear($datos) {
        $stmt = $this->db->prepare("INSERT INTO actividades (nombre, descripcion, tiempo_limite, orden) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$datos['nombre'], $datos['descripcion'], $datos['tiempo_limite'], $datos['orden']]);
    }
    
    public function actualizar($id, $datos) {
        $stmt = $this->db->prepare("UPDATE actividades SET nombre = ?, descripcion = ?, tiempo_limite = ?, orden = ?, activo = ? WHERE id = ?");
        return $stmt->execute([$datos['nombre'], $datos['descripcion'], $datos['tiempo_limite'], $datos['orden'], $datos['activo'], $id]);
    }
    
    public function actualizarOrden($id, $orden) {
        $stmt = $this->db->prepare("UPDATE actividades SET orden = ? WHERE id = ?");
        return $stmt->execute([$orden, $id]);
    }
    
    public function eliminar($id) {
        // Verificar si hay seguimientos asociados
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM seguimiento_requerimientos WHERE id_actividad = ?");
        $stmt->execute([$id]);
        $seguimientos = $stmt->fetch()['total'];
        
        if ($seguimientos > 0) {
            return false; // No se puede eliminar si hay seguimientos asociados
        }
        
        $stmt = $this->db->prepare("DELETE FROM actividades WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>