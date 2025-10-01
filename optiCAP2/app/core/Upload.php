<?php
class Upload {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function subirEvidencia($requerimientoActividadId, $fileData) {
        // Validar archivo
        $this->validarArchivo($fileData);
        
        // Generar nombre único
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $nombreUnico = $this->generarNombreUnico($extension);
        $rutaCompleta = $this->getRutaEvidencias() . $nombreUnico;
        
        // Mover archivo
        if (!move_uploaded_file($fileData['tmp_name'], $rutaCompleta)) {
            throw new Exception("Error al subir el archivo");
        }
        
        // Guardar en base de datos
        $this->guardarEvidenciaBD($requerimientoActividadId, $fileData, $nombreUnico, $rutaCompleta);
        
        return $nombreUnico;
    }
    
    private function validarArchivo($fileData) {
        // Verificar errores de upload
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getMensajeError($fileData['error']));
        }
        
        // Verificar tamaño
        if ($fileData['size'] > MAX_FILE_SIZE) {
            throw new Exception("El archivo excede el tamaño máximo permitido de " . (MAX_FILE_SIZE / 1024 / 1024) . "MB");
        }
        
        // Verificar tipo de archivo
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            throw new Exception("Tipo de archivo no permitido. Formatos aceptados: " . implode(', ', ALLOWED_EXTENSIONS));
        }
        
        // Verificar tipo MIME
        $mimeTypesPermitidos = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $mimeTypesPermitidos)) {
            throw new Exception("Tipo MIME no permitido");
        }
    }
    
    private function generarNombreUnico($extension) {
        return uniqid() . '_' . time() . '.' . $extension;
    }
    
    private function getRutaEvidencias() {
        $ruta = ROOT_PATH . '/public/uploads/evidencias/';
        
        // Crear directorio si no existe
        if (!is_dir($ruta)) {
            mkdir($ruta, 0755, true);
        }
        
        return $ruta;
    }
    
    private function guardarEvidenciaBD($requerimientoActividadId, $fileData, $nombreUnico, $rutaCompleta) {
        $pdo = $this->db->getConnection();
        $auth = new Auth();
        $usuarioId = $auth->getUserId();
        
        // Determinar tipo de evidencia
        $tipoEvidencia = $this->determinarTipoEvidencia($requerimientoActividadId, $fileData['name']);
        
        $stmt = $pdo->prepare("
            INSERT INTO evidencias (requerimiento_actividad_id, usuario_id, nombre_archivo, 
                                  nombre_original, tipo_archivo, tamaño, ruta, tipo_evidencia) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $requerimientoActividadId,
            $usuarioId,
            $nombreUnico,
            $fileData['name'],
            $fileData['type'],
            $fileData['size'],
            '/public/uploads/evidencias/' . $nombreUnico,
            $tipoEvidencia
        ]);
    }
    
    private function determinarTipoEvidencia($requerimientoActividadId, $nombreArchivo) {
        $pdo = $this->db->getConnection();
        
        // Obtener información de la actividad
        $stmt = $pdo->prepare("
            SELECT a.numero_paso, tp.codigo as tipo_proceso
            FROM requerimiento_actividades ra
            JOIN actividades a ON ra.actividad_id = a.id
            JOIN procesos p ON a.proceso_id = p.id
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE ra.id = ?
        ");
        $stmt->execute([$requerimientoActividadId]);
        $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$actividad) {
            return 'otro';
        }
        
        // Mapear tipos de evidencia según el paso y tipo de proceso
        $numeroPaso = $actividad['numero_paso'];
        $tipoProceso = $actividad['tipo_proceso'];
        
        switch ($numeroPaso) {
            case 2:
                return 'disponibilidad_presupuestal';
            case 5:
                return $tipoProceso === 'BIEN' ? 'especificaciones_tecnicas' : 'terminos_referencia';
            case 6:
                return 'pca_priorizacion';
            case 12:
                return $tipoProceso === 'BIEN' ? 'conformidad_bienes' : 'otro';
            case 13:
                return $tipoProceso === 'SERV' ? 'conformidad_servicio' : 'otro';
            case 14:
                return 'documentacion_contable';
            default:
                return 'otro';
        }
    }
    
    private function getMensajeError($errorCode) {
        $mensajes = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo fue solo parcialmente subido',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
        ];
        
        return $mensajes[$errorCode] ?? 'Error desconocido al subir el archivo';
    }
    
    public function eliminarEvidencia($evidenciaId) {
        $pdo = $this->db->getConnection();
        
        // Obtener información del archivo
        $stmt = $pdo->prepare("SELECT ruta FROM evidencias WHERE id = ?");
        $stmt->execute([$evidenciaId]);
        $evidencia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($evidencia) {
            // Eliminar archivo físico
            $rutaCompleta = ROOT_PATH . $evidencia['ruta'];
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
            
            // Eliminar registro de la base de datos
            $stmt = $pdo->prepare("DELETE FROM evidencias WHERE id = ?");
            $stmt->execute([$evidenciaId]);
        }
    }
}
?>