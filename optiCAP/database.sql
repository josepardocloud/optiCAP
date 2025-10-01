-- Crear base de datos
CREATE DATABASE IF NOT EXISTS opticap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE opticap;

-- Tabla de áreas/oficinas
CREATE TABLE areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'supervisor', 'super_usuario', 'usuario') NOT NULL,
    area_id INT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado BOOLEAN DEFAULT FALSE,
    fecha_bloqueo TIMESTAMP NULL,
    FOREIGN KEY (area_id) REFERENCES areas(id)
);

-- Tabla de procesos
CREATE TABLE procesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    tipo ENUM('Bien', 'Servicio') NOT NULL,
    tiempo_total_dias INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    sla_objetivo INT DEFAULT 30,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de actividades
CREATE TABLE actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    proceso_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    orden INT NOT NULL,
    tiempo_dias INT NOT NULL,
    actividad_anterior_id INT NULL,
    sla_objetivo INT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id),
    FOREIGN KEY (actividad_anterior_id) REFERENCES actividades(id)
);

-- Tabla de requerimientos
CREATE TABLE requerimientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    proceso_id INT NOT NULL,
    area_id INT NOT NULL,
    usuario_solicitante_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
    observaciones TEXT,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id),
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id)
);

-- Tabla de seguimiento de actividades
CREATE TABLE seguimiento_actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requerimiento_id INT NOT NULL,
    actividad_id INT NOT NULL,
    usuario_id INT,
    estado ENUM('pendiente', 'en_proceso', 'completado') DEFAULT 'pendiente',
    fecha_inicio TIMESTAMP NULL,
    fecha_fin TIMESTAMP NULL,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos(id),
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de incidencias
CREATE TABLE incidencias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requerimiento_id INT NOT NULL,
    usuario_reporta_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    evidencia_url VARCHAR(500),
    estado ENUM('reportada', 'en_revision', 'resuelta') DEFAULT 'reportada',
    fecha_reporte TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL,
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos(id),
    FOREIGN KEY (usuario_reporta_id) REFERENCES usuarios(id)
);

-- Tabla para permisos granulares
CREATE TABLE permisos_actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    proceso_id INT NOT NULL,
    actividad_id INT NOT NULL,
    permiso_modificar BOOLEAN DEFAULT FALSE,
    permiso_ver BOOLEAN DEFAULT TRUE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_asignador_id INT,
    fecha_expiracion DATE NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id),
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    FOREIGN KEY (usuario_asignador_id) REFERENCES usuarios(id)
);

-- Tabla de solicitudes de permisos
CREATE TABLE solicitudes_permisos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_solicitante_id INT NOT NULL,
    proceso_id INT NOT NULL,
    actividad_id INT NOT NULL,
    justificacion TEXT NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_resolutor_id INT,
    fecha_resolucion TIMESTAMP NULL,
    observaciones_resolucion TEXT,
    FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id),
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    FOREIGN KEY (usuario_resolutor_id) REFERENCES usuarios(id)
);

-- Tabla de configuraciones del sistema
CREATE TABLE configuraciones_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_sistema VARCHAR(255) DEFAULT 'OptiCAP',
    logo_url VARCHAR(500),
    smtp_host VARCHAR(255),
    smtp_port INT,
    smtp_user VARCHAR(255),
    smtp_pass VARCHAR(255),
    from_email VARCHAR(255),
    from_name VARCHAR(255),
    email_activo BOOLEAN DEFAULT FALSE,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de plantillas de email
