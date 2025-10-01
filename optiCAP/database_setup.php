<?php
/**
 * Script de instalación de la base de datos OptiCAP
 * Ejecutar una sola vez para crear la base de datos y tablas
 */

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'opticap_db';
$username = 'root';
$password = '';

try {
    // Conectar sin seleccionar base de datos primero
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");

    echo "Base de datos creada/existe correctamente.<br>";

    // Tabla de áreas/oficinas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS areas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT,
            activo BOOLEAN DEFAULT TRUE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Tabla 'areas' creada correctamente.<br>";

    // Tabla de usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
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
        )
    ");
    echo "Tabla 'usuarios' creada correctamente.<br>";

    // Tabla de procesos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS procesos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(255) NOT NULL,
            tipo ENUM('Bien', 'Servicio') NOT NULL,
            tiempo_total_dias INT NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            sla_objetivo INT DEFAULT 30,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Tabla 'procesos' creada correctamente.<br>";

    // Tabla de actividades
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS actividades (
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
        )
    ");
    echo "Tabla 'actividades' creada correctamente.<br>";

    // Tabla de requerimientos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requerimientos (
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
        )
    ");
    echo "Tabla 'requerimientos' creada correctamente.<br>";

    // Tabla de seguimiento de actividades - CORREGIDA
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS seguimiento_actividades (
            id INT PRIMARY KEY AUTO_INCREMENT,
            requerimiento_id INT NOT NULL,
            actividad_id INT NOT NULL,
            usuario_id INT,
            estado ENUM('pendiente', 'en_proceso', 'completado') DEFAULT 'pendiente',
            fecha_inicio TIMESTAMP NULL,
            fecha_fin TIMESTAMP NULL,
            observaciones TEXT,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (requerimiento_id) REFERENCES requerimientos(id) ON DELETE CASCADE,
            FOREIGN KEY (actividad_id) REFERENCES actividades(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");
    echo "Tabla 'seguimiento_actividades' creada correctamente.<br>";

    // Tabla de incidencias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS incidencias (
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
        )
    ");
    echo "Tabla 'incidencias' creada correctamente.<br>";

    // Tabla para permisos granulares
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permisos_actividades (
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
        )
    ");
    echo "Tabla 'permisos_actividades' creada correctamente.<br>";

    // Tabla de solicitudes de permisos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS solicitudes_permisos (
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
        )
    ");
    echo "Tabla 'solicitudes_permisos' creada correctamente.<br>";

    // Tabla de configuraciones del sistema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuraciones_sistema (
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
        )
    ");
    echo "Tabla 'configuraciones_sistema' creada correctamente.<br>";

    // Tabla de plantillas de email
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plantillas_email (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tipo VARCHAR(100) NOT NULL,
            asunto VARCHAR(255) NOT NULL,
            contenido TEXT NOT NULL,
            variables TEXT,
            activo BOOLEAN DEFAULT TRUE
        )
    ");
    echo "Tabla 'plantillas_email' creada correctamente.<br>";

    // Tabla de notificaciones pendientes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notificaciones_pendientes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            tipo VARCHAR(100) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            contenido TEXT NOT NULL,
            leida BOOLEAN DEFAULT FALSE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");
    echo "Tabla 'notificaciones_pendientes' creada correctamente.<br>";

    // Tabla de logs de seguridad
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs_seguridad (
            id INT PRIMARY KEY AUTO_INCREMENT,
            usuario_id INT,
            ip VARCHAR(45) NOT NULL,
            accion VARCHAR(100) NOT NULL,
            resultado ENUM('exito', 'fallo') NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            detalles TEXT,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");
    echo "Tabla 'logs_seguridad' creada correctamente.<br>";

    // Insertar datos iniciales
    echo "<br>Insertando datos iniciales...<br>";

    // Insertar áreas
    $pdo->exec("INSERT INTO areas (nombre, descripcion) VALUES 
        ('Administración', 'Área de administración general'),
        ('Logística', 'Área de logística y compras'),
        ('TI', 'Área de tecnologías de la información'),
        ('Finanzas', 'Área de finanzas y presupuesto'),
        ('Recursos Humanos', 'Área de recursos humanos')");
    echo "Áreas insertadas.<br>";

    // Insertar usuario administrador por defecto (password: admin123)
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO usuarios (nombre, email, password, rol, area_id) VALUES 
        ('Administrador', 'admin@opticap.com', '$hashed_password', 'administrador', 1),
        ('Supervisor General', 'supervisor@opticap.com', '$hashed_password', 'supervisor', 1),
        ('Usuario Ejemplo', 'usuario@opticap.com', '$hashed_password', 'usuario', 2)");
    echo "Usuarios insertados.<br>";

    // Insertar procesos base
    $pdo->exec("INSERT INTO procesos (nombre, tipo, tiempo_total_dias, sla_objetivo) VALUES 
        ('Adquisición de Bienes', 'Bien', 45, 30),
        ('Adquisición de Servicios', 'Servicio', 40, 30)");
    echo "Procesos insertados.<br>";

    // Insertar actividades para proceso de Bienes
    $pdo->exec("INSERT INTO actividades (proceso_id, nombre, descripcion, orden, tiempo_dias, actividad_anterior_id) VALUES 
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
        (1, 'Generación de Devengado', 'Generar devengado con documentación para contabilidad', 14, 3, 13)");
    echo "Actividades de Bienes insertadas.<br>";

    // Insertar actividades para proceso de Servicios
    $pdo->exec("INSERT INTO actividades (proceso_id, nombre, descripcion, orden, tiempo_dias, actividad_anterior_id) VALUES 
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
        (2, 'Generación de Devengado', 'Generar devengado con documentación para contabilidad', 14, 3, 27)");
    echo "Actividades de Servicios insertadas.<br>";

    // Insertar configuración inicial del sistema
    $pdo->exec("INSERT INTO configuraciones_sistema (nombre_sistema, from_email, from_name) VALUES 
        ('OptiCAP', 'sistema@opticap.com', 'Sistema OptiCAP')");
    echo "Configuración del sistema insertada.<br>";

    // Insertar plantillas de email básicas
    $pdo->exec("INSERT INTO plantillas_email (tipo, asunto, contenido, variables) VALUES 
        ('nuevo_requerimiento', 'Nuevo Requerimiento Creado - {codigo}', '<p>Se ha creado un nuevo requerimiento:</p><p><strong>Código:</strong> {codigo}</p><p><strong>Tipo:</strong> {tipo}</p><p><strong>Área:</strong> {area}</p><p>Por favor, revise el sistema para más detalles.</p>', 'codigo,tipo,area'),
        ('actividad_asignada', 'Actividad Asignada - {actividad}', '<p>Se le ha asignado una nueva actividad:</p><p><strong>Actividad:</strong> {actividad}</p><p><strong>Requerimiento:</strong> {codigo}</p><p><strong>Fecha límite:</strong> {fecha_limite}</p>', 'actividad,codigo,fecha_limite'),
        ('bloqueo_cuenta', 'Cuenta Bloqueada - {usuario}', '<p>La cuenta del usuario <strong>{usuario}</strong> ha sido bloqueada por intentos fallidos de login.</p><p><strong>Fecha:</strong> {fecha}</p><p><strong>IP:</strong> {ip}</p>', 'usuario,fecha,ip')");
    echo "Plantillas de email insertadas.<br>";

    echo "<br><strong>¡Instalación completada exitosamente!</strong><br>";
    echo "Puedes acceder al sistema con:<br>";
    echo "Email: admin@opticap.com<br>";
    echo "Contraseña: admin123<br>";
    echo "<br><a href='login.php'>Ir al Login</a>";

} catch (PDOException $e) {
    die("Error en la instalación: " . $e->getMessage());
}
?>