<?php
class Requerimiento {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function crear($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Generar código único
            $codigo = $this->generarCodigo($data['tipo_proceso_id']);
            
            // Crear requerimiento
            $stmt = $pdo->prepare("
                INSERT INTO requerimientos (codigo, tipo_proceso_id, area_id, usuario_solicitante_id, motivo, fecha_creacion) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $codigo,
                $data['tipo_proceso_id'],
                $data['area_id'],
                $data['usuario_solicitante_id'],
                $data['motivo']
            ]);
            
            $requerimientoId = $pdo->lastInsertId();
            
            // Asignar las 14 actividades del proceso
            $this->asignarActividades($requerimientoId, $data['tipo_proceso_id']);
            
            // Registrar en auditoría
            $this->registrarAuditoria($requerimientoId, 1, 'creacion', $data);
            
            $pdo->commit();
            
            // Enviar notificación
            $this->enviarNotificacionCreacion($requerimientoId);
            
            return $requerimientoId;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public function obtenerPorId($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*, tp.nombre as tipo_proceso_nombre, tp.codigo as tipo_proceso_codigo,
                   a.nombre as area_nombre, u.nombre as usuario_solicitante_nombre
            FROM requerimientos r
            JOIN tipos_proceso tp ON r.tipo_proceso_id = tp.id
            JOIN areas a ON r.area_id = a.id
            JOIN usuarios u ON r.usuario_solicitante_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function listar($filtros = []) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['tipo_proceso_id'])) {
            $where .= " AND r.tipo_proceso_id = ?";
            $params[] = $filtros['tipo_proceso_id'];
        }
        
        if (!empty($filtros['area_id'])) {
            $where .= " AND r.area_id = ?";
            $params[] = $filtros['area_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $where .= " AND r.estado_general = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['codigo'])) {
            $where .= " AND r.codigo LIKE ?";
            $params[] = '%' . $filtros['codigo'] . '%';
        }
        
        $stmt = $pdo->prepare("
            SELECT r.*, tp.nombre as tipo_proceso_nombre, a.nombre as area_nombre,
                   u.nombre as usuario_solicitante_nombre,
                   (SELECT COUNT(*) FROM requerimiento_actividades ra 
                    WHERE ra.requerimiento_id = r.id AND ra.estado = 'finalizado') as actividades_completadas
            FROM requerimientos r
            JOIN tipos_proceso tp ON r.tipo_proceso_id = tp.id
            JOIN areas a ON r.area_id = a.id
            JOIN usuarios u ON r.usuario_solicitante_id = u.id
            $where
            ORDER BY r.fecha_creacion DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerActividades($requerimientoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT ra.*, a.numero_paso, a.nombre, a.descripcion, a.requisitos_obligatorios,
                   u.nombre as usuario_asignado_nombre
            FROM requerimiento_actividades ra
            JOIN actividades a ON ra.actividad_id = a.id
            LEFT JOIN usuarios u ON ra.usuario_asignado_id = u.id
            WHERE ra.requerimiento_id = ?
            ORDER BY a.orden
        ");
        $stmt->execute([$requerimientoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerEvidencias($requerimientoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT e.*, u.nombre as usuario_nombre, ra.id as actividad_id,
                   a.numero_paso, a.nombre as actividad_nombre
            FROM evidencias e
            JOIN requerimiento_actividades ra ON e.requerimiento_actividad_id = ra.id
            JOIN actividades a ON ra.actividad_id = a.id
            JOIN usuarios u ON e.usuario_id = u.id
            WHERE ra.requerimiento_id = ?
            ORDER BY a.orden, e.creado_en
        ");
        $stmt->execute([$requerimientoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerHistorial($requerimientoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT ar.*, u.nombre as usuario_nombre, a.numero_paso, a.nombre as actividad_nombre
            FROM auditoria_requerimientos ar
            JOIN usuarios u ON ar.usuario_id = u.id
            JOIN actividades a ON ar.actividad_id = a.id
            WHERE ar.requerimiento_id = ?
            ORDER BY ar.fecha DESC
        ");
        $stmt->execute([$requerimientoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerCodigo($requerimientoId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("SELECT codigo FROM requerimientos WHERE id = ?");
        $stmt->execute([$requerimientoId]);
        return $stmt->fetchColumn();
    }
    
    private function generarCodigo($tipoProcesoId) {
        $pdo = $this->db->getConnection();
        
        // Obtener prefijo del tipo de proceso
        $stmt = $pdo->prepare("SELECT codigo FROM tipos_proceso WHERE id = ?");
        $stmt->execute([$tipoProcesoId]);
        $prefijo = $stmt->fetchColumn();
        
        $anio = date('Y');
        
        // Obtener último número secuencial del año
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM requerimientos 
            WHERE codigo LIKE ? AND YEAR(fecha_creacion) = ?
        ");
        $stmt->execute([$prefijo . '-' . $anio . '-%', $anio]);
        $secuencial = $stmt->fetchColumn() + 1;
        
        return sprintf("%s-%s-%04d", $prefijo, $anio, $secuencial);
    }
    
    private function asignarActividades($requerimientoId, $tipoProcesoId) {
        $pdo = $this->db->getConnection();
        
        // Obtener proceso asociado al tipo
        $stmt = $pdo->prepare("
            SELECT id FROM procesos WHERE tipo_proceso_id = ? AND estado = 'activo'
        ");
        $stmt->execute([$tipoProcesoId]);
        $procesoId = $stmt->fetchColumn();
        
        if (!$procesoId) {
            throw new Exception("No se encontró proceso activo para el tipo seleccionado");
        }
        
        // Obtener las 14 actividades del proceso
        $stmt = $pdo->prepare("
            SELECT id FROM actividades 
            WHERE proceso_id = ? AND estado = 'activo' 
            ORDER BY orden
        ");
        $stmt->execute([$procesoId]);
        $actividades = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($actividades) !== 14) {
            throw new Exception("El proceso debe tener exactamente 14 actividades");
        }
        
        // Insertar actividades del requerimiento
        $insertStmt = $pdo->prepare("
            INSERT INTO requerimiento_actividades (requerimiento_id, actividad_id, estado) 
            VALUES (?, ?, 'pendiente')
        ");
        
        foreach ($actividades as $actividadId) {
            $insertStmt->execute([$requerimientoId, $actividadId]);
        }
        
        // Habilitar primera actividad
        $this->habilitarActividad($requerimientoId, 1);
    }
    
    private function habilitarActividad($requerimientoId, $numeroPaso) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE requerimiento_actividades ra
            JOIN actividades a ON ra.actividad_id = a.id
            SET ra.estado = 'en_proceso', ra.fecha_inicio = NOW()
            WHERE ra.requerimiento_id = ? AND a.numero_paso = ?
        ");
        $stmt->execute([$requerimientoId, $numeroPaso]);
    }
    
    private function registrarAuditoria($requerimientoId, $actividadId, $accion, $datos = []) {
        $pdo = $this->db->getConnection();
        $auth = new Auth();
        $usuarioId = $auth->getUserId();
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_requerimientos (requerimiento_id, actividad_id, accion, usuario_id, fecha) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$requerimientoId, $actividadId, $accion, $usuarioId]);
    }
    
    private function enviarNotificacionCreacion($requerimientoId) {
        $requerimiento = $this->obtenerPorId($requerimientoId);
        
        $asunto = "Nuevo Requerimiento Creado - " . $requerimiento['codigo'];
        $mensaje = "
            Se ha creado un nuevo requerimiento en el sistema:
            
            Código: {$requerimiento['codigo']}
            Tipo: {$requerimiento['tipo_proceso_nombre']}
            Área: {$requerimiento['area_nombre']}
            Solicitante: {$requerimiento['usuario_solicitante_nombre']}
            Motivo: {$requerimiento['motivo']}
            
            Puede acceder al requerimiento desde el sistema.
        ";
        
        // Notificar a administradores y supervisores
        $notificacion = new Notificacion();
        $notificacion->notificarRoles(['Administrador', 'Supervisor'], $asunto, $mensaje, 
                                    SITE_URL . '/requerimientos/detalle/' . $requerimientoId);
    }
    
    public function actualizarActividad($requerimientoId, $numeroPaso, $data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Obtener actividad actual
            $stmt = $pdo->prepare("
                SELECT ra.*, a.requisitos_obligatorios 
                FROM requerimiento_actividades ra
                JOIN actividades a ON ra.actividad_id = a.id
                WHERE ra.requerimiento_id = ? AND a.numero_paso = ?
            ");
            $stmt->execute([$requerimientoId, $numeroPaso]);
            $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$actividad) {
                throw new Exception("Actividad no encontrada");
            }
            
            // Validar requisitos si se va a finalizar
            if (isset($data['estado']) && $data['estado'] === 'finalizado') {
                $this->validarRequisitos($actividad, $data);
            }
            
            // Actualizar actividad
            $updateStmt = $pdo->prepare("
                UPDATE requerimiento_actividades 
                SET estado = ?, observaciones = ?, requisitos_cumplidos = ?, 
                    fecha_fin = ?, actualizado_en = NOW()
                WHERE id = ?
            ");
            
            $fechaFin = ($data['estado'] === 'finalizado') ? date('Y-m-d H:i:s') : null;
            $requisitosCumplidos = isset($data['requisitos_cumplidos']) ? 
                json_encode($data['requisitos_cumplidos']) : null;
            
            $updateStmt->execute([
                $data['estado'],
                $data['observaciones'] ?? null,
                $requisitosCumplidos,
                $fechaFin,
                $actividad['id']
            ]);
            
            // Registrar en auditoría
            $this->registrarAuditoria($requerimientoId, $actividad['actividad_id'], 
                                    'actualizacion_actividad', $data);
            
            // Si se finalizó, habilitar siguiente actividad o aplicar lógica condicional
            if ($data['estado'] === 'finalizado') {
                $this->procesarSiguienteActividad($requerimientoId, $numeroPaso, $data);
            }
            
            $pdo->commit();
            
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    private function validarRequisitos($actividad, $data) {
        $requisitos = json_decode($actividad['requisitos_obligatorios'], true);
        
        if (!empty($requisitos)) {
            foreach ($requisitos as $requisito) {
                if (!isset($data['requisitos_cumplidos'][$requisito]) || 
                    !$data['requisitos_cumplidos'][$requisito]) {
                    throw new Exception("Requisito obligatorio no cumplido: $requisito");
                }
            }
        }
    }
    
    private function procesarSiguienteActividad($requerimientoId, $numeroPasoActual, $data) {
        // Lógica condicional para Paso 01
        if ($numeroPasoActual === 1) {
            $this->aplicarLogicaCondicionalPaso01($requerimientoId, $data);
            return;
        }
        
        // Secuencia normal
        $siguientePaso = $numeroPasoActual + 1;
        if ($siguientePaso <= 14) {
            $this->habilitarActividad($requerimientoId, $siguientePaso);
        } else {
            // Completar requerimiento
            $this->completarRequerimiento($requerimientoId);
        }
    }
    
    private function aplicarLogicaCondicionalPaso01($requerimientoId, $data) {
        $pdo = $this->db->getConnection();
        
        // Verificar respuesta del Paso 01
        $cumple = $data['requisitos_cumplidos']['existe_cuadro_multianual'] ?? false;
        
        if ($cumple) {
            // Salto condicional: habilitar Paso 05, marcar 02-04 como No Aplica
            $stmt = $pdo->prepare("
                UPDATE requerimiento_actividades ra
                JOIN actividades a ON ra.actividad_id = a.id
                SET ra.estado = 'no_aplica', ra.salto_condicional = 1
                WHERE ra.requerimiento_id = ? AND a.numero_paso IN (2, 3, 4)
            ");
            $stmt->execute([$requerimientoId]);
            
            // Habilitar Paso 05
            $this->habilitarActividad($requerimientoId, 5);
            
            // Registrar salto condicional
            $this->registrarSaltoCondicional($requerimientoId);
            
        } else {
            // Secuencia normal: habilitar Paso 02
            $this->habilitarActividad($requerimientoId, 2);
        }
    }
    
    private function registrarSaltoCondicional($requerimientoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE requerimientos 
            SET fecha_salto_condicional = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$requerimientoId]);
        
        // Registrar en auditoría
        $this->registrarAuditoria($requerimientoId, 1, 'salto_condicional_aplicado');
    }
    
    private function completarRequerimiento($requerimientoId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE requerimientos 
            SET estado_general = 'completado', fecha_completado = NOW(),
                progreso = 100
            WHERE id = ?
        ");
        $stmt->execute([$requerimientoId]);
        
        // Registrar en auditoría
        $this->registrarAuditoria($requerimientoId, 0, 'requerimiento_completado');
        
        // Enviar notificación
        $this->enviarNotificacionCompletado($requerimientoId);
    }
    
    private function enviarNotificacionCompletado($requerimientoId) {
        $requerimiento = $this->obtenerPorId($requerimientoId);
        
        $asunto = "Requerimiento Completado - " . $requerimiento['codigo'];
        $mensaje = "
            El requerimiento ha sido completado exitosamente:
            
            Código: {$requerimiento['codigo']}
            Tipo: {$requerimiento['tipo_proceso_nombre']}
            Área: {$requerimiento['area_nombre']}
            Fecha de completado: " . date('d/m/Y H:i') . "
            
            Puede revisar el detalle completo desde el sistema.
        ";
        
        $notificacion = new Notificacion();
        $notificacion->notificarUsuario($requerimiento['usuario_solicitante_id'], $asunto, $mensaje,
                                      SITE_URL . '/requerimientos/detalle/' . $requerimientoId);
    }
}
?>