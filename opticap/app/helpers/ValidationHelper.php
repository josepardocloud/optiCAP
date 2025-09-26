<?php
/**
 * Helper de validación para el sistema OptiCAP
 */

class ValidationHelper {
    
    /**
     * Validar email
     */
    public static function validateEmail($email) {
        if (empty($email)) {
            return 'El email es obligatorio';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'El formato del email no es válido';
        }
        
        if (strlen($email) > 150) {
            return 'El email no puede tener más de 150 caracteres';
        }
        
        return true;
    }
    
    /**
     * Validar contraseña
     */
    public static function validatePassword($password, $confirmPassword = null) {
        if (empty($password)) {
            return 'La contraseña es obligatoria';
        }
        
        if (strlen($password) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }
        
        if ($confirmPassword !== null && $password !== $confirmPassword) {
            return 'Las contraseñas no coinciden';
        }
        
        return true;
    }
    
    /**
     * Validar nombre
     */
    public static function validateName($name, $field = 'Nombre') {
        if (empty($name)) {
            return "El $field es obligatorio";
        }
        
        if (strlen($name) < 2) {
            return "El $field debe tener al menos 2 caracteres";
        }
        
        if (strlen($name) > 100) {
            return "El $field no puede tener más de 100 caracteres";
        }
        
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $name)) {
            return "El $field solo puede contener letras y espacios";
        }
        
        return true;
    }
    
    /**
     * Validar texto
     */
    public static function validateText($text, $field = 'Texto', $maxLength = 1000) {
        if (empty($text)) {
            return "El $field es obligatorio";
        }
        
        if (strlen($text) > $maxLength) {
            return "El $field no puede tener más de $maxLength caracteres";
        }
        
        return true;
    }
    
    /**
     * Validar número
     */
    public static function validateNumber($number, $field = 'Número', $min = null, $max = null) {
        if (!is_numeric($number)) {
            return "El $field debe ser un número válido";
        }
        
        $number = (float) $number;
        
        if ($min !== null && $number < $min) {
            return "El $field debe ser mayor o igual a $min";
        }
        
        if ($max !== null && $number > $max) {
            return "El $field debe ser menor o igual a $max";
        }
        
        return true;
    }
    
    /**
     * Validar fecha
     */
    public static function validateDate($date, $field = 'Fecha') {
        if (empty($date)) {
            return "La $field es obligatoria";
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            return "El formato de la $field no es válido (YYYY-MM-DD)";
        }
        
        // Verificar que la fecha no sea futura (para ciertos casos)
        if ($date > date('Y-m-d')) {
            return "La $field no puede ser futura";
        }
        
        return true;
    }
    
    /**
     * Validar archivo
     */
    public static function validateFile($file, $allowedTypes, $maxSize) {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'No se ha seleccionado ningún archivo';
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo: ' . self::getUploadError($file['error']);
        }
        
        // Validar tamaño
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            return "El archivo excede el tamaño máximo permitido ($maxSizeMB MB)";
        }
        
        // Validar tipo
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            $allowedTypesStr = implode(', ', $allowedTypes);
            return "Tipo de archivo no permitido. Formatos aceptados: $allowedTypesStr";
        }
        
        // Validar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (isset($allowedMimes[$fileExtension]) && $allowedMimes[$fileExtension] !== $mimeType) {
            return 'El tipo MIME del archivo no coincide con su extensión';
        }
        
        return true;
    }
    
    /**
     * Validar selección de opción
     */
    public static function validateOption($value, $options, $field = 'Opción') {
        if (empty($value)) {
            return "La $field es obligatoria";
        }
        
        if (!in_array($value, $options)) {
            return "La $field seleccionada no es válida";
        }
        
        return true;
    }
    
    /**
     * Validar URL
     */
    public static function validateURL($url, $field = 'URL') {
        if (empty($url)) {
            return "La $field es obligatoria";
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return "El formato de la $field no es válido";
        }
        
        // Verificar protocolos permitidos
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'], ['http', 'https'])) {
            return "La $field debe usar HTTP o HTTPS";
        }
        
        return true;
    }
    
    /**
     * Validar código único
     */
    public static function validateUniqueCode($code, $table, $field = 'código', $excludeId = null) {
        if (empty($code)) {
            return "El $field es obligatorio";
        }
        
        if (strlen($code) > 50) {
            return "El $field no puede tener más de 50 caracteres";
        }
        
        // Verificar unicidad en la base de datos
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM $table WHERE codigo = ?";
        $params = [$code];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return "El $field ya existe en el sistema";
        }
        
        return true;
    }
    
    /**
     * Validar múltiples campos
     */
    public static function validateMultiple($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $validation = self::validateField($value, $rule, $field);
            
            if ($validation !== true) {
                $errors[$field] = $validation;
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Validar campo individual con reglas
     */
    private static function validateField($value, $rules, $fieldName) {
        $rules = explode('|', $rules);
        
        foreach ($rules as $rule) {
            $params = [];
            
            // Verificar si la regla tiene parámetros
            if (strpos($rule, ':') !== false) {
                list($rule, $paramStr) = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }
            
            array_unshift($params, $value);
            array_push($params, $fieldName);
            
            $method = 'validate' . ucfirst($rule);
            
            if (method_exists(__CLASS__, $method)) {
                $result = call_user_func_array([__CLASS__, $method], $params);
                if ($result !== true) {
                    return $result;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Regla: required
     */
    private static function validateRequired($value, $field) {
        if (empty($value)) {
            return "El campo $field es obligatorio";
        }
        return true;
    }
    
    /**
     * Regla: email
     */
    private static function validateEmailRule($value, $field) {
        return self::validateEmail($value);
    }
    
    /**
     * Regla: min
     */
    private static function validateMin($value, $min, $field) {
        if (strlen($value) < $min) {
            return "El campo $field debe tener al menos $min caracteres";
        }
        return true;
    }
    
    /**
     * Regla: max
     */
    private static function validateMax($value, $max, $field) {
        if (strlen($value) > $max) {
            return "El campo $field no puede tener más de $max caracteres";
        }
        return true;
    }
    
    /**
     * Regla: numeric
     */
    private static function validateNumeric($value, $field) {
        if (!is_numeric($value)) {
            return "El campo $field debe ser un número";
        }
        return true;
    }
    
    /**
     * Regla: date
     */
    private static function validateDateRule($value, $field) {
        return self::validateDate($value, $field);
    }
    
    /**
     * Obtener mensaje de error de subida
     */
    private static function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo fue solo parcialmente subido',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
        ];
        
        return $errors[$errorCode] ?? 'Error desconocido al subir el archivo';
    }
    
    /**
     * Sanitizar datos antes de validar
     */
    public static function sanitizeData($data) {
        if (is_array($data)) {
            return array_map([__CLASS__, 'sanitizeData'], $data);
        }
        
        if (is_string($data)) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Validar RUC/DNI (ejemplo para Perú)
     */
    public static function validateDocument($document, $type = 'dni') {
        if (empty($document)) {
            return 'El número de documento es obligatorio';
        }
        
        $document = preg_replace('/[^0-9]/', '', $document);
        
        if ($type === 'dni') {
            if (strlen($document) !== 8) {
                return 'El DNI debe tener 8 dígitos';
            }
        } elseif ($type === 'ruc') {
            if (strlen($document) !== 11) {
                return 'El RUC debe tener 11 dígitos';
            }
        }
        
        return true;
    }
    
    /**
     * Validar teléfono
     */
    public static function validatePhone($phone) {
        if (empty($phone)) {
            return 'El teléfono es obligatorio';
        }
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) < 9 || strlen($phone) > 12) {
            return 'El número de teléfono no es válido';
        }
        
        return true;
    }
}
?>