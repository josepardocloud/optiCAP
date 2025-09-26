<?php
class Configuracion {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function obtener() {
        $stmt = $this->db->query("SELECT * FROM configuracion_sistema WHERE id = 1");
        return $stmt->fetch();
    }
    
    public function actualizar($datos) {
        $stmt = $this->db->prepare("UPDATE configuracion_sistema SET nombre_sistema = ?, tiempo_maximo_proceso = ?, email_notificaciones = ? WHERE id = 1");
        return $stmt->execute([$datos['nombre_sistema'], $datos['tiempo_maximo_proceso'], $datos['email_notificaciones']]);
    }
    
    public function actualizarLogo($logoPath) {
        $stmt = $this->db->prepare("UPDATE configuracion_sistema SET logo = ? WHERE id = 1");
        return $stmt->execute([$logoPath]);
    }
    
    public function obtenerTiempoMaximoProceso() {
        $config = $this->obtener();
        return $config['tiempo_maximo_proceso'] ?? 30;
    }
}
?>