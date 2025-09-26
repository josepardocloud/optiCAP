-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS opticap_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE opticap_db;

-- Tabla de áreas/oficinas
CREATE TABLE areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    id_area INT,
    rol ENUM('admin', 'supervisor', 'proceso', 'usuario') DEFAULT 'usuario',
    activo BOOLEAN DEFAULT TRUE,
    primer_login BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_area) REFERENCES areas(id) ON DELETE SET NULL
);

-- Tabla de actividades del proceso
CREATE TABLE actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tiempo_limite INT NOT NULL, -- en días
    orden INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de requerimientos
CREATE TABLE requerimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    id_area_solicitante INT NOT NULL,
    id_usuario_solicitante INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_limite_total DATE,
    estado ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
    FOREIGN KEY (id_area_solicitante) REFERENCES areas(id),
    FOREIGN KEY (id_usuario_solicitante) REFERENCES usuarios(id)
);

-- Tabla de seguimiento de actividades
CREATE TABLE seguimiento_requerimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_requerimiento INT NOT NULL,
    id_actividad INT NOT NULL,
    id_usuario_asignado INT,
    fecha_inicio_estimada DATE,
    fecha_fin_estimada DATE,
    fecha_inicio_real DATE,
    fecha_fin_real DATE,
    estado ENUM('pendiente', 'en_proceso', 'completado', 'atrasado') DEFAULT 'pendiente',
    evidencias TEXT,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_requerimiento) REFERENCES requerimientos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_actividad) REFERENCES actividades(id),
    FOREIGN KEY (id_usuario_asignado) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de permisos de usuario por actividad
CREATE TABLE permisos_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_actividad INT NOT NULL,
    puede_aprobar BOOLEAN DEFAULT FALSE,
    puede_modificar BOOLEAN DEFAULT FALSE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_actividad) REFERENCES actividades(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_actividad (id_usuario, id_actividad)
);

-- Tabla de configuración del sistema
CREATE TABLE configuracion_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_sistema VARCHAR(100) DEFAULT 'OptiCAP',
    logo VARCHAR(255),
    tiempo_maximo_proceso INT DEFAULT 30, -- días
    email_notificaciones VARCHAR(150),
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de logs del sistema
CREATE TABLE logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tabla_afectada VARCHAR(50),
    id_registro_afectado INT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Datos iniciales
INSERT INTO areas (nombre, descripcion) VALUES 
('Administración', 'Área de administración general'),
('Finanzas', 'Departamento de finanzas y contabilidad'),
('Compras', 'Área de adquisiciones y compras'),
('Logística', 'Departamento de logística y distribución');

INSERT INTO actividades (nombre, descripcion, tiempo_limite, orden) VALUES 
('Generación de Requerimiento', 'Creación y formalización del requerimiento', 2, 1),
('Aprobación Presupuestaria', 'Revisión y aprobación del presupuesto', 3, 2),
('Cotización', 'Obtención de cotizaciones de proveedores', 5, 3),
('Evaluación Técnica', 'Análisis técnico de las propuestas', 4, 4),
('Adjudicación', 'Selección y adjudicación al proveedor', 2, 5),
('Contratación', 'Firmas de contratos y documentos', 3, 6),
('Seguimiento y Recepción', 'Control de entrega y recepción', 7, 7);

INSERT INTO configuracion_sistema (nombre_sistema, tiempo_maximo_proceso, email_notificaciones) VALUES 
('OptiCAP', 30, 'notificaciones@opticap.com');

-- Usuario administrador por defecto (password: Admin123)
INSERT INTO usuarios (nombre, email, password, id_area, rol, primer_login) VALUES 
('Administrador', 'admin@opticap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'admin', FALSE);