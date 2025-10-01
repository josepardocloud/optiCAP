-- Base de datos OptiCAP2
CREATE DATABASE IF NOT EXISTS opticap2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE opticap2;

-- Tabla de roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de áreas/oficinas
CREATE TABLE areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(20) UNIQUE,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    area_id INT NOT NULL,
    estado ENUM('activo', 'inactivo', 'eliminado') DEFAULT 'activo',
    intentos_fallidos INT DEFAULT 0,
    ultimo_intento TIMESTAMP NULL,
    token_recuperacion VARCHAR(100) NULL,
    token_expiracion TIMESTAMP NULL,
    debe_cambiar_password BOOLEAN DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    eliminado_en TIMESTAMP NULL,
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    FOREIGN KEY (area_id) REFERENCES areas(id)
);

-- Tabla de tipos de proceso
CREATE TABLE tipos_proceso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de procesos
CREATE TABLE procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_proceso_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    duracion_estimada INT DEFAULT 0, -- en días
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tipo_proceso_id) REFERENCES tipos_proceso(id)
);

-- Tabla de actividades
CREATE TABLE actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    proceso_id INT NOT NULL,
    numero_paso INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    requisitos_obligatorios JSON,
    duracion_estimada INT DEFAULT 1, -- en días
    orden INT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (proceso_id, numero_paso),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id)
);

-- Tabla de requerimientos
CREATE TABLE requerimientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    tipo_proceso_id INT NOT NULL,
    area_id INT NOT NULL,
    usuario_solicitante_id INT NOT NULL,
    motivo TEXT NOT NULL,
    estado_general ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
    progreso DECIMAL(5,2) DEFAULT 0.00,
    fecha_salto_condicional TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_limite TIMESTAMP NULL,
    fecha_completado TIMESTAMP NULL,
    FOREIGN KEY (tipo_proceso_id) REFERENCES tipos_proceso(id),
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id)
);

-- Tabla de actividades del requerimiento
CREATE TABLE requerimiento_actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requerimiento_id INT NOT NULL,
    actividad_id INT NOT NULL,
    estado ENUM('pendiente', 'en_proceso', 'finalizado', 'rechazado', 'no_aplica') DEFAULT 'pendiente',
    usuario_asignado_id INT NULL,
    fecha_inicio TIMESTAMP NULL,
    fecha_fin TIMESTAMP NULL,
    fecha_limite TIMESTAMP NULL,
    observaciones TEXT,
    salto_condicional BOOLEAN DEFAULT 0,
    requisitos_cumplidos JSON,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos(id) ON DELETE CASCADE,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    FOREIGN KEY (usuario_asignado_id) REFERENCES usuarios(id)
);

-- Tabla de evidencias/documentos
CREATE TABLE evidencias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requerimiento_actividad_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL,
    tamaño INT NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    tipo_evidencia ENUM('especificaciones', 'terminos_referencia', 'pca_priorizacion', 'conformidad', 'documentacion', 'otro') NOT NULL,
    descripcion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requerimiento_actividad_id) REFERENCES requerimiento_actividades(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de permisos granulares
CREATE TABLE permisos_granulares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    proceso_id INT NOT NULL,
    actividad_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NULL,
    estado ENUM('activo', 'revocado', 'expirado') DEFAULT 'activo',
    revocado_en TIMESTAMP NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id),
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    UNIQUE KEY (usuario_id, proceso_id, actividad_id)
);

-- Tabla de incidencias
CREATE TABLE incidencias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    requerimiento_id INT NOT NULL,
    usuario_reportero_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    tipo ENUM('tecnica', 'funcional', 'seguridad', 'otro') DEFAULT 'funcional',
    prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
    estado ENUM('pendiente', 'en_proceso', 'resuelto') DEFAULT 'pendiente',
    fecha_reporte TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL,
    usuario_resolutor_id INT NULL,
    solucion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos(id),
    FOREIGN KEY (usuario_reportero_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_resolutor_id) REFERENCES usuarios(id)
);

-- Tabla de evidencias de incidencias
CREATE TABLE incidencia_evidencias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    incidencia_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL,
    tamaño INT NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE CASCADE
);

-- Tabla de auditoría de accesos
CREATE TABLE auditoria_accesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NULL,
    email VARCHAR(100) NULL,
    accion VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de auditoría de usuarios
