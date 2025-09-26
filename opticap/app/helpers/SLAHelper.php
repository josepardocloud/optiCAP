<?php
/**
 * Helper para cálculos y gestión de SLA (Service Level Agreement)
 */

class SLAHelper {
    
    /**
     * Calcular SLA para un requerimiento
     */
    public static function calcularSLARequerimiento($requerimientoId) {
        $db = Database::getInstance()->getConnection();
        
        // Obtener información del requerimiento
        $stmt = $db->prepare("
            SELECT r.*, a.nombre as area_nombre 
            FROM requerimientos r 
            LEFT JOIN areas a ON r.id_area_solicitante = a.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$requerimientoId]);
        $requerimiento = $stmt->fetch();
        
        if (!$requerimiento) {
            return false;
        }
        
        // Obtener actividades del requerimiento
        $stmt = $db->prepare("
            SELECT sr.*, a.tiempo_limite, a.nombre as actividad_nombre 
            FROM seguimiento_requerimientos sr 
            LEFT JOIN actividades a ON sr.id_actividad = a.id 
            WHERE sr.id_requerimiento = ? 
            ORDER BY a.orden
        ");
        $stmt->execute([$requerimientoId]);
        $actividades = $stmt->fetchAll();
        
        return self::calcularSLA($requerimiento, $actividades);
    }
    
    /**
     * Calcular métricas de SLA
     */
    private static function calcularSLA($requerimiento, $actividades) {
        $sla = [
            'requerimiento_id' => $requerimiento['id'],
            'codigo' => $requerimiento['codigo'],
            'area' => $requerimiento['area_nombre'],
            'fecha_creacion' => $requerimiento['fecha_creacion'],
            'fecha_limite_total' => $requerimiento['fecha_limite_total'],
            'estado_actual' => $requerimiento['estado'],
            'metricas' => [],
            'actividades' => [],
            'resumen' => []
        ];
        
        $tiempoTotalEstimado = 0;
        $tiempoTotalReal = 0;
        $actividadesCompletadas = 0;
        $actividadesAtrasadas = 0;
        $diasTranscurridos = 0;
        
        // Calcular métricas por actividad
        foreach ($actividades as $actividad) {
            $metricasActividad = self::calcularMetricasActividad($actividad);
            $sla['actividades'][] = $metricasActividad;
            
            $tiempoTotalEstimado += $actividad['tiempo_limite'];
            
            if ($actividad['estado'] === 'completado') {
                $actividadesCompletadas++;
                $tiempoTotalReal += $metricasActividad['tiempo_real'];
                
                if ($metricasActividad['atrasada']) {
                    $actividadesAtrasadas++;
                }
            }
            
            if ($actividad['estado'] === 'en_proceso') {
                $diasTranscurridos += $metricasActividad['dias_transcurridos'];
            }
        }
        
        // Calcular porcentaje de completitud
        $porcentajeCompletitud = count($actividades) > 0 ? 
            ($actividadesCompletadas / count($actividades)) * 100 : 0;
        
        // Calcular eficiencia
        $eficiencia = $tiempoTotalReal > 0 ? 
            ($tiempoTotalEstimado / $tiempoTotalReal) * 100 : 0;
        
        // Determinar estado del SLA
        $estadoSLA = self::determinarEstadoSLA($requerimiento, $actividades);
        
        $sla['resumen'] = [
            'total_actividades' => count($actividades),
            'actividades_completadas' => $actividadesCompletadas,
            'actividades_atrasadas' => $actividadesAtrasadas,
            'porcentaje_completitud' => round($porcentajeCompletitud, 2),
            'tiempo_total_estimado' => $tiempoTotalEstimado,
            'tiempo_total_real' => $tiempoTotalReal,
            'eficiencia' => round($eficiencia, 2),
            'dias_transcurridos' => $diasTranscurridos,
            'estado_sla' => $estadoSLA,
            'dentro_sla' => $estadoSLA === 'dentro'
        ];
        
        return $sla;
    }
    
