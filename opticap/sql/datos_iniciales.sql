-- Datos iniciales para el sistema OptiCAP

-- Insertar áreas iniciales
INSERT INTO areas (nombre, descripcion) VALUES 
('Administración General', 'Área responsable de la administración general de la organización'),
('Finanzas y Contabilidad', 'Departamento de gestión financiera y contable'),
('Compras y Adquisiciones', 'Área encargada de las compras y procesos de adquisición'),
('Logística y Distribución', 'Departamento de logística y gestión de distribución'),
('Recursos Humanos', 'Área de gestión del talento humano'),
('Tecnología de la Información', 'Departamento de sistemas e infraestructura tecnológica'),
('Operaciones', 'Área de operaciones y procesos productivos'),
('Calidad y Control', 'Departamento de garantía de calidad y control');

-- Insertar actividades del proceso de abastecimiento
INSERT INTO actividades (nombre, descripcion, tiempo_limite, orden) VALUES 
('Generación del Requerimiento', 'Identificación y formalización de la necesidad de bienes o servicios', 1, 1),
('Revisión y Aprobación Inicial', 'Validación inicial del requerimiento por parte del área solicitante', 1, 2),
('Análisis de Viabilidad', 'Evaluación técnica y económica de la solicitud', 2, 3),
('Aprobación Presupuestaria', 'Autorización de los recursos financieros necesarios', 2, 4),
('Elaboración de Términos de Referencia', 'Desarrollo de especificaciones técnicas y condiciones', 3, 5),
('Cotización y Búsqueda de Proveedores', 'Obtención de cotizaciones y evaluación de oferentes', 4, 6),
('Evaluación Técnica', 'Análisis comparativo de las propuestas recibidas', 3, 7),
('Negociación Contractual', 'Proceso de negociación con el proveedor seleccionado', 3, 8),
('Elaboración de Contrato', 'Redacción y formalización del documento contractual', 2, 9),
('Firma y Formalización', 'Firmas de autoridades y formalización del contrato', 1, 10),
('Seguimiento de Ejecución', 'Control y monitoreo del cumplimiento contractual', 7, 11),
('Recepción y Verificación', 'Verificación de bienes o servicios recibidos', 2, 12),
('Pago y Liquidación', 'Proceso de pago y liquidación contractual', 3, 13),
('Cierre y Evaluación', 'Cierre del proceso y evaluación de resultados', 1, 14);

