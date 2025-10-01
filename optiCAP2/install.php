<?php
// install.php - Script de instalación
if (file_exists('config/config.php')) {
    die('El sistema ya está instalado. Elimine este archivo después de la instalación.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'opticap2';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    $site_name = $_POST['site_name'] ?? 'OptiCAP2';
    $site_url = $_POST['site_url'] ?? 'http://localhost/opticap2';
    
    try {
        // Probar conexión a la base de datos
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE $db_name");
        
        // Ejecutar script SQL
        $sql = file_get_contents('sql/database.sql');
        $pdo->exec($sql);
        
        // Crear archivo de configuración
        $configContent = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', '$site_name');
define('SITE_URL', '$site_url');
define('SITE_PATH', dirname(dirname(__FILE__)));

define('SESSION_TIMEOUT', 3600);
define('MAX_FILE_SIZE', 10485760);
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_PORT', 587);

define('ROL_SUPERUSUARIO', 1);
define('ROL_ADMINISTRADOR', 2);
define('ROL_SUPERVISOR', 3);
define('ROL_USUARIO', 4);

define('ESTADO_PENDIENTE', 1);
define('ESTADO_EN_PROGRESO', 2);
define('ESTADO_COMPLETADO', 3);
define('ESTADO_CANCELADO', 4);
?>";
        
        file_put_contents('config/config.php', $configContent);
        
        // Crear directorios necesarios
        $directories = [
            'public/uploads/evidencias',
            'public/uploads/incidencias',
            'public/uploads/temp',
            'logs'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        $success = "Sistema instalado correctamente. Ahora puede <a href='$site_url/login'>acceder al sistema</a>.";
        
    } catch (Exception $e) {
        $error = "Error durante la instalación: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - OptiCAP2</title>
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/fonts/fontawesome/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-container { max-width: 800px; margin: 50px auto; background: white; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="install-container p-5">
        <div class="text-center mb-5">
            <h1 class="h3 text-primary">
                <i class="fas fa-cogs me-2"></i>Instalación OptiCAP2
            </h1>
            <p class="text-muted">Complete la información para instalar el sistema</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
            </div>
            <div class="text-center mt-4">
                <a href="<?php echo $site_url; ?>/login" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket me-2"></i>Iniciar Sistema
                </a>
            </div>
        <?php else: ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3 text-primary">
                        <i class="fas fa-database me-2"></i>Base de Datos
                    </h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Servidor</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Base de Datos</label>
                        <input type="text" name="db_name" class="form-control" value="opticap2" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="db_user" class="form-control" value="root" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="db_pass" class="form-control">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5 class="mb-3 text-primary">
                        <i class="fas fa-cog me-2"></i>Configuración del Sistema
                    </h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre del Sistema</label>
                        <input type="text" name="site_name" class="form-control" value="OptiCAP2" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">URL del Sistema</label>
                        <input type="url" name="site_url" class="form-control" value="http://localhost/opticap2" required>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Información Importante
                        </h6>
                        <ul class="mb-0 small">
                            <li>Se creará un usuario administrador por defecto (admin@opticap2.com / Admin123)</li>
                            <li>La configuración de email se puede ajustar después de la instalación</li>
                            <li>Se recomienda cambiar las contraseñas por defecto después de la instalación</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-play me-2"></i>Instalar Sistema
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>