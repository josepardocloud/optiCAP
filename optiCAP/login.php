<?php
// NO llamar session_start() aquí, ya se llama en session.php
require_once 'config/session.php';
require_once 'config/database.php'; // ✅ Añadir esta línea

$error = '';

// Obtener configuración del sistema
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Valores por defecto si no hay configuración
$nombre_sistema = $config['nombre_sistema'] ?? 'OptiCAP';
$logo_url = $config['logo_url'] ?? null;

if ($_POST) {
    $usuario_input = trim($_POST['usuario']);
    $password = $_POST['password'];
    
    // Verificar si el usuario está bloqueado
    $query = "SELECT * FROM usuarios WHERE usuario = ? AND bloqueado = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_input]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Cuenta bloqueada. Contacte al administrador.";
    } else {
        // Buscar usuario SOLO por nombre de usuario (no por email)
        $query = "SELECT * FROM usuarios WHERE usuario = ? AND activo = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$usuario_input]);
        
        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $usuario['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['usuario'] = $usuario['usuario'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['telefono'] = $usuario['telefono'];
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
    <title><?php echo htmlspecialchars($nombre_sistema); ?> - Login</title>
    <link href="/opticap/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/opticap/assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-body {
            background: linear-gradient(135deg, #ddddddff 0%, #a5a4a5ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 1rem;
            border-radius: 50%;
            background: white;
            padding: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .system-name {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .system-description {
            color: #7f8c8d;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .system-entity {
            color: #95a5a6;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            background: white;
        }

        .btn-login {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .info-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .info-icon {
            color: #3498db;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .alert-danger {
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .form-text {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        /* Efectos de partículas sutiles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 15px;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .system-name {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="login-body">
    <!-- Partículas de fondo sutiles -->
    <div class="particles" id="particles"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <?php if ($logo_url): ?>
                <img src="/opticap/assets/uploads/logos/<?php echo $logo_url; ?>" 
                     alt="<?php echo htmlspecialchars($nombre_sistema); ?>" 
                     class="logo-img">
                <?php else: ?>
                <img src="/opticap/assets/img/logo.png" 
                     alt="<?php echo htmlspecialchars($nombre_sistema); ?>" 
                     class="logo-img">
                <?php endif; ?>
                
                <h1 class="system-name"><?php echo htmlspecialchars($nombre_sistema); ?></h1>
                <p class="system-description">Sistema de Gestión de Procesos de Adquisición</p>
                <p class="system-entity">Dirección Regional de Educación Ucayali</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="usuario" class="form-label">
                        <i class="fas fa-user me-1"></i>Usuario
                    </label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required 
                           placeholder="Ingrese su nombre de usuario">
                    <div class="form-text">Utilice su nombre de usuario asignado</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-1"></i>Contraseña
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="Ingrese su contraseña">
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                </button>
            </form>
            
            <!-- Información adicional -->
            <div class="info-box">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle info-icon"></i>
                    <div>
                        <small class="text-muted">
                            <strong>Nota:</strong> Solo puede ingresar con su nombre de usuario. 
                            Si no recuerda su usuario, contacte al administrador del sistema.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Crear partículas de fondo sutiles
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Tamaño aleatorio entre 3px y 8px
                const size = Math.random() * 5 + 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posición aleatoria
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Retraso de animación aleatorio
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                // Opacidad aleatoria
                particle.style.opacity = Math.random() * 0.3 + 0.1;
                
                particlesContainer.appendChild(particle);
            }
        });

        // Efecto de focus en los campos del formulario
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });

        // Prevenir el envío del formulario con Enter en campos no deseados
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const target = e.target;
                if (target.tagName === 'INPUT' && target.type !== 'submit') {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>