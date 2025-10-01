<?php
// NO llamar session_start() aquí, ya se llama en session.php
require_once 'config/session.php';
require_once 'config/database.php'; // ✅ Añadir esta línea

$error = '';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Verificar si el usuario está bloqueado
    $query = "SELECT * FROM usuarios WHERE email = ? AND bloqueado = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Cuenta bloqueada. Contacte al administrador.";
    } else {
        // Buscar usuario
        $query = "SELECT * FROM usuarios WHERE email = ? AND activo = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $usuario['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['area_id'] = $usuario['area_id'];
                
                // Reiniciar intentos fallidos
                $query = "UPDATE usuarios SET intentos_fallidos = 0, ultimo_login = NOW() WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$usuario['id']]);
                
                // Registrar log de seguridad
                $query = "INSERT INTO logs_seguridad (usuario_id, ip, accion, resultado, detalles) VALUES (?, ?, 'login', 'exito', 'Login exitoso')";
                $stmt = $db->prepare($query);
                $stmt->execute([$usuario['id'], $_SERVER['REMOTE_ADDR']]);
                
                redirectTo('dashboard.php');
                
            } else {
                // Password incorrecto
                $intentos = $usuario['intentos_fallidos'] + 1;
                
                if ($intentos >= 4) {
                    // Bloquear cuenta
                    $query = "UPDATE usuarios SET intentos_fallidos = ?, bloqueado = 1, fecha_bloqueo = NOW() WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$intentos, $usuario['id']]);
                    
                    $error = "Cuenta bloqueada. Contacte al administrador.";
                } else {
                    // Incrementar intentos
                    $query = "UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$intentos, $usuario['id']]);
                    
                    $intentos_restantes = 4 - $intentos;
                    $error = "Credenciales incorrectas. Le quedan {$intentos_restantes} intentos.";
                }
            }
        } else {
            $error = "Credenciales incorrectas.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OptiCAP - Login</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <h2 class="login-title">OptiCAP</h2>
                <p class="text-muted">Sistema de Gestión de Procesos de Adquisición</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</body>
</html>