-- Insertar usuario administrador por defecto (password: Admin123)
INSERT INTO usuarios (nombre, email, password, id_area, rol, primer_login, activo) VALUES 
('Administrador del Sistema', 'admin@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'admin', 0, 1);

-- Insertar usuarios de ejemplo
INSERT INTO usuarios (nombre, email, password, id_area, rol, primer_login, activo) VALUES 
('Juan Pérez', 'juan.perez@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'supervisor', 0, 1),
('María García', 'maria.garcia@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'proceso', 0, 1),
('Carlos López', 'carlos.lopez@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 'usuario', 1, 1),
('Ana Martínez', 'ana.martinez@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 'usuario', 0, 1);

-- Insertar configuración inicial del sistema
INSERT INTO configuracion_sistema (nombre_sistema, tiempo_maximo_proceso, email_notificaciones) VALUES 
('OptiCAP - Sistema de Abastecimiento', 30, 'notificaciones@opticap.com');

-- Insertar algunos requerimientos de ejemplo
INSERT INTO requerimientos (codigo, titulo, descripcion, id_area_solicitante, id_usuario_solicitante, fecha_limite_total, estado) VALUES 
('REQ20240001', 'Adquisición de equipos de computo', 'Compra de 10 laptops para el área de ventas con especificaciones técnicas específicas', 3, 3, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'en_proceso'),
('REQ20240002', 'Servicio de mantenimiento de aire acondicionado', 'Contratación de servicio de mantenimiento preventivo para sistemas de aire acondicionado', 1, 2, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'pendiente'),
('REQ20240003', 'Compra de material de oficina', 'Adquisición de material de oficina para el primer trimestre del año', 1, 4, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'completado');

-- Insertar seguimiento para el requerimiento en proceso
INSERT INTO seguimiento_requerimientos (id_requerimiento, id_actividad, fecha_inicio_estimada, fecha_fin_estimada, estado) VALUES 
(1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'completado'),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'completado'),
(1, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'completado'),
(1, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'en_proceso'),
(1, 5, DATE_ADD(CURDATE(), INTERVAL 6 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), 'pendiente');

-- Insertar permisos de ejemplo
INSERT INTO permisos_usuario (id_usuario, id_actividad, puede_aprobar, puede_modificar) VALUES 
(3, 4, 1, 1),  -- María puede aprobar y modificar aprobación presupuestaria
(3, 7, 1, 1),  -- María puede aprobar y modificar evaluación técnica
(2, 10, 1, 0), -- Juan puede aprobar (pero no modificar) firma y formalización
(2, 13, 1, 1); -- Juan puede aprobar y modificar pago y liquidación

-- Insertar logs del sistema iniciales
INSERT INTO logs_sistema (id_usuario, accion, descripcion, tabla_afectada, id_registro_afectado, ip) VALUES 
(1, 'SISTEMA_INICIALIZADO', 'Instalación del sistema completada', 'sistema', 1, '127.0.0.1'),
(1, 'USUARIO_CREADO', 'Usuario administrador creado', 'usuarios', 1, '127.0.0.1'),
(1, 'CONFIGURACION_INICIAL', 'Configuración del sistema establecida', 'configuracion_sistema', 1, '127.0.0.1');

-- Crear tabla para intentos de login (si no existe)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100) NOT NULL,
    attempt_time INT NOT NULL,
    user_agent TEXT,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_username_time (username, attempt_time)
);

-- Crear tabla para cache de reportes (si no existe)
CREATE TABLE IF NOT EXISTS report_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    cache_data LONGTEXT NOT NULL,
    expiration_time INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expiration (expiration_time)
);

-- Insertar datos de ejemplo para reportes
INSERT INTO report_cache (cache_key, cache_data, expiration_time) VALUES 
('sla_report_global', '{"total":150,"dentro_sla":130,"fuera_sla":20,"porcentaje":86.67}', UNIX_TIMESTAMP() + 3600),
('user_activity_month', '{"active_users":25,"total_actions":1245,"avg_actions":49.8}', UNIX_TIMESTAMP() + 1800);

-- Crear índices adicionales para optimización
CREATE INDEX idx_requerimientos_area ON requerimientos(id_area_solicitante);
CREATE INDEX idx_requerimientos_estado ON requerimientos(estado);
CREATE INDEX idx_requerimientos_fecha ON requerimientos(fecha_creacion);
CREATE INDEX idx_seguimiento_requerimiento ON seguimiento_requerimientos(id_requerimiento);
CREATE INDEX idx_seguimiento_estado ON seguimiento_requerimientos(estado);
CREATE INDEX idx_seguimiento_fecha ON seguimiento_requerimientos(fecha_fin_estimada);
CREATE INDEX idx_usuarios_area ON usuarios(id_area);
CREATE INDEX idx_usuarios_activo ON usuarios(activo);

-- Procedimiento almacenado para limpieza automática
DELIMITER //
CREATE PROCEDURE limpiar_datos_temporales()
BEGIN
    -- Limpiar intentos de login antiguos (más de 24 horas)
    DELETE FROM login_attempts WHERE attempt_time < UNIX_TIMESTAMP() - 86400;
    
    -- Limpiar cache expirado
    DELETE FROM report_cache WHERE expiration_time < UNIX_TIMESTAMP();
    
    -- Limpiar logs muy antiguos (conservar solo 6 meses)
    DELETE FROM logs_sistema WHERE fecha < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END//
DELIMITER ;

-- Evento para ejecutar limpieza automática diaria
DELIMITER //
CREATE EVENT IF NOT EXISTS limpieza_automatica
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL limpiar_datos_temporales();
END//
DELIMITER ;

-- Habilitar el planificador de eventos
SET GLOBAL event_scheduler = ON;

-- Vista para reportes consolidados
CREATE VIEW vista_reportes_consolidados AS
SELECT 
    r.id,
    r.codigo,
    r.titulo,
    a.nombre as area_solicitante,
    u.nombre as usuario_solicitante,
    r.fecha_creacion,
    r.fecha_limite_total,
    r.estado as estado_requerimiento,
    COUNT(sr.id) as total_actividades,
    SUM(CASE WHEN sr.estado = 'completado' THEN 1 ELSE 0 END) as actividades_completadas,
    SUM(CASE WHEN sr.estado = 'atrasado' THEN 1 ELSE 0 END) as actividades_atrasadas,
    DATEDIFF(r.fecha_limite_total, CURDATE()) as dias_restantes,
    CASE 
        WHEN r.estado = 'completado' AND r.fecha_limite_total >= r.fecha_actualizacion THEN 'DENTRO_SLA'
        WHEN r.estado = 'completado' AND r.fecha_limite_total < r.fecha_actualizacion THEN 'FUERA_SLA'
        WHEN r.estado = 'en_proceso' AND r.fecha_limite_total < CURDATE() THEN 'EN_RIESGO'
        ELSE 'EN_PROCESO'
    END as estado_sla
FROM requerimientos r
LEFT JOIN areas a ON r.id_area_solicitante = a.id
LEFT JOIN usuarios u ON r.id_usuario_solicitante = u.id
LEFT JOIN seguimiento_requerimientos sr ON r.id = sr.id_requerimiento
GROUP BY r.id, r.codigo, r.titulo, a.nombre, u.nombre, r.fecha_creacion, r.fecha_limite_total, r.estado;

-- Comentarios sobre las tablas (para documentación)
ALTER TABLE areas COMMENT = 'Áreas u oficinas de la organización';
ALTER TABLE usuarios COMMENT = 'Usuarios del sistema con sus roles y permisos';
ALTER TABLE actividades COMMENT = 'Actividades del proceso de abastecimiento';
ALTER TABLE requerimientos COMMENT = 'Requerimientos de bienes o servicios';
ALTER TABLE seguimiento_requerimientos COMMENT = 'Seguimiento de actividades por requerimiento';
ALTER TABLE permisos_usuario COMMENT = 'Permisos específicos por usuario y actividad';
ALTER TABLE configuracion_sistema COMMENT = 'Configuración general del sistema';
ALTER TABLE logs_sistema COMMENT = 'Registro de actividades del sistema';