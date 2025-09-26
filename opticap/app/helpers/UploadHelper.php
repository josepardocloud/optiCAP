<?php
class UploadHelper {
    
    public function subirArchivo($archivo, $tipo = 'general', $tiposPermitidos = null) {
        if ($tiposPermitidos === null) {
            $tiposPermitidos = Config::ALLOWED_FILE_TYPES;
        }
        
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir el archivo: ' . $archivo['error']];
        }
        
        // Verificar tamaño
        if ($archivo['size'] > Config::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'El archivo excede el tamaño máximo permitido'];
        }
        
        // Verificar tipo
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $tiposPermitidos)) {
            return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
        }
        
        // Crear directorio si no existe
        $directorio = UPLOAD_PATH . $tipo . '/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Generar nombre único
        $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
        $rutaCompleta = $directorio . $nombreArchivo;
        
        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            return [
                'success' => true,
                'file_name' => $archivo['name'],
                'file_path' => $tipo . '/' . $nombreArchivo,
                'file_size' => $archivo['size']
            ];
        } else {
            return ['success' => false, 'error' => 'Error al guardar el archivo'];
        }
    }
    
    public function subirMultiplesArchivos($archivos, $tipo = 'general') {
        $resultados = [];
        
        for ($i = 0; $i < count($archivos['name']); $i++) {
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                $archivoIndividual = [
                    'name' => $archivos['name'][$i],
                    'type' => $archivos['type'][$i],
                    'tmp_name' => $archivos['tmp_name'][$i],
                    'error' => $archivos['error'][$i],
                    'size' => $archivos['size'][$i]
                ];
                
                $resultado = $this->subirArchivo($archivoIndividual, $tipo);
                if ($resultado['success']) {
                    $resultados[] = $resultado;
                }
            }
        }
        
        return $resultados;
    }
    
    public function eliminarArchivo($rutaArchivo) {
        $rutaCompleta = UPLOAD_PATH . $rutaArchivo;
        if (file_exists($rutaCompleta)) {
            return unlink($rutaCompleta);
        }
        return false;
    }
}
?>