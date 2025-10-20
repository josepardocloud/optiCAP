<?php
require_once '../../config/session.php';
require_once '../../includes/funciones.php';
verificarSesion();
verificarRol(['administrador']);

$database = new Database();
$db = $database->getConnection();

// Obtener configuración actual
$query = "SELECT * FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$mensaje = '';
$error = '';

if ($_POST) {
    try {
        $nombre_sistema = $_POST['nombre_sistema'];
        $from_email = $_POST['from_email'];
        $from_name = $_POST['from_name'];
        
        // Manejar upload de logo
        $logo_url = $config['logo_url'] ?? null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/uploads/logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $new_filename = 'logo_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    // Eliminar logo anterior si existe
                    if ($logo_url && file_exists($upload_dir . $logo_url)) {
                        unlink($upload_dir . $logo_url);
                    }
                    $logo_url = $new_filename;
                } else {
                    throw new Exception("Error al subir el archivo.");
                }
            } else {
                throw new Exception("Formato de archivo no permitido. Use PNG, JPG, GIF o SVG.");
            }
        }
        
        if ($config) {
            // Actualizar configuración existente
            $query = "UPDATE configuraciones_sistema SET 
                     nombre_sistema = ?, logo_url = ?, from_email = ?, from_name = ?, 
                     fecha_actualizacion = NOW() 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$nombre_sistema, $logo_url, $from_email, $from_name, $config['id']]);
        } else {
            // Insertar nueva configuración
            $query = "INSERT INTO configuraciones_sistema (nombre_sistema, logo_url, from_email, from_name) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$nombre_sistema, $logo_url, $from_email, $from_name]);
        }
        
        $mensaje = "Configuración del sistema actualizada exitosamente";
        
    } catch (Exception $e) {
        $error = "Error al actualizar la configuración: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - OptiCAP</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configuración del Sistema</h1>
                    <div class="btn-group">
                        <a href="email.php" class="btn btn-outline-primary">Configuración Email</a>
                        <a href="seguridad.php" class="btn btn-outline-info">Seguridad</a>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Configuración General</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="nombre_sistema" class="form-label">Nombre del Sistema</label>
                                        <input type="text" class="form-control" id="nombre_sistema" name="nombre_sistema" 
                                               value="<?php echo htmlspecialchars($config['nombre_sistema'] ?? 'OptiCAP'); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Logo del Sistema</label>
                                        <input type="file" class="form-control" id="logo" name="logo" 
                                               accept=".png,.jpg,.jpeg,.gif,.svg">
                                        <div class="form-text">
                                            Formatos permitidos: PNG, JPG, GIF, SVG. Tamaño máximo: 2MB.
                                        </div>
                                        <?php if ($config && $config['logo_url']): ?>
                                        <div class="mt-2">
                                            <strong>Logo actual:</strong><br>
                                            <img src="/opticap/assets/uploads/logos/<?php echo $config['logo_url']; ?>" 
                                                 alt="Logo actual" height="50" class="mt-2 border rounded p-1">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="from_email" class="form-label">Email del Sistema</label>
                                                <input type="email" class="form-control" id="from_email" name="from_email" 
                                                       value="<?php echo htmlspecialchars($config['from_email'] ?? 'sistema@opticap.com'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="from_name" class="form-label">Nombre del Remitente</label>
                                                <input type="text" class="form-control" id="from_name" name="from_name" 
                                                       value="<?php echo htmlspecialchars($config['from_name'] ?? 'Sistema OptiCAP'); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Guardar Configuración
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Vista Previa</h5>
                            </div>
                            <div class="card-body text-center">
                                <?php if ($config && $config['logo_url']): ?>
                                <img src="/opticap/assets/uploads/logos/<?php echo $config['logo_url']; ?>" 
                                     alt="Logo preview" height="60" class="mb-3">
                                <?php else: ?>
                                <div class="bg-light rounded p-4 mb-3">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                    <p class="mt-2 mb-0 text-muted">Logo no configurado</p>
                                </div>
                                <?php endif; ?>
                                
                                <h4 id="previewNombre"><?php echo htmlspecialchars($config['nombre_sistema'] ?? 'OptiCAP'); ?></h4>
                                <p class="text-muted">Sistema de Gestión de Procesos de Adquisición</p>
                                
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6>Como se verá en el login:</h6>
                                    <div class="border rounded p-3 mt-2 bg-white">
                                        <h5 class="text-primary" id="previewLoginNombre"><?php echo htmlspecialchars($config['nombre_sistema'] ?? 'OptiCAP'); ?></h5>
                                        <p class="text-muted small">Sistema de Gestión</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Información del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Versión del Sistema
                                        <span class="badge bg-primary">1.0.0</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        PHP
                                        <span class="badge bg-success"><?php echo PHP_VERSION; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Base de Datos
                                        <span class="badge bg-info">MySQL</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        Última Actualización
                                        <small class="text-muted"><?php echo $config ? date('d/m/Y H:i', strtotime($config['fecha_actualizacion'])) : 'Nunca'; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/opticap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/opticap/assets/js/script.js"></script>
    
    <script>
        // Actualizar vista previa en tiempo real
        document.getElementById('nombre_sistema').addEventListener('input', function() {
            document.getElementById('previewNombre').textContent = this.value;
            document.getElementById('previewLoginNombre').textContent = this.value;
        });
        
        // Vista previa de imagen seleccionada
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Aquí se podría actualizar la vista previa de la imagen
                    console.log('Nueva imagen seleccionada:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>