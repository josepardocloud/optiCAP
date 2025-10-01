<?php
class Validator {
    
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function required($value) {
        return !empty(trim($value));
    }
    
    public static function minLength($value, $min) {
        return strlen(trim($value)) >= $min;
    }
    
    public static function maxLength($value, $max) {
        return strlen(trim($value)) <= $max;
    }
    
    public static function numeric($value) {
        return is_numeric($value);
    }
    
    public static function integer($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public static function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function fileType($filename, $allowedTypes) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes);
    }
    
    public static function fileSize($size, $maxSize) {
        return $size <= $maxSize;
    }
    
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $data[$field] ?? null;
            
            foreach ($rulesArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (!self::required($value)) {
                            $errors[$field][] = "El campo $field es requerido";
                        }
                        break;
                        
                    case 'email':
                        if (!self::email($value)) {
                            $errors[$field][] = "El campo $field debe ser un email válido";
                        }
                        break;
                        
                    case 'min':
                        if (!self::minLength($value, $ruleParam)) {
                            $errors[$field][] = "El campo $field debe tener al menos $ruleParam caracteres";
                        }
                        break;
                        
                    case 'max':
                        if (!self::maxLength($value, $ruleParam)) {
                            $errors[$field][] = "El campo $field no puede tener más de $ruleParam caracteres";
                        }
                        break;
                        
                    case 'numeric':
                        if (!self::numeric($value)) {
                            $errors[$field][] = "El campo $field debe ser numérico";
                        }
                        break;
                        
                    case 'integer':
                        if (!self::integer($value)) {
                            $errors[$field][] = "El campo $field debe ser un número entero";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
}
?>