CREATE TABLE plantillas_email (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(100) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    variables TEXT,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de notificaciones pendientes
CREATE TABLE notificaciones_pendientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de logs de seguridad
CREATE TABLE logs_seguridad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    ip VARCHAR(45) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    resultado ENUM('exito', 'fallo') NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Insertar datos iniciales
INSERT INTO areas (nombre, descripcion) VALUES 
('Administración', 'Área de administración general'),
('Logística', 'Área de logística y compras'),
('TI', 'Área de tecnologías de la información'),
('Finanzas', 'Área de finanzas y presupuesto'),
('Recursos Humanos', 'Área de recursos humanos');

-- Insertar usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol, area_id) VALUES 
('Administrador', 'admin@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 1),
('Supervisor General', 'supervisor@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor', 1),
('Usuario Ejemplo', 'usuario@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', 2);

-- Insertar procesos base
INSERT INTO procesos (nombre, tipo, tiempo_total_dias, sla_objetivo) VALUES 
('Adquisición de Bienes', 'Bien', 45, 30),
('Adquisición de Servicios', 'Servicio', 40, 30);

-- Insertar actividades para proceso de Bienes
INSERT INTO actividades (proceso_id, nombre, descripcion, orden, tiempo_dias, actividad_anterior_id) VALUES 
(1, 'Verificación de Existencia del Bien', 'Verificar existencia en cuadro multianual de necesidades', 1, 2, NULL),
(1, 'Genera Solicitud de Modificación', 'Generar solicitud de modificación del cuadro multianual', 2, 3, 1),
(1, 'Consolidación y aprobación', 'Consolidar y aprobar solicitud de modificación', 3, 5, 2),
(1, 'Aprobación de modificación', 'Aprobar modificación del cuadro multianual', 4, 3, 3),
(1, 'Generación del Pedido de Bienes', 'Generar pedido adjuntando especificaciones técnicas', 5, 2, 4),
(1, 'Generación del CNM Actualizado', 'Generar CNM actualizado solicitando PCA y priorización', 6, 4, 5),
(1, 'Certificación Presupuestal', 'Realizar certificación presupuestal', 7, 3, 6),
(1, 'Generación de la Orden de Compra', 'Generar orden de compra', 8, 2, 7),
(1, 'Generación del Compromiso Anual', 'Generar compromiso anual', 9, 2, 8),
(1, 'Generación del Compromiso Mensual', 'Generar compromiso mensual', 10, 2, 9),
(1, 'Notificación al Proveedor', 'Notificar al proveedor seleccionado', 11, 1, 10),
(1, 'Recepción y conformidad de Bienes', 'Recibir y verificar conformidad de bienes en almacén', 12, 5, 11),
(1, 'Generación de PECOSAS', 'Generar PECOSAS y entrega al área usuaria', 13, 2, 12),
(1, 'Generación de Devengado', 'Generar devengado con documentación para contabilidad', 14, 3, 13);

-- Insertar actividades para proceso de Servicios
INSERT INTO actividades (proceso_id, nombre, descripcion, orden, tiempo_dias, actividad_anterior_id) VALUES 
(2, 'Verificación de Existencia del Servicio', 'Verificar existencia en cuadro multianual de necesidades', 1, 2, NULL),
(2, 'Genera Solicitud de Modificación', 'Generar solicitud de modificación del cuadro multianual', 2, 3, 15),
(2, 'Consolidación y aprobación', 'Consolidar y aprobar solicitud de modificación', 3, 5, 16),
(2, 'Aprobación de modificación', 'Aprobar modificación del cuadro multianual', 4, 3, 17),
(2, 'Generación del Pedido de Servicios', 'Generar pedido adjuntando términos de referencia', 5, 2, 18),
(2, 'Generación del CNM Actualizado', 'Generar CNM actualizado solicitando PCA y priorización', 6, 4, 19),
(2, 'Certificación Presupuestal', 'Realizar certificación presupuestal', 7, 3, 20),
(2, 'Generación de la Orden de Servicio', 'Generar orden de servicio', 8, 2, 21),
(2, 'Generación del Compromiso Anual', 'Generar compromiso anual', 9, 2, 22),
(2, 'Generación del Compromiso Mensual', 'Generar compromiso mensual', 10, 2, 23),
(2, 'Notificación al Proveedor', 'Notificar al proveedor seleccionado', 11, 1, 24),
(2, 'Atención del Servicio', 'Atender servicio al área usuaria', 12, 10, 25),
(2, 'Conformidad del Servicio', 'Generar conformidad del servicio del área usuaria', 13, 2, 26),
(2, 'Generación de Devengado', 'Generar devengado con documentación para contabilidad', 14, 3, 27);

-- Insertar configuración inicial del sistema
INSERT INTO configuraciones_sistema (nombre_sistema, from_email, from_name) VALUES 
('OptiCAP', 'sistema@opticap.com', 'Sistema OptiCAP');

-- Insertar plantillas de email básicas
INSERT INTO plantillas_email (tipo, asunto, contenido, variables) VALUES 
('nuevo_requerimiento', 'Nuevo Requerimiento Creado - {codigo}', '<p>Se ha creado un nuevo requerimiento:</p><p><strong>Código:</strong> {codigo}</p><p><strong>Tipo:</strong> {tipo}</p><p><strong>Área:</strong> {area}</p><p>Por favor, revise el sistema para más detalles.</p>', 'codigo,tipo,area'),
('actividad_asignada', 'Actividad Asignada - {actividad}', '<p>Se le ha asignado una nueva actividad:</p><p><strong>Actividad:</strong> {actividad}</p><p><strong>Requerimiento:</strong> {codigo}</p><p><strong>Fecha límite:</strong> {fecha_limite}</p>', 'actividad,codigo,fecha_limite'),
('bloqueo_cuenta', 'Cuenta Bloqueada - {usuario}', '<p>La cuenta del usuario <strong>{usuario}</strong> ha sido bloqueada por intentos fallidos de login.</p><p><strong>Fecha:</strong> {fecha}</p><p><strong>IP:</strong> {ip}</p>', 'usuario,fecha,ip');