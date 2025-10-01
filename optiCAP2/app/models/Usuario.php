<?php
class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function crear($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password, rol_id, area_id, estado, creado_en) 
                VALUES (?, ?, ?, ?, ?, 'activo', NOW())
            ");
            
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                $data['nombre'],
                $data['email'],
                $passwordHash,
                $data['rol_id'],
                $data['area_id']
            ]);
            
            $usuarioId = $pdo->lastInsertId();
            
            // Registrar en auditoría
            $this->registrarAuditoria($usuarioId, 'creacion', $data);
            
            $pdo->commit();
            return $usuarioId;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public function obtenerPorId($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre, a.nombre as area_nombre 
            FROM usuarios u 
            LEFT JOIN roles r ON u.rol_id = r.id 
            LEFT JOIN areas a ON u.area_id = a.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function listar($filtros = []) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE u.estado != 'eliminado'";
        $params = [];
        
        if (!empty($filtros['rol_id'])) {
            $where .= " AND u.rol_id = ?";
            $params[] = $filtros['rol_id'];
        }
        
        if (!empty($filtros['area_id'])) {
            $where .= " AND u.area_id = ?";
            $params[] = $filtros['area_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $where .= " AND u.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre, a.nombre as area_nombre 
            FROM usuarios u 
            LEFT JOIN roles r ON u.rol_id = r.id 
            LEFT JOIN areas a ON u.area_id = a.id 
            $where 
            ORDER BY u.nombre
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function actualizar($id, $data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $usuarioAnterior = $this->obtenerPorId($id);
            
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre = ?, email = ?, rol_id = ?, area_id = ?, estado = ?, actualizado_en = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['nombre'],
                $data['email'],
                $data['rol_id'],
                $data['area_id'],
                $data['estado'],
                $id
            ]);
            
            // Registrar cambios en auditoría
            $this->registrarAuditoria($id, 'actualizacion', $data, $usuarioAnterior);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public function eliminar($id) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET estado = 'eliminado', eliminado_en = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            $this->registrarAuditoria($id, 'eliminacion');
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public function reiniciarPassword($id) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Generar password temporal
            $nuevaPassword = bin2hex(random_bytes(8));
            $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET password = ?, intentos_fallidos = 0, token_recuperacion = NULL, 
                    debe_cambiar_password = 1, actualizado_en = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$passwordHash, $id]);
            
            // Obtener email del usuario
            $usuario = $this->obtenerPorId($id);
            
            // Enviar email con nueva contraseña
            $this->enviarNuevaPassword($usuario, $nuevaPassword);
            
            $this->registrarAuditoria($id, 'reinicio_password');
            
            $pdo->commit();
            return $nuevaPassword;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public function desbloquearCuenta($id) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = 0, ultimo_intento = NULL, actualizado_en = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        $this->registrarAuditoria($id, 'desbloqueo_cuenta');
        
        return true;
    }
    
    private function enviarNuevaPassword($usuario, $nuevaPassword) {
        $asunto = "Nueva Contraseña - " . SITE_NAME;
        $mensaje = "
            Hola {$usuario['nombre']},
            
            Tu contraseña ha sido reiniciada por un administrador.
            
            Tu nueva contraseña temporal es: $nuevaPassword
            
            Por seguridad, te recomendamos cambiar esta contraseña después de iniciar sesión.
            
            Saludos,
            Equipo " . SITE_NAME . "
        ";
        
        $notificacion = new Notificacion();
        $notificacion->enviarEmail($usuario['email'], $asunto, $mensaje);
    }
    
    private function registrarAuditoria($usuarioId, $accion, $datosNuevos = [], $datosAnteriores = []) {
        $pdo = $this->db->getConnection();
        $auth = new Auth();
        $usuarioAuditoria = $auth->getUserId();
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_usuarios (usuario_id, accion, datos_anteriores, datos_nuevos, usuario_auditoria, fecha) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $usuarioId,
            $accion,
            json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE),
            json_encode($datosNuevos, JSON_UNESCAPED_UNICODE),
            $usuarioAuditoria
        ]);
    }
    
    public function obtenerPermisosGranulares($usuarioId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, pr.nombre as proceso_nombre, a.nombre as actividad_nombre 
            FROM permisos_granulares p 
            LEFT JOIN procesos pr ON p.proceso_id = pr.id 
            LEFT JOIN actividades a ON p.actividad_id = a.id 
            WHERE p.usuario_id = ? AND p.estado = 'activo'
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function asignarPermiso($usuarioId, $procesoId, $actividadId, $expira = null) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO permisos_granulares (usuario_id, proceso_id, actividad_id, fecha_expiracion, estado, creado_en) 
            VALUES (?, ?, ?, ?, 'activo', NOW())
            ON DUPLICATE KEY UPDATE estado = 'activo', fecha_expiracion = ?
        ");
        
        $stmt->execute([$usuarioId, $procesoId, $actividadId, $expira, $expira]);
        
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
        
        return true;
    }
}
?>