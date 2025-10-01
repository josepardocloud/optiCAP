<?php
class Permiso {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function obtenerPermisosUsuario($usuarioId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT pg.*, p.nombre as proceso_nombre, a.nombre as actividad_nombre, a.numero_paso
            FROM permisos_granulares pg
            JOIN procesos p ON pg.proceso_id = p.id
            JOIN actividades a ON pg.actividad_id = a.id
            WHERE pg.usuario_id = ? AND pg.estado = 'activo'
            ORDER BY p.nombre, a.orden
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function tienePermiso($usuarioId, $procesoId, $actividadId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM permisos_granulares 
            WHERE usuario_id = ? AND proceso_id = ? AND actividad_id = ? AND estado = 'activo'
        ");
        $stmt->execute([$usuarioId, $procesoId, $actividadId]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function asignarPermiso($usuarioId, $procesoId, $actividadId, $fechaExpiracion = null) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO permisos_granulares (usuario_id, proceso_id, actividad_id, fecha_asignacion, fecha_expiracion, estado) 
            VALUES (?, ?, ?, NOW(), ?, 'activo')
            ON DUPLICATE KEY UPDATE estado = 'activo', fecha_expiracion = ?
        ");
        
        $stmt->execute([$usuarioId, $procesoId, $actividadId, $fechaExpiracion, $fechaExpiracion]);
        
        return true;
    }
    
    public function revocarPermiso($permisoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE permisos_granulares 
            SET estado = 'revocado', revocado_en = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([$permisoId]);
        
        return $stmt->rowCount() > 0;
    }
    
    public function revocarTodosPermisosUsuario($usuarioId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE permisos_granulares 
            SET estado = 'revocado', revocado_en = NOW() 
            WHERE usuario_id = ? AND estado = 'activo'
        ");
        
        $stmt->execute([$usuarioId]);
        
        return $stmt->rowCount();
    }
    
    public function verificarExpiraciones() {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE permisos_granulares 
            SET estado = 'expirado' 
            WHERE estado = 'activo' AND fecha_expiracion IS NOT NULL AND fecha_expiracion < NOW()
        ");
        
        return $stmt->execute();
    }
    
    public function obtenerSolicitudesPendientes() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT sp.*, u.nombre as usuario_nombre, u.email as usuario_email,
                   p.nombre as proceso_nombre, a.nombre as actividad_nombre, a.numero_paso
            FROM solicitudes_permisos sp
            JOIN usuarios u ON sp.usuario_id = u.id
            JOIN procesos p ON sp.proceso_id = p.id
            JOIN actividades a ON sp.actividad_id = a.id
            WHERE sp.estado = 'pendiente'
            ORDER BY sp.fecha_solicitud ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>