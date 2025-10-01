<?php
class Auditoria {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function registrarAcceso($usuarioId, $email, $accion, $ip, $userAgent) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_accesos (usuario_id, email, accion, ip, user_agent, fecha) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$usuarioId, $email, $accion, $ip, $userAgent]);
    }
    
    public function registrarRequerimiento($requerimientoId, $actividadId, $accion, $usuarioId, $observaciones = null) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_requerimientos (requerimiento_id, actividad_id, accion, usuario_id, observaciones, fecha) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$requerimientoId, $actividadId, $accion, $usuarioId, $observaciones]);
    }
    
    public function obtenerAccesos($filtros = []) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['usuario_id'])) {
            $where .= " AND usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $where .= " AND fecha >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $where .= " AND fecha <= ?";
            $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }
        
        $stmt = $pdo->prepare("
            SELECT aa.*, u.nombre as usuario_nombre
            FROM auditoria_accesos aa
            LEFT JOIN usuarios u ON aa.usuario_id = u.id
            $where
            ORDER BY aa.fecha DESC
            LIMIT 1000
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerMovimientosRequerimiento($requerimientoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT ar.*, u.nombre as usuario_nombre, a.numero_paso, a.nombre as actividad_nombre
            FROM auditoria_requerimientos ar
            JOIN usuarios u ON ar.usuario_id = u.id
            LEFT JOIN actividades a ON ar.actividad_id = a.id
            WHERE ar.requerimiento_id = ?
            ORDER BY ar.fecha DESC
        ");
        $stmt->execute([$requerimientoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerEstadisticasAccesos($dias = 30) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                DATE(fecha) as fecha,
                COUNT(*) as total_accesos,
                SUM(CASE WHEN accion = 'login_exitoso' THEN 1 ELSE 0 END) as logins_exitosos,
                SUM(CASE WHEN accion = 'login_fallido' THEN 1 ELSE 0 END) as logins_fallidos
            FROM auditoria_accesos 
            WHERE fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(fecha)
            ORDER BY fecha DESC
        ");
        $stmt->execute([$dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>