    /**
     * Calcular métricas para una actividad específica
     */
    private static function calcularMetricasActividad($actividad) {
        $metricas = [
            'actividad_id' => $actividad['id'],
            'actividad_nombre' => $actividad['actividad_nombre'],
            'estado' => $actividad['estado'],
            'tiempo_limite' => $actividad['tiempo_limite'],
            'fecha_inicio_estimada' => $actividad['fecha_inicio_estimada'],
            'fecha_fin_estimada' => $actividad['fecha_fin_estimada'],
            'fecha_inicio_real' => $actividad['fecha_inicio_real'],
            'fecha_fin_real' => $actividad['fecha_fin_real']
        ];
        
        // Calcular días transcurridos
        if ($actividad['fecha_inicio_real']) {
            $inicio = new DateTime($actividad['fecha_inicio_real']);
            $hoy = new DateTime();
            $metricas['dias_transcurridos'] = $inicio->diff($hoy)->days;
        } else {
            $metricas['dias_transcurridos'] = 0;
        }
        
        // Calcular tiempo real si está completada
        if ($actividad['estado'] === 'completado' && $actividad['fecha_inicio_real'] && $actividad['fecha_fin_real']) {
            $inicio = new DateTime($actividad['fecha_inicio_real']);
            $fin = new DateTime($actividad['fecha_fin_real']);
            $metricas['tiempo_real'] = $inicio->diff($fin)->days;
        } else {
            $metricas['tiempo_real'] = null;
        }
        
        // Verificar si está atrasada
        $metricas['atrasada'] = false;
        if ($actividad['fecha_fin_estimada'] && $actividad['estado'] !== 'completado') {
            $finEstimado = new DateTime($actividad['fecha_fin_estimada']);
            $hoy = new DateTime();
            if ($hoy > $finEstimado) {
                $metricas['atrasada'] = true;
                $metricas['dias_atraso'] = $finEstimado->diff($hoy)->days;
            }
        }
        
        // Calcular porcentaje de uso del tiempo
        if ($metricas['dias_transcurridos'] > 0 && $actividad['tiempo_limite'] > 0) {
            $metricas['porcentaje_tiempo_uso'] = min(100, 
                ($metricas['dias_transcurridos'] / $actividad['tiempo_limite']) * 100
            );
        } else {
            $metricas['porcentaje_tiempo_uso'] = 0;
        }
        
        return $metricas;
    }
    
    /**
     * Determinar el estado del SLA
     */
    private static function determinarEstadoSLA($requerimiento, $actividades) {
        if ($requerimiento['estado'] === 'completado') {
            // Verificar si se completó dentro del tiempo límite
            if ($requerimiento['fecha_limite_total']) {
                $fechaLimite = new DateTime($requerimiento['fecha_limite_total']);
                $fechaCompletado = new DateTime($requerimiento['fecha_actualizacion']);
                return $fechaCompletado <= $fechaLimite ? 'dentro' : 'fuera';
            }
            return 'dentro'; // Si no hay fecha límite, se considera dentro del SLA
        }
        
        // Para requerimientos en proceso, verificar actividades atrasadas
        foreach ($actividades as $actividad) {
            if ($actividad['estado'] !== 'completado') {
                $finEstimado = new DateTime($actividad['fecha_fin_estimada']);
                $hoy = new DateTime();
                if ($hoy > $finEstimado) {
                    return 'en_riesgo';
                }
            }
        }
        
        return 'dentro';
    }
    
