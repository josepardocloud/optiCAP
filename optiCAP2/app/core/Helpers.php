<?php
class Helpers {
    
    public static function getBadgeEstado($estado) {
        $clases = [
            'pendiente' => 'badge-estado-pendiente',
            'en_proceso' => 'badge-estado-en_proceso',
            'completado' => 'badge-estado-completado',
            'cancelado' => 'badge-estado-cancelado'
        ];
        
        $textos = [
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En Proceso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        ];
        
        $clase = $clases[$estado] ?? 'badge-secondary';
        $texto = $textos[$estado] ?? $estado;
        
        return "<span class='badge $clase'>$texto</span>";
    }
    
    public static function getBadgeEstadoActividad($estado) {
        $clases = [
            'pendiente' => 'badge-estado-pendiente',
            'en_proceso' => 'badge-estado-en_proceso',
            'finalizado' => 'badge-estado-finalizado',
            'rechazado' => 'badge-estado-rechazado',
            'no_aplica' => 'badge-estado-no_aplica'
        ];
        
        $textos = [
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En Proceso',
            'finalizado' => 'Finalizado',
            'rechazado' => 'Rechazado',
            'no_aplica' => 'No Aplica'
        ];
        
        $clase = $clases[$estado] ?? 'badge-secondary';
        $texto = $textos[$estado] ?? $estado;
        
        return "<span class='badge $clase'>$texto</span>";
    }
    
    public static function getBadgeEstadoUsuario($estado) {
        if ($estado === 'activo') {
            return '<span class="badge badge-success">Activo</span>';
        } else {
            return '<span class="badge badge-secondary">Inactivo</span>';
        }
    }
    
    public static function getClassEstadoActividad($estado) {
        $clases = [
            'pendiente' => 'pendiente',
            'en_proceso' => 'en_proceso',
            'finalizado' => 'finalizado',
            'rechazado' => 'rechazado',
            'no_aplica' => 'no_aplica'
        ];
        
        return $clases[$estado] ?? 'pendiente';
    }
    
    public static function getClassRol($rol) {
        $clases = [
            'Administrador' => 'danger',
            'Supervisor' => 'warning',
            'Super Usuario' => 'info',
            'Usuario' => 'secondary'
        ];
        
        return $clases[$rol] ?? 'secondary';
    }
    
    public static function getNombreRequisito($requisito) {
        $nombres = [
            'disponibilidad_presupuestal' => 'Disponibilidad Presupuestal',
            'especificaciones_tecnicas' => 'Especificaciones Técnicas',
            'terminos_referencia' => 'Términos de Referencia',
            'pca_priorizacion' => 'PCA y Priorización',
            'verificacion_especificaciones' => 'Verificación de Especificaciones',
            'conformidad_servicio' => 'Conformidad del Servicio',
            'informe_conformidad' => 'Informe de Conformidad',
            'documentacion_contable' => 'Documentación Contable',
            'existe_cuadro_multianual' => 'Existe en Cuadro Multianual'
        ];
        
        return $nombres[$requisito] ?? $requisito;
    }
    
    public static function getIconoTipoArchivo($tipoArchivo) {
        $iconos = [
            'pdf' => 'pdf',
            'doc' => 'word',
            'docx' => 'word',
            'xls' => 'excel',
            'xlsx' => 'excel',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image'
        ];
        
        $extension = strtolower(pathinfo($tipoArchivo, PATHINFO_EXTENSION));
        return $iconos[$extension] ?? 'file';
    }
    
    public static function puedeEditarActividad($actividad, $user) {
        // Lógica simplificada - se debe implementar según permisos granulares
        if ($user['rol_nombre'] === 'Administrador' || $user['rol_nombre'] === 'Supervisor') {
            return false;
        }
        
        if ($user['rol_nombre'] === 'Usuario') {
            // Verificar si el requerimiento pertenece a su área
            // Esta verificación se hace en el controlador
            return true;
        }
        
        if ($user['rol_nombre'] === 'Super Usuario') {
            // Verificar permisos granulares
            // Esta verificación se hace en el controlador
            return true;
        }
        
        return false;
    }
    
    public static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    public static function getDescripcionAccion($accion) {
        $descripciones = [
            'creacion' => 'creó el requerimiento',
            'actualizacion_actividad' => 'actualizó una actividad',
            'salto_condicional_aplicado' => 'aplicó salto condicional',
            'requerimiento_completado' => 'completó el requerimiento'
        ];
        
        return $descripciones[$accion] ?? $accion;
    }
}
?>