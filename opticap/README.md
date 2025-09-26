# OptiCAP - Sistema de Gestión de Cadena de Abastecimiento

## Descripción

OptiCAP es un sistema web completo desarrollado en PHP y MySQL para el seguimiento y gestión del proceso de cadena pública de abastecimiento. Permite realizar un control eficiente de requerimientos, actividades, tiempos y evidencias throughout todo el ciclo de vida del proceso.

## Características Principales

### 🔐 Gestión de Usuarios y Roles
- Sistema de autenticación seguro
- Roles jerárquicos (Admin, Supervisor, Proceso, Usuario)
- Permisos granulares por actividad
- Cambio obligatorio de contraseña en primer login

### 📋 Gestión de Requerimientos
- Creación y seguimiento de requerimientos
- Códigos únicos automáticos
- Workflow configurable de actividades
- Control de tiempos y SLA

### 📊 Dashboard y Reportes
- Métricas en tiempo real
- Indicadores de desempeño (KPI)
- Reportes de SLA y eficiencia
- Gráficos interactivos

### ⚙️ Configuración Flexible
- Gestión de áreas y oficinas
- Configuración de actividades del proceso
- Personalización de tiempos límite
- Sistema de notificaciones

### 🔒 Seguridad
- Protección contra SQL Injection
- Validación de datos robusta
- Control de sesiones seguro
- Hash de contraseñas bcrypt

## Requisitos del Sistema

### Servidor Web
- Apache 2.4+ con mod_rewrite
- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB 10.3+

### Extensiones PHP Requeridas
- PDO MySQL
- JSON
- cURL
- GD (para manipulación de imágenes)
- OpenSSL

### Recomendado
- SSL/TLS para producción
- Backup automático
- Monitoreo de rendimiento

## Instalación

### 1. Descargar el Sistema
```bash
git clone https://github.com/tu-repositorio/opticap.git
cd opticap