CREATE TABLE auditoria_usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    datos_anteriores JSON,
    datos_nuevos JSON,
    usuario_auditoria INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_auditoria) REFERENCES usuarios(id)
);

-- Tabla de auditoría de requerimientos
CREATE TABLE auditoria_requerimientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requerimiento_id INT NOT NULL,
    actividad_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50),
    usuario_id INT NOT NULL,
    observaciones TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos(id),
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de configuración del sistema
CREATE TABLE configuracion_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    tipo VARCHAR(50) DEFAULT 'text',
    descripcion TEXT,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de notificaciones
CREATE TABLE notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    leida BOOLEAN DEFAULT 0,
    enlace VARCHAR(500) NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_leida TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Índices para mejor performance
CREATE INDEX idx_requerimientos_area ON requerimientos(area_id);
CREATE INDEX idx_requerimientos_estado ON requerimientos(estado_general);
CREATE INDEX idx_requerimientos_usuario ON requerimientos(usuario_solicitante_id);
CREATE INDEX idx_actividades_requerimiento ON requerimiento_actividades(requerimiento_id, actividad_id);
CREATE INDEX idx_actividades_estado ON requerimiento_actividades(estado);
CREATE INDEX idx_evidencias_actividad ON evidencias(requerimiento_actividad_id);
CREATE INDEX idx_incidencias_estado ON incidencias(estado);
CREATE INDEX idx_auditoria_fecha ON auditoria_accesos(fecha);
CREATE INDEX idx_notificaciones_usuario ON notificaciones(usuario_id, leida);

-- Datos iniciales
INSERT INTO roles (nombre, descripcion) VALUES 
('Administrador', 'Administrador del sistema con acceso completo a configuración'),
('Supervisor', 'Supervisor con acceso de solo lectura a todos los requerimientos'),
('Super Usuario', 'Usuario con permisos extendidos para gestionar requerimientos de todas las áreas'),
('Usuario', 'Usuario estándar con permisos limitados a su área');

INSERT INTO tipos_proceso (nombre, codigo, descripcion) VALUES 
('Bienes', 'BIEN', 'Proceso de adquisición de bienes con 14 actividades'),
('Servicios', 'SERV', 'Proceso de adquisición de servicios con 14 actividades');

INSERT INTO areas (nombre, codigo, descripcion) VALUES 
('Administración Central', 'ADM-CENT', 'Área de administración central'),
('Recursos Humanos', 'RRHH', 'Departamento de recursos humanos'),
('Tecnología de Información', 'TI', 'Departamento de sistemas y tecnología'),
('Finanzas', 'FINANZ', 'Departamento financiero'),
('Logística', 'LOGIS', 'Departamento de logística y compras');

-- Insertar procesos base
INSERT INTO procesos (tipo_proceso_id, nombre, descripcion, duracion_estimada) VALUES 
(1, 'Proceso de Adquisición de Bienes', 'Proceso estándar de adquisición de bienes con 14 actividades', 30),
(2, 'Proceso de Adquisición de Servicios', 'Proceso estándar de adquisición de servicios con 14 actividades', 30);

-- Insertar actividades para proceso de bienes (14 actividades)
INSERT INTO actividades (proceso_id, numero_paso, nombre, descripcion, requisitos_obligatorios, orden) VALUES 
(1, 1, 'Verificación de Existencia del Bien en el cuadro multianual de Necesidades', 'Verificar si el bien existe en el cuadro multianual de necesidades', '[]', 1),
(1, 2, 'Genera Solicitud de Modificación del cuadro multianual de Necesidades', 'Generar solicitud de modificación del cuadro multianual', '["disponibilidad_presupuestal"]', 2),
(1, 3, 'Consolidación y aprobación de Solicitud de modificación del cuadro multianual de necesidades', 'Consolidar y aprobar solicitud de modificación', '[]', 3),
(1, 4, 'Aprobación de modificación del cuadro multianual de necesidades', 'Aprobar modificación del cuadro multianual', '[]', 4),
(1, 5, 'Generación del Pedido de Bienes adjuntando Especificaciones técnicas', 'Generar pedido de bienes con especificaciones técnicas', '["especificaciones_tecnicas"]', 5),
(1, 6, 'Generación del CNM Actualizado', 'Generar CNM actualizado', '["pca_priorizacion"]', 6),
(1, 7, 'Certificación Presupuestal', 'Realizar certificación presupuestal', '[]', 7),
(1, 8, 'Generación de la Orden de Compra', 'Generar orden de compra', '[]', 8),
(1, 9, 'Generación del Compromiso Anual', 'Generar compromiso anual', '[]', 9),
(1, 10, 'Generación del Compromiso Mensual', 'Generar compromiso mensual', '[]', 10),
(1, 11, 'Notificación al Proveedor', 'Notificar al proveedor', '[]', 11),
(1, 12, 'Recepción y conformidad de Bienes (Almacén)', 'Recibir y verificar conformidad de bienes en almacén', '["verificacion_especificaciones"]', 12),
(1, 13, 'Generación de PECOSAS y Entrega al Área Usuaria', 'Generar PECOSAS y entregar al área usuaria', '[]', 13),
(1, 14, 'Generación de Devengado', 'Generar devengado', '["informe_conformidad", "documentacion_contable"]', 14);

