<?php
echo "<h2>Verificador de Estado</h2>";
echo "<pre>";

// Verificar session.php
if (file_exists('config/session.php')) {
    $content = file_get_contents('config/session.php');
    if (strpos($content, 'function redirectTo') !== false) {
        echo "✅ config/session.php - OK\n";
    } else {
        echo "❌ config/session.php - FALTA redirectTo\n";
    }
} else {
    echo "❌ config/session.php - NO EXISTE\n";
}

// Verificar archivos corregidos
$files = [
    'index.php' => 'header("Location:',
    'modules/usuarios/acciones.php' => 'redirectTo(',
    'modules/requerimientos/imprimir.php' => 'require_once',
    'modules/seguimiento/seguimiento.php' => 'require_once'
];

foreach ($files as $file => $check) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $check) !== false) {
            echo "✅ $file - OK\n";
        } else {
            echo "❌ $file - PROBLEMA\n";
        }
    } else {
        echo "❌ $file - NO EXISTE\n";
    }
}

echo "\n🎯 INSTRUCCIONES:\n";
echo "1. Si hay errores, ejecuta simple_fix.php\n";
echo "2. Si session.php no tiene redirectTo, agrégalo manualmente\n";
echo "3. Prueba el login: http://localhost/opticap/login.php\n";
echo "</pre>";
?>