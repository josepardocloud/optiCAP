<?php
class IncidenciaController {
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
    
    public function listar() {
        $user = $this->auth->getUser();
        $filtros = $_GET ?? [];
        
        // Aplicar filtros según rol
        if (in_array($user['rol_nombre'], ['Usuario', 'Super Usuario'])) {
            $filtros['usuario_reportero_id'] = $user['id'];
        }
        
        $incidencias = $this->obtenerIncidencias($filtros);
        
        $data = [
            'pageTitle' => 'Gestión de Incidencias',
            'currentPage' => 'incidencias',
            'user' => $user,
            'incidencias' => $incidencias,
            'filtros' => $filtros
        ];
        
        $this->renderView('incidencias/listar', $data);
    }
    
    public function reportar() {
        $user = $this->auth->getUser();
        
        // Solo usuarios y super usuarios pueden reportar incidencias
        if (!in_array($user['rol_nombre'], ['Usuario', 'Super Usuario'])) {
            $_SESSION['error'] = 'No tiene permisos para reportar incidencias';
            header('Location: ' . SITE_URL . '/incidencias');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = $this->validarDatosIncidencia($_POST);
                $data['usuario_reportero_id'] = $user['id'];
                
                $incidenciaId = $this->crearIncidencia($data);
                
                // Procesar evidencias si se enviaron
                if (!empty($_FILES['evidencias'])) {
                    $this->procesarEvidenciasIncidencia($incidenciaId, $_FILES['evidencias']);
                }
                
                $_SESSION['success'] = "Incidencia reportada correctamente";
                header('Location: ' . SITE_URL . '/incidencias');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $requerimientos = $this->obtenerRequerimientosUsuario($user);
        
        $data = [
            'pageTitle' => 'Reportar Incidencia',
            'currentPage' => 'incidencias',
            'user' => $user,
            'requerimientos' => $requerimientos,
            'error' => $error ?? null
        ];
        
        $this->renderView('incidencias/reportar', $data);
    }
    
    public function resolver($id) {
        $user = $this->auth->getUser();
        
        // Solo administradores pueden resolver incidencias
        if ($user['rol_nombre'] !== 'Administrador') {
            $_SESSION['error'] = 'No tiene permisos para resolver incidencias';
            header('Location: ' . SITE_URL . '/incidencias');
            exit;
        }
        
        $incidencia = $this->obtenerIncidenciaCompleta($id);
        
        if (!$incidencia) {
            $_SESSION['error'] = 'Incidencia no encontrada';
            header('Location: ' . SITE_URL . '/incidencias');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->resolverIncidencia($id, $_POST, $user['id']);
                $_SESSION['success'] = "Incidencia resuelta correctamente";
                header('Location: ' . SITE_URL . '/incidencias');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $evidencias = $this->obtenerEvidenciasIncidencia($id);
        
        $data = [
            'pageTitle' => 'Resolver Incidencia: ' . $incidencia['codigo'],
            'currentPage' => 'incidencias',
            'user' => $user,
            'incidencia' => $incidencia,
            'evidencias' => $evidencias,
            'error' => $error ?? null
        ];
        
        $this->renderView('incidencias/resolver', $data);
    }
    
    private function obtenerIncidencias($filtros = []) {
        $pdo = $this->db->getConnection();
        
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['usuario_reportero_id'])) {
            $where .= " AND i.usuario_reportero_id = ?";
            $params[] = $filtros['usuario_reportero_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $where .= " AND i.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['prioridad'])) {
            $where .= " AND i.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }
        
        $stmt = $pdo->prepare("
            SELECT i.*, u.nombre as usuario_reportero_nombre,
                   ur.nombre as usuario_resolutor_nombre,
                   r.codigo as requerimiento_codigo
            FROM incidencias i
            JOIN usuarios u ON i.usuario_reportero_id = u.id
            LEFT JOIN usuarios ur ON i.usuario_resolutor_id = ur.id
            LEFT JOIN requerimientos r ON i.requerimiento_id = r.id
            $where
            ORDER BY i.fecha_reporte DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function validarDatosIncidencia($postData) {
        $required = ['titulo', 'descripcion', 'requerimiento_id'];
        
        foreach ($required as $field) {
            if (empty($postData[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
        
        return [
            'requerimiento_id' => intval($postData['requerimiento_id']),
            'titulo' => trim($postData['titulo']),
            'descripcion' => trim($postData['descripcion']),
            'tipo' => $postData['tipo'] ?? 'funcional',
            'prioridad' => $postData['prioridad'] ?? 'media'
        ];
    }
    
    private function crearIncidencia($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Generar código único
            $codigo = 'INC-' . date('Y') . '-' . str_pad($this->obtenerProximoSecuencial(), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO incidencias (codigo, requerimiento_id, usuario_reportero_id, 
                                       titulo, descripcion, tipo, prioridad, fecha_reporte) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $codigo,
                $data['requerimiento_id'],
                $data['usuario_reportero_id'],
                $data['titulo'],
                $data['descripcion'],
                $data['tipo'],
                $data['prioridad']
            ]);
            
            $incidenciaId = $pdo->lastInsertId();
            
            // Notificar a administradores
            $this->notificarNuevaIncidencia($incidenciaId);
            
            $pdo->commit();
            return $incidenciaId;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    private function obtenerProximoSecuencial() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM incidencias WHERE YEAR(fecha_reporte) = YEAR(NOW())");
        return $stmt->fetchColumn() + 1;
    }
    
    private function procesarEvidenciasIncidencia($incidenciaId, $archivos) {
        $uploader = new Upload();
        
        foreach ($archivos['name'] as $key => $name) {
            if ($archivos['error'][$key] === UPLOAD_ERR_OK) {
                $fileData = [
                    'name' => $archivos['name'][$key],
                    'type' => $archivos['type'][$key],
                    'tmp_name' => $archivos['tmp_name'][$key],
                    'error' => $archivos['error'][$key],
                    'size' => $archivos['size'][$key]
                ];
                
                $this->guardarEvidenciaIncidencia($incidenciaId, $fileData);
            }
        }
    }
    
    private function guardarEvidenciaIncidencia($incidenciaId, $fileData) {
        $pdo = $this->db->getConnection();
        
        // Generar nombre único
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $nombreUnico = uniqid() . '_' . time() . '.' . $extension;
        $ruta = ROOT_PATH . '/public/uploads/incidencias/';
        
        // Crear directorio si no existe
        if (!is_dir($ruta)) {
            mkdir($ruta, 0755, true);
        }
        
        $rutaCompleta = $ruta . $nombreUnico;
        
        // Mover archivo
        if (!move_uploaded_file($fileData['tmp_name'], $rutaCompleta)) {
            throw new Exception("Error al subir el archivo de evidencia");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO incidencia_evidencias (incidencia_id, nombre_archivo, nombre_original, 
                                             tipo_archivo, tamaño, ruta) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $incidenciaId,
            $nombreUnico,
            $fileData['name'],
            $fileData['type'],
            $fileData['size'],
            '/public/uploads/incidencias/' . $nombreUnico
        ]);
    }
    
    private function obtenerIncidenciaCompleta($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT i.*, u.nombre as usuario_reportero_nombre,
                   ur.nombre as usuario_resolutor_nombre,
                   r.codigo as requerimiento_codigo, r.motivo as requerimiento_motivo
            FROM incidencias i
            JOIN usuarios u ON i.usuario_reportero_id = u.id
            LEFT JOIN usuarios ur ON i.usuario_resolutor_id = ur.id
            LEFT JOIN requerimientos r ON i.requerimiento_id = r.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function resolverIncidencia($id, $postData, $usuarioResolutorId) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE incidencias 
            SET estado = 'resuelto', usuario_resolutor_id = ?, solucion = ?, fecha_resolucion = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $usuarioResolutorId,
            trim($postData['solucion']),
            $id
        ]);
        
        // Notificar al usuario reportero
        $this->notificarResolucionIncidencia($id);
    }
    
    private function obtenerEvidenciasIncidencia($incidenciaId) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM incidencia_evidencias 
            WHERE incidencia_id = ? 
            ORDER BY creado_en DESC
        ");
        $stmt->execute([$incidenciaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenerRequerimientosUsuario($user) {
        $pdo = $this->db->getConnection();
        
        if ($user['rol_nombre'] === 'Usuario') {
            $stmt = $pdo->prepare("
                SELECT id, codigo, motivo 
                FROM requerimientos 
                WHERE area_id = ? 
                ORDER BY fecha_creacion DESC
            ");
            $stmt->execute([$user['area_id']]);
        } else {
            $stmt = $pdo->query("
                SELECT id, codigo, motivo 
                FROM requerimientos 
                ORDER BY fecha_creacion DESC
            ");
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function notificarNuevaIncidencia($incidenciaId) {
        $incidencia = $this->obtenerIncidenciaCompleta($incidenciaId);
        
        $asunto = "Nueva Incidencia Reportada - " . $incidencia['codigo'];
        $mensaje = "
            Se ha reportado una nueva incidencia en el sistema:
            
            Código: {$incidencia['codigo']}
            Título: {$incidencia['titulo']}
            Reportada por: {$incidencia['usuario_reportero_nombre']}
            Prioridad: " . ucfirst($incidencia['prioridad']) . "
            
            Descripción:
            {$incidencia['descripcion']}
            
            Puede acceder a la incidencia desde el sistema para resolverla.
        ";
        
        $notificacion = new Notificacion();
        $notificacion->notificarAdministradores($asunto, $mensaje, 
                                              SITE_URL . '/incidencias/resolver/' . $incidenciaId);
    }
    
    private function notificarResolucionIncidencia($incidenciaId) {
        $incidencia = $this->obtenerIncidenciaCompleta($incidenciaId);
        
        $asunto = "Incidencia Resuelta - " . $incidencia['codigo'];
        $mensaje = "
            Su incidencia ha sido resuelta:
            
            Código: {$incidencia['codigo']}
            Título: {$incidencia['titulo']}
            Resuelta por: {$incidencia['usuario_resolutor_nombre']}
            Fecha de resolución: " . date('d/m/Y H:i') . "
            
            Solución aplicada:
            {$incidencia['solucion']}
        ";
        
        $notificacion = new Notificacion();
        $notificacion->notificarUsuario($incidencia['usuario_reportero_id'], $asunto, $mensaje,
                                      SITE_URL . '/incidencias');
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/$view.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>