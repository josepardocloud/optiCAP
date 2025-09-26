<?php
class Seguimiento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("
            SELECT sr.*, r.codigo, r.titulo, a.nombre as actividad_nombre, a.descripcion,
                   u.nombre as usuario_asignado, u.email as usuario_email
            FROM seguimiento_requerimientos sr
            LEFT JOIN requerimientos r ON sr.id_requerimiento = r.id
            LEFT JOIN actividades a ON sr.id_actividad = a.id
            LEFT JOIN usuarios u ON sr.id_usuario_asignado = u.id
            WHERE sr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function actualizarSeguimiento($id, $datos) {
        $campos = [];
        $valores = [];
        
        foreach ($datos as $campo => $valor) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $id;
        $sql = "UPDATE seguimiento_requerimientos SET " . implode(', ', $campos) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valores);
    }
    
    public function asignarUsuarioSeguimiento($seguimientoId, $usuarioId) {
        $stmt = $this->db->prepare("UPDATE seguimiento_requerimientos SET id_usuario_asignado = ? WHERE id = ?");
        return $stmt->execute([$usuarioId, $seguimientoId]);
    }
    
    public function obtenerActividadesAtrasadas() {
        $stmt = $this->db->prepare("
            SELECT sr.*, r.codigo, r.titulo, a.nombre as actividad_nombre, 
                   u.nombre as usuario_asignado, DATEDIFF(CURDATE(), sr.fecha_fin_estimada) as dias_retraso
            FROM seguimiento_requerimientos sr
            LEFT JOIN requerimientos r ON sr.id_requerimiento = r.id
            LEFT JOIN actividades a ON sr.id_actividad = a.id
            LEFT JOIN usuarios u ON sr.id_usuario_asignado = u.id
            WHERE sr.estado IN ('pendiente', 'en_proceso') 
            AND sr.fecha_fin_estimada < CURDATE()
            ORDER BY sr.fecha_fin_estimada ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function calcularProgresoRequerimiento($requerimientoId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_actividades,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as actividades_completadas
            FROM seguimiento_requerimientos 
            WHERE id_requerimiento = ?
        ");
        $stmt->execute([$requerimientoId]);
        return $stmt->fetch();
    }
    
    public function obtenerHistorialActividad($seguimientoId) {
        $stmt = $this->db->prepare("
            SELECT l.*, u.nombre as usuario_nombre
            FROM logs_sistema l
            LEFT JOIN usuarios u ON l.id_usuario = u.id
            WHERE l.tabla_afectada = 'seguimiento_requerimientos' 
            AND l.id_registro_afectado = ?
            ORDER BY l.fecha DESC
        ");
        $stmt->execute([$seguimientoId]);
        return $stmt->fetchAll();
    }
}
?>