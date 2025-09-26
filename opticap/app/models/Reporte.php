<?php
class Reporte {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function obtenerRequerimientosPorFecha($fechaInicio, $fechaFin, $areaId = null) {
        $sql = "
            SELECT r.*, a.nombre as area_nombre, u.nombre as usuario_solicitante 
            FROM requerimientos r 
            LEFT JOIN areas a ON r.id_area_solicitante = a.id 
            LEFT JOIN usuarios u ON r.id_usuario_solicitante = u.id 
            WHERE r.fecha_creacion BETWEEN ? AND ? 
        ";
        
        $params = [$fechaInicio, $fechaFin];
        
        if ($areaId) {
            $sql .= " AND r.id_area_solicitante = ?";
            $params[] = $areaId;
        }
        
        $sql .= " ORDER BY r.fecha_creacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function obtenerSLAGeneral($areaId = null) {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN r.estado = 'completado' AND r.fecha_limite_total >= CURDATE() THEN 1 ELSE 0 END) as dentro_sla,
                SUM(CASE WHEN r.estado = 'completado' AND r.fecha_limite_total < CURDATE() THEN 1 ELSE 0 END) as fuera_sla
            FROM requerimientos r 
            WHERE r.estado = 'completado'
        ";
        
        $params = [];
        
        if ($areaId) {
            $sql .= " AND r.id_area_solicitante = ?";
            $params[] = $areaId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        $porcentaje = $result['total'] > 0 ? round(($result['dentro_sla'] / $result['total']) * 100, 2) : 0;
        
        return [
            'total' => $result['total'],
            'dentroSLA' => $result['dentro_sla'],
            'fueraSLA' => $result['fuera_sla'],
            'porcentaje' => $porcentaje
        ];
    }
    
    public function obtenerEstadisticasPorArea($fechaInicio, $fechaFin) {
        $sql = "
            SELECT 
                a.nombre as area,
                COUNT(r.id) as total_requerimientos,
                SUM(CASE WHEN r.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN r.estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN r.estado = 'completado' THEN 1 ELSE 0 END) as completados
            FROM areas a 
            LEFT JOIN requerimientos r ON a.id = r.id_area_solicitante 
                AND r.fecha_creacion BETWEEN ? AND ?
            WHERE a.activo = 1
            GROUP BY a.id, a.nombre
            ORDER BY total_requerimientos DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaInicio, $fechaFin]);
        return $stmt->fetchAll();
    }
    
    public function obtenerTiemposPromedio() {
        $sql = "
            SELECT 
                a.nombre as actividad,
                AVG(DATEDIFF(sr.fecha_fin_real, sr.fecha_inicio_real)) as tiempo_promedio,
                a.tiempo_limite as tiempo_limite
            FROM seguimiento_requerimientos sr 
            LEFT JOIN actividades a ON sr.id_actividad = a.id 
            WHERE sr.estado = 'completado' 
                AND sr.fecha_inicio_real IS NOT NULL 
                AND sr.fecha_fin_real IS NOT NULL
            GROUP BY a.id, a.nombre, a.tiempo_limite
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function obtenerDesempenoUsuarios($fechaInicio, $fechaFin) {
        $sql = "
            SELECT 
                u.nombre as usuario,
                a.nombre as area,
                COUNT(sr.id) as actividades_asignadas,
                SUM(CASE WHEN sr.estado = 'completado' THEN 1 ELSE 0 END) as actividades_completadas,
                AVG(CASE WHEN sr.estado = 'completado' THEN DATEDIFF(sr.fecha_fin_real, sr.fecha_inicio_real) ELSE NULL END) as tiempo_promedio
            FROM usuarios u 
            LEFT JOIN areas a ON u.id_area = a.id 
            LEFT JOIN seguimiento_requerimientos sr ON u.id = sr.id_usuario_asignado 
                AND sr.fecha_creacion BETWEEN ? AND ?
            WHERE u.activo = 1
            GROUP BY u.id, u.nombre, a.nombre
            HAVING actividades_asignadas > 0
            ORDER BY actividades_completadas DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fechaInicio, $fechaFin]);
        return $stmt->fetchAll();
    }
}
?>