# OptiCAP - Sistema de Gestión de Procesos de Adquisición

Sistema web desarrollado en PHP y MySQL para la gestión y seguimiento de procesos de adquisición de bienes y servicios.

## Características Principales

### 🎯 Gestión de Procesos
- Procesos predefinidos para adquisición de bienes y servicios
- Control secuencial de actividades
- Generación automática de códigos únicos
- Línea de tiempo visual del progreso

### 👥 Gestión de Usuarios y Roles
- **Administrador**: Gestión completa del sistema
- **Supervisor**: Visualización global y reportes
- **Super Usuario**: Acción en múltiples áreas
- **Usuario**: Acción limitada a su área

### 🔐 Seguridad
- Control de intentos fallidos de login (máximo 4 intentos)
- Bloqueo automático de cuentas
- Permisos granulares por actividad
- Logs de auditoría

### 📊 Reportes y Exportación
- Exportación a PDF, Excel y CSV
- Dashboard con KPIs en tiempo real
- Gráficos y estadísticas
- Vista optimizada para impresión

### 🎨 Interfaz de Usuario
- Diseño responsive y moderno
- Colores claros y elegantes
- Navegación intuitiva
- Compatible con dispositivos móviles

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, MySQL, GD, mbstring

## Instalación

1. **Descargar o clonar el proyecto**
   ```bash
   git clone <repository-url>