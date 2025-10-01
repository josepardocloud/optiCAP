<?php
// SOLO esto, sin session_start()
require_once 'config/session.php';

// Resto del código...
if (isset($_SESSION['usuario_id'])) {
    // Registrar log
}

session_destroy();
redirectTo('login.php');
?>