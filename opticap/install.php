<?php
session_start();
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/install.php', '', $_SERVER['PHP_SELF']) . '/');
define('ROOT_PATH', realpath(dirname(__FILE__)) . '/');

if (file_exists('app/config/config.php')) {
    die('El sistema ya está instalado. Elimina install.php para mayor seguridad.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'opticap_db';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $app_url = $_POST['app_url'] ?? BASE_URL;
    
    // Validar conexión a la base de datos
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
        $pdo->exec("USE $db_name");
        
        // Ejecutar script SQL
        $sql = file_get_contents('sql/database.sql');
        $pdo->exec($sql);
        
        // Crear archivo de configuración
        $configContent = "<?php
class Config {
    const DB_HOST = '$db_host';
    const DB_NAME = '$db_name';
    const DB_USER = '$db_user';
    const DB_PASS = '$db_pass';
    const DB_CHARSET = 'utf8mb4';
    
    const APP_NAME = 'OptiCAP';
    const APP_VERSION = '1.0';
    const APP_URL = '$app_url';
    
    const SESSION_TIMEOUT = 3600;
    const PASSWORD_RESET_TIMEOUT = 1800;
    
    const MAX_FILE_SIZE = 10485760;
    const ALLOWED_FILE_TYPES = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USER = 'notificaciones@opticap.com';
    const SMTP_PASS = 'password';
    const SMTP_FROM = 'notificaciones@opticap.com';
}
?>";

        file_put_contents('app/config/config.php', $configContent);
        
        // Crear directorios necesarios
        $dirs = [
            'assets/uploads/evidencias',
            'assets/uploads/documentos',
            'assets/uploads/logos',
            'logs'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        $_SESSION['success'] = 'Instalación completada correctamente. Elimina install.php para mayor seguridad.';
        header('Location: ' . BASE_URL . 'auth/login');
        exit();
        
    } catch (PDOException $e) {
        $error = "Error de conexión a la base de datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - OptiCAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h2><i class="fas fa-boxes me-2"></i>Instalación de OptiCAP</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <h4 class="mb-3">Configuración de Base de Datos</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Servidor de BD</label>
                                    <input type="text" class="form-control" name="db_host" value="localhost" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre de BD</label>
                                    <input type="text" class="form-control" name="db_name" value="opticap_db" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Usuario de BD</label>
                                    <input type="text" class="form-control" name="db_user" value="root" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña de BD</label>
                                    <input type="password" class="form-control" name="db_pass">
                                </div>
                            </div>
                            
                            <h4 class="mb-3 mt-4">Configuración de la Aplicación</h4>
                            <div class="mb-3">
                                <label class="form-label">URL de la Aplicación</label>
                                <input type="url" class="form-control" name="app_url" value="<?php echo BASE_URL; ?>" required>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>Nota:</strong> Se creará un usuario administrador por defecto:<br>
                                Email: admin@opticap.com<br>
                                Contraseña: Admin123
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Instalar Sistema</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>