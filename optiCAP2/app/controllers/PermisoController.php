<?php
class PermisoController {
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Database();
        $this->checkAccess();
    }
    
    private function checkAccess() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login');
            exit;
        }
    }
    
    public function solicitar() {
        $user = $this->auth->getUser();
        
        // Solo usuarios y super usuarios pueden solicitar permisos
        if (!in_array($user['rol_nombre'], ['Usuario', 'Super Usuario'])) {
            $_SESSION['error'] = 'No tiene permisos para solicitar permisos adicionales';
            header('Location: ' . SITE_URL . '/dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->crearSolicitudPermiso($user['id'], $_POST);
                $_SESSION['success'] = "Solicitud de permiso enviada correctamente";
                header('Location: ' . SITE_URL . '/solicitar-permisos');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $procesos = $this->obtenerProcesosConActividades();
        $solicitudesPendientes = $this->obtenerSolicitudesUsuario($user['id']);
        
        $data = [
            'pageTitle' => 'Solicitar Permisos Adicionales',
            'currentPage' => 'permisos',
            'user' => $user,
            'procesos' => $procesos,
            'solicitudesPendientes' => $solicitudesPendientes,
            'error' => $error ?? null
        ];
        
        $this->renderView('permisos/solicitar', $data);
    }
    
    public function gestionar() {
        $user = $this->auth->getUser();
        
        // Solo administradores pueden gestionar permisos
        if ($user['rol_nombre'] !== 'Administrador') {
            $_SESSION['error'] = 'No tiene permisos para gestionar permisos';
            header('Location: ' . SITE_URL . '/dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->procesarSolicitud($_POST);
                $_SESSION['success'] = "Solicitud procesada correctamente";
                header('Location: ' . SITE_URL . '/gestionar-permisos');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $solicitudes = $this->obtenerSolicitudesPendientes();
        
        $data = [
            'pageTitle' => 'Gestionar Solicitudes de Permisos',
            'currentPage' => 'permisos',
            'user' => $user,
            'solicitudes' => $solicitudes,
            'error' => $error ?? null
        ];
        
        $this->renderView('permisos/gestionar', $data);
    }
    
    private function crearSolicitudPermiso($usuarioId, $data) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO solicitudes_permisos (usuario_id, proceso_id, actividad_id, justificacion, estado, fecha_solicitud) 
            VALUES (?, ?, ?, ?, 'pendiente', NOW())
        ");
        
        $stmt->execute([
            $usuarioId,
            $data['proceso_id'],
            $data['actividad_id'],
            trim($data['justificacion'])
        ]);
        
        // Notificar a administradores
        $this->notificarNuevaSolicitud($pdo->lastInsertId());
    }
    
    private function procesarSolicitud($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $solicitudId = $data['solicitud_id'];
            $accion = $data['accion'];
            $observaciones = $data['observaciones'] ?? '';
            
            if ($accion === 'aprobar') {
                // Obtener información de la solicitud
                $stmt = $pdo->prepare("
                    SELECT usuario_id, proceso_id, actividad_id 
                    FROM solicitudes_permisos 
                    WHERE id = ?
                ");
                $stmt->execute([$solicitudId]);
                $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$solicitud) {
                    throw new Exception("Solicitud no encontrada");
                }
                
                // Asignar permiso
                $usuarioModel = new Usuario();
                $fechaExpiracion = !empty($data['fecha_expiracion']) ? $data['fecha_expiracion'] : null;
                $usuarioModel->asignarPermiso(
                    $solicitud['usuario_id'],
                    $solicitud['proceso_id'],
                    $solicitud['actividad_id'],
                    $fechaExpiracion
                );
                
                $nuevoEstado = 'aprobada';
            } else {
                $nuevoEstado = 'rechazada';
            }
            
            // Actualizar estado de la solicitud
            $stmt = $pdo->prepare("
                UPDATE solicitudes_permisos 
                SET estado = ?, observaciones_administrador = ?, fecha_resolucion = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$nuevoEstado, $observaciones, $solicitudId]);
            
            // Notificar al usuario
            $this->notificarResolucionSolicitud($solicitudId, $accion);
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    private function obtenerProcesosConActividades() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT p.*, tp.nombre as tipo_proceso_nombre
            FROM procesos p
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE p.estado = 'activo'
            ORDER BY tp.nombre, p.nombre
        ");
        $procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($procesos as &$proceso) {
            $stmt = $pdo->prepare("
                SELECT * FROM actividades 
                WHERE proceso_id = ? AND estado = 'activo' 
                ORDER BY orden
            ");
            $stmt->execute([$proceso['id']]);
            $proceso['actividades'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $procesos;
    }
    
    private function obtenerSolicitudesUsuario($usuarioId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, p.nombre as proceso_nombre, a.nombre as actividad_nombre, a.numero_paso
            FROM solicitudes_permisos sp
            JOIN procesos p ON sp.proceso_id = p.id
            JOIN actividades a ON sp.actividad_id = a.id
            WHERE sp.usuario_id = ? AND sp.estado = 'pendiente'
            ORDER BY sp.fecha_solicitud DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenerSolicitudesPendientes() {
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
    
    private function notificarNuevaSolicitud($solicitudId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, u.nombre as usuario_nombre, p.nombre as proceso_nombre, a.nombre as actividad_nombre
            FROM solicitudes_permisos sp
            JOIN usuarios u ON sp.usuario_id = u.id
            JOIN procesos p ON sp.proceso_id = p.id
            JOIN actividades a ON sp.actividad_id = a.id
            WHERE sp.id = ?
        ");
        $stmt->execute([$solicitudId]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $asunto = "Nueva Solicitud de Permiso - " . SITE_NAME;
        $mensaje = "
            Se ha recibido una nueva solicitud de permiso:
            
            Usuario: {$solicitud['usuario_nombre']}
            Proceso: {$solicitud['proceso_nombre']}
            Actividad: {$solicitud['actividad_nombre']}
            Justificación: {$solicitud['justificacion']}
            
            Puede gestionar esta solicitud desde el sistema.
        ";
        
        $notificacion = new Notificacion();
        $notificacion->notificarAdministradores($asunto, $mensaje, 
                                              SITE_URL . '/gestionar-permisos');
    }
    
    private function notificarResolucionSolicitud($solicitudId, $accion) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, u.nombre as usuario_nombre, u.email as usuario_email,
                   p.nombre as proceso_nombre, a.nombre as actividad_nombre
            FROM solicitudes_permisos sp
            JOIN usuarios u ON sp.usuario_id = u.id
            JOIN procesos p ON sp.proceso_id = p.id
            JOIN actividades a ON sp.actividad_id = a.id
            WHERE sp.id = ?
        ");
        $stmt->execute([$solicitudId]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $estado = $accion === 'aprobar' ? 'APROBADA' : 'RECHAZADA';
        
        $asunto = "Solicitud de Permiso $estado - " . SITE_NAME;
        $mensaje = "
            Su solicitud de permiso ha sido $estado:
            
            Proceso: {$solicitud['proceso_nombre']}
            Actividad: {$solicitud['actividad_nombre']}
            Observaciones: {$solicitud['observaciones_administrador']}
            
            Fecha de resolución: " . date('d/m/Y H:i') . "
        ";
        
        $notificacion = new Notificacion();
        $notificacion->notificarUsuario($solicitud['usuario_id'], $asunto, $mensaje);
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/$view.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>