    /**
     * Obtener estadísticas de SLA por área
     */
    public static function obtenerEstadisticasSLAPorArea($fechaInicio = null, $fechaFin = null) {
        $db = Database::getInstance()->getConnection();
        
        $whereConditions = [];
        $params = [];
        
        if ($fechaInicio) {
            $whereConditions[] = "r.fecha_creacion >= ?";
            $params[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $whereConditions[] = "r.fecha_creacion <= ?";
            $params[] = $fechaFin;
        }
        
        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        $sql = "
            SELECT 
                a.id as area_id,
                a.nombre as area_nombre,
                COUNT(r.id) as total_requerimientos,
                SUM(CASE WHEN r.estado = 'completado' THEN 1 ELSE 0 END) as requerimientos_completados,
                SUM(CASE WHEN r.estado = 'completado' AND r.fecha_limite_total >= CURDATE() THEN 1 ELSE 0 END) as dentro_sla,
                SUM(CASE WHEN r.estado = 'completado' AND r.fecha_limite_total < CURDATE() THEN 1 ELSE 0 END) as fuera_sla,
                AVG(CASE WHEN r.estado = 'completado' THEN DATEDIFF(r.fecha_actualizacion, r.fecha_creacion) ELSE NULL END) as tiempo_promedio
            FROM areas a
            LEFT JOIN requerimientos r ON a.id = r.id_area_solicitante
            $whereClause
            GROUP BY a.id, a.nombre
            ORDER BY total_requerimientos DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll();
        
        // Calcular porcentajes
        foreach ($resultados as &$area) {
            $area['porcentaje_completados'] = $area['total_requerimientos'] > 0 ? 
                round(($area['requerimientos_completados'] / $area['total_requerimientos']) * 100, 2) : 0;
            
            $area['porcentaje_dentro_sla'] = $area['requerimientos_completados'] > 0 ? 
                round(($area['dentro_sla'] / $area['requerimientos_completados']) * 100, 2) : 0;
            
            $area['eficiencia'] = $area['tiempo_promedio'] ? 
                round(($area['tiempo_promedio'] / 30) * 100, 2) : 0; // Comparación con 30 días estándar
        }
        
        return $resultados;
    }
    
    /**
     * Obtener tendencias de SLA
     */
    public static function obtenerTendenciasSLA($meses = 6) {
        $db = Database::getInstance()->getConnection();
        
        $tendencias = [];
        $fechaFin = new DateTime();
        $fechaInicio = (new DateTime())->modify("-$meses months");
        
        // Generar rango de meses
        $periodo = new DatePeriod(
            $fechaInicio,
            new DateInterval('P1M'),
            $fechaFin
        );
        
        foreach ($periodo as $fecha) {
            $mes = $fecha->format('Y-m');
            $mesLabel = $fecha->format('M Y');
            
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                    SUM(CASE WHEN estado = 'completado' AND fecha_limite_total >= fecha_actualizacion THEN 1 ELSE 0 END) as dentro_sla
                FROM requerimientos 
                WHERE DATE_FORMAT(fecha_creacion, '%Y-%m') = ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$mes]);
            $datos = $stmt->fetch();
            
            $porcentajeSLA = $datos['completados'] > 0 ? 
                round(($datos['dentro_sla'] / $datos['completados']) * 100, 2) : 0;
            
            $tendencias[] = [
                'mes' => $mesLabel,
                'total' => $datos['total'],
                'completados' => $datos['completados'],
                'dentro_sla' => $datos['dentro_sla'],
                'porcentaje_sla' => $porcentajeSLA
            ];
        }
        
