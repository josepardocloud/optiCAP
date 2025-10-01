<?php
// Evitar múltiples session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        redirectTo('login.php');
    }
}

function verificarRol($rolesPermitidos) {
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
        redirectTo('dashboard.php');
    }
}

// Función para redireccionar con rutas absolutas
function redirectTo($path) {
    $base_path = '/opticap/';
    $absolute_path = $base_path . ltrim($path, '/');
    header("Location: " . $absolute_path);
    exit();
}

// Función para verificar si el usuario está logueado (sin redirección)
function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}
?>