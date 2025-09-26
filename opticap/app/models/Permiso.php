<?php
class Permiso {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function obtenerPorUsuario($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT p.*, a.nombre as actividad_nombre 
            FROM permisos_usuario p 
            LEFT JOIN actividades a ON p.id_actividad = a.id 
            WHERE p.id_usuario = ?
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }
    
    public function obtenerPorUsuarioActividad($usuarioId, $actividadId) {
        $stmt = $this->db->prepare("SELECT * FROM permisos_usuario WHERE id_usuario = ? AND id_actividad = ?");
        $stmt->execute([$usuarioId, $actividadId]);
        return $stmt->fetch();
    }
    
    public function crear($datos) {
        $stmt = $this->db->prepare("INSERT INTO permisos_usuario (id_usuario, id_actividad, puede_aprobar, puede_modificar) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$datos['id_usuario'], $datos['id_actividad'], $datos['puede_aprobar'], $datos['puede_modificar']]);
    }
    
    public function actualizar($id, $datos) {
        $stmt = $this->db->prepare("UPDATE permisos_usuario SET puede_aprobar = ?, puede_modificar = ? WHERE id = ?");
        return $stmt->execute([$datos['puede_aprobar'], $datos['puede_modificar'], $id]);
    }
    
    public function eliminarPorUsuario($usuarioId) {
        $stmt = $this->db->prepare("DELETE FROM permisos_usuario WHERE id_usuario = ?");
        return $stmt->execute([$usuarioId]);
    }
    
    public function usuarioPuedeAprobar($usuarioId, $actividadId) {
        $permiso = $this->obtenerPorUsuarioActividad($usuarioId, $actividadId);
        return $permiso && $permiso['puede_aprobar'] == 1;
    }
    
    public function usuarioPuedeModificar($usuarioId, $actividadId) {
        $permiso = $this->obtenerPorUsuarioActividad($usuarioId, $actividadId);
        return $permiso && $permiso['puede_modificar'] == 1;
    }
}
?>