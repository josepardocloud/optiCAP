<?php
class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function obtenerPorEmail($email) {
        $stmt = $this->db->prepare("SELECT u.*, a.nombre as area_nombre FROM usuarios u LEFT JOIN areas a ON u.id_area = a.id WHERE u.email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT u.*, a.nombre as area_nombre FROM usuarios u LEFT JOIN areas a ON u.id_area = a.id WHERE u.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function obtenerTodos() {
        $stmt = $this->db->prepare("SELECT u.*, a.nombre as area_nombre FROM usuarios u LEFT JOIN areas a ON u.id_area = a.id ORDER BY u.nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function crear($datos) {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, email, password, id_area, rol) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $datos['nombre'],
            $datos['email'],
            $datos['password'],
            $datos['id_area'],
            $datos['rol']
        ]);
    }
    
    public function actualizar($id, $datos) {
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, email = ?, id_area = ?, rol = ?, activo = ? WHERE id = ?");
        return $stmt->execute([
            $datos['nombre'],
            $datos['email'],
            $datos['id_area'],
            $datos['rol'],
            $datos['activo'],
            $id
        ]);
    }
    
    public function actualizarPassword($id, $password) {
        $stmt = $this->db->prepare("UPDATE usuarios SET password = ?, primer_login = 0 WHERE id = ?");
        return $stmt->execute([$password, $id]);
    }
    
    public function marcarPrimerLoginCompletado($id) {
        $stmt = $this->db->prepare("UPDATE usuarios SET primer_login = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function obtenerPorArea($areaId) {
        $stmt = $this->db->prepare("SELECT u.*, a.nombre as area_nombre FROM usuarios u LEFT JOIN areas a ON u.id_area = a.id WHERE u.id_area = ? ORDER BY u.nombre");
        $stmt->execute([$areaId]);
        return $stmt->fetchAll();
    }
    
    public function registrarLog($usuarioId, $accion, $descripcion) {
        $stmt = $this->db->prepare("INSERT INTO logs_sistema (id_usuario, accion, descripcion, ip) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $usuarioId,
            $accion,
            $descripcion,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
}
?>