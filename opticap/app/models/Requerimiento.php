<?php
class Requerimiento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generarCodigo() {
        $prefix = 'REQ';
        $year = date('Y');
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM requerimientos WHERE YEAR(fecha_creacion) = $year");
        $count = $stmt->fetch()['total'] + 1;
        return $prefix . $year . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
    
    public function crear($datos) {
        $codigo = $this->generarCodigo();
        
        $this->db->beginTransaction();
        try {
            // Insertar requerimiento
            $stmt = $this->db->prepare("INSERT INTO requerimientos (codigo, titulo, descripcion, id_area_solicitante, id_usuario_solicitante) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $datos['titulo'], $datos['descripcion'], $datos['id_area_solicitante'], $datos['id_usuario_solicitante']]);
            $requerimientoId = $this->db->lastInsertId();
            
            // Obtener actividades en orden
            $actividades = $this->obtenerActividadesOrdenadas();
            $fechaBase = new DateTime();
            
            // Crear seguimiento para cada actividad
            foreach ($actividades as $index => $actividad) {
                $fechaInicio = clone $fechaBase;
                $fechaFin = clone $fechaBase;
                $fechaFin->modify("+{$actividad['tiempo_limite']} days");
                
                $stmt = $this->db->prepare("INSERT INTO seguimiento_requerimientos (id_requerimiento, id_actividad, fecha_inicio_estimada, fecha_fin_estimada) VALUES (?, ?, ?, ?)");
                $stmt->execute([$requerimientoId, $actividad['id'], $fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')]);
                
                $fechaBase = $fechaFin;
            }
            
            $this->db->commit();
            return $requerimientoId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al crear requerimiento: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("
            SELECT r.*, a.nombre as area_nombre, u.nombre as usuario_solicitante 
            FROM requerimientos r 
            LEFT JOIN areas a ON r.id_area_solicitante = a.id 
            LEFT JOIN usuarios u ON r.id_usuario_solicitante = u.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function obtenerSeguimiento($requerimientoId) {
        $stmt = $this->db->prepare("
            SELECT sr.*, a.nombre as actividad_nombre, a.tiempo_limite, u.nombre as usuario_asignado 
            FROM seguimiento_requerimientos sr 
            LEFT JOIN actividades a ON sr.id_actividad = a.id 
            LEFT JOIN usuarios u ON sr.id_usuario_asignado = u.id 
            WHERE sr.id_requerimiento = ? 
            ORDER BY a.orden
        ");
        $stmt->execute([$requerimientoId]);
        return $stmt->fetchAll();
    }
    
    public function obtenerActividadesOrdenadas() {
        $stmt = $this->db->query("SELECT * FROM actividades WHERE activo = 1 ORDER BY orden");
        return $stmt->fetchAll();
    }
    
    public function contarPorUsuarioArea($usuarioId, $areaId, $rol) {
        if ($rol === 'admin' || $rol === 'supervisor') {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM requerimientos");
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM requerimientos WHERE id_area_solicitante = ?");
            $stmt->execute([$areaId]);
        }
        return $stmt->fetch()['total'];
    }
    
    public function contarPorEstadoUsuario($estado, $usuarioId, $areaId, $rol) {
        if ($rol === 'admin' || $rol === 'supervisor') {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM requerimientos WHERE estado = ?");
            $stmt->execute([$estado]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM requerimientos WHERE estado = ? AND id_area_solicitante = ?");
            $stmt->execute([$estado, $areaId]);
        }
        return $stmt->fetch()['total'];
    }
    
    public function obtenerActividadesPendientes($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT sr.*, r.codigo, r.titulo, a.nombre as actividad_nombre 
            FROM seguimiento_requerimientos sr 
            LEFT JOIN requerimientos r ON sr.id_requerimiento = r.id 
            LEFT JOIN actividades a ON sr.id_actividad = a.id 
            WHERE sr.id_usuario_asignado = ? AND sr.estado IN ('pendiente', 'en_proceso')
            ORDER BY sr.fecha_fin_estimada ASC 
            LIMIT 10
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }
}
?>