        return $tendencias;
    }
    
    /**
     * Generar alertas de SLA
     */
    public static function generarAlertasSLA() {
        $db = Database::getInstance()->getConnection();
        
        $alertas = [];
        
        // Alertas de actividades atrasadas
        $sql = "
            SELECT 
                sr.id,
                r.codigo,
                r.titulo,
                a.nombre as actividad_nombre,
                u.nombre as usuario_asignado,
                u.email as usuario_email,
                DATEDIFF(CURDATE(), sr.fecha_fin_estimada) as dias_atraso,
                sr.fecha_fin_estimada
            FROM seguimiento_requerimientos sr
            LEFT JOIN requerimientos r ON sr.id_requerimiento = r.id
            LEFT JOIN actividades a ON sr.id_actividad = a.id
            LEFT JOIN usuarios u ON sr.id_usuario_asignado = u.id
            WHERE sr.estado IN ('pendiente', 'en_proceso')
            AND sr.fecha_fin_estimada < CURDATE()
            ORDER BY dias_atraso DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $actividadesAtrasadas = $stmt->fetchAll();
        
        foreach ($actividadesAtrasadas as $actividad) {
            $nivelAlerta = $actividad['dias_atraso'] > 7 ? 'alta' : ($actividad['dias_atraso'] > 3 ? 'media' : 'baja');
            
            $alertas[] = [
                'tipo' => 'actividad_atrasada',
                'nivel' => $nivelAlerta,
                'titulo' => "Actividad atrasada: {$actividad['actividad_nombre']}",
                'descripcion' => "La actividad '{$actividad['actividad_nombre']}' del requerimiento {$actividad['codigo']} está atrasada por {$actividad['dias_atraso']} días",
                'codigo' => $actividad['codigo'],
                'usuario' => $actividad['usuario_asignado'],
                'email' => $actividad['usuario_email'],
                'dias_atraso' => $actividad['dias_atraso'],
                'fecha_limite' => $actividad['fecha_fin_estimada']
            ];
        }
        
        // Alertas de requerimientos próximos a vencer
        $sql = "
            SELECT 
                r.id,
                r.codigo,
                r.titulo,
                a.nombre as area_nombre,
                DATEDIFF(r.fecha_limite_total, CURDATE()) as dias_restantes,
                r.fecha_limite_total
            FROM requerimientos r
            LEFT JOIN areas a ON r.id_area_solicitante = a.id
            WHERE r.estado IN ('pendiente', 'en_proceso')
            AND r.fecha_limite_total IS NOT NULL
            AND r.fecha_limite_total > CURDATE()
            AND DATEDIFF(r.fecha_limite_total, CURDATE()) <= 7
            ORDER BY dias_restantes ASC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $requerimientosProximos = $stmt->fetchAll();
        
        foreach ($requerimientosProximos as $req) {
            $alertas[] = [
                'tipo' => 'requerimiento_proximo_vencer',
                'nivel' => $req['dias_restantes'] <= 3 ? 'alta' : 'media',
                'titulo' => "Requerimiento próximo a vencer: {$req['codigo']}",
                'descripcion' => "El requerimiento {$req['codigo']} vence en {$req['dias_restantes']} días",
                'codigo' => $req['codigo'],
                'area' => $req['area_nombre'],
                'dias_restantes' => $req['dias_restantes'],
                'fecha_limite' => $req['fecha_limite_total']
            ];
        }
        
        return $alertas;
    }
    
    /**
     * Calcular KPI de eficiencia
     */
    public static function calcularKPIEficiencia($areaId = null, $periodo = 'month') {
        $db = Database::getInstance()->getConnection();
        
        $whereConditions = ["r.estado = 'completado'"];
        $params = [];
        
        if ($areaId) {
            $whereConditions[] = "r.id_area_solicitante = ?";
            $params[] = $areaId;
        }
        
        // Definir período
        $fechaFiltro = '';
        switch ($periodo) {
            case 'week':
                $fechaFiltro = "r.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $fechaFiltro = "r.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            case 'quarter':
                $fechaFiltro = "r.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
                break;
            case 'year':
                $fechaFiltro = "r.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
        }
        
        if ($fechaFiltro) {
            $whereConditions[] = $fechaFiltro;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        
        $sql = "
            SELECT 
                COUNT(r.id) as total_completados,
                AVG(DATEDIFF(r.fecha_actualizacion, r.fecha_creacion)) as tiempo_promedio,
                AVG(a.tiempo_limite) as tiempo_limite_promedio,
                SUM(CASE WHEN r.fecha_limite_total >= r.fecha_actualizacion THEN 1 ELSE 0 END) as dentro_sla
            FROM requerimientos r
            LEFT JOIN areas ar ON r.id_area_solicitante = ar.id
            LEFT JOIN seguimiento_requerimientos sr ON r.id = sr.id_requerimiento
            LEFT JOIN actividades a ON sr.id_actividad = a.id
            $whereClause
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetch();
        
        // Calcular KPIs
        $kpis = [
            'total_completados' => $datos['total_completados'],
            'tiempo_promedio' => round($datos['tiempo_promedio'], 1),
            'eficiencia_tiempo' => $datos['tiempo_limite_promedio'] > 0 ? 
                round(($datos['tiempo_limite_promedio'] / $datos['tiempo_promedio']) * 100, 2) : 0,
            'tasa_cumplimiento_sla' => $datos['total_completados'] > 0 ? 
                round(($datos['dentro_sla'] / $datos['total_completados']) * 100, 2) : 0
        ];
        
        return $kpis;
    }
}
?>