-- Insertar actividades para proceso de servicios (14 actividades)
INSERT INTO actividades (proceso_id, numero_paso, nombre, descripcion, requisitos_obligatorios, orden) VALUES 
(2, 1, 'Verificación de Existencia del Servicio en el cuadro multianual de Necesidades', 'Verificar si el servicio existe en el cuadro multianual de necesidades', '[]', 1),
(2, 2, 'Genera Solicitud de Modificación del cuadro multianual de Necesidades', 'Generar solicitud de modificación del cuadro multianual', '["disponibilidad_presupuestal"]', 2),
(2, 3, 'Consolidación y aprobación de Solicitud de modificación del cuadro multianual de necesidades', 'Consolidar y aprobar solicitud de modificación', '[]', 3),
(2, 4, 'Aprobación de modificación del cuadro multianual de necesidades', 'Aprobar modificación del cuadro multianual', '[]', 4),
(2, 5, 'Generación del Pedido de Servicios adjuntando Términos de Referencia', 'Generar pedido de servicios con términos de referencia', '["terminos_referencia"]', 5),
(2, 6, 'Generación del CNM Actualizado', 'Generar CNM actualizado', '["pca_priorizacion"]', 6),
(2, 7, 'Certificación Presupuestal', 'Realizar certificación presupuestal', '[]', 7),
(2, 8, 'Generación de la Orden de Servicio', 'Generar orden de servicio', '[]', 8),
(2, 9, 'Generación del Compromiso Anual', 'Generar compromiso anual', '[]', 9),
(2, 10, 'Generación del Compromiso Mensual', 'Generar compromiso mensual', '[]', 10),
(2, 11, 'Notificación al Proveedor', 'Notificar al proveedor', '[]', 11),
(2, 12, 'Atención del Servicio al Área Usuaria', 'Atender servicio al área usuaria', '[]', 12),
(2, 13, 'Generación Conformidad del Servicio', 'Generar conformidad del servicio', '["conformidad_servicio"]', 13),
(2, 14, 'Generación de Devengado', 'Generar devengado', '["informe_conformidad", "documentacion_contable"]', 14);

-- Configuración inicial del sistema
INSERT INTO configuracion_sistema (clave, valor, tipo, descripcion) VALUES 
('nombre_sistema', 'OptiCAP2', 'text', 'Nombre del sistema'),
('logo_sistema', '/public/assets/img/logo.png', 'text', 'Ruta del logo del sistema'),
('smtp_host', 'smtp.gmail.com', 'text', 'Servidor SMTP para envío de emails'),
('smtp_port', '587', 'number', 'Puerto SMTP'),
('smtp_secure', 'tls', 'text', 'Tipo de seguridad SMTP'),
('dias_alerta_vencimiento', '3', 'number', 'Días de anticipación para alertas de vencimiento'),
('max_intentos_login', '4', 'number', 'Máximo número de intentos fallidos de login'),
('tiempo_bloqueo_minutos', '30', 'number', 'Tiempo de bloqueo de cuenta en minutos'),
('max_tamano_archivo_mb', '10', 'number', 'Tamaño máximo de archivos en MB');

-- Crear usuario administrador por defecto (password: Admin123)
INSERT INTO usuarios (nombre, email, password, rol_id, area_id) VALUES 
('Administrador Principal', 'admin@opticap2.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);