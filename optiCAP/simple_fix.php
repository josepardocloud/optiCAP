<?php
/**
 * Script simple para corregir redirecciones sin recursión
 */

echo "<h2>Corrector Simple de Redirecciones</h2>";
echo "<pre>";

$files = [
    'index.php' => '<?php
header("Location: /opticap/login.php");
exit();
?>',

    'modules/usuarios/acciones.php' => null, // Se procesará después
];

// Corregir index.php
if (file_exists('index.php')) {
    file_put_contents('index.php', $files['index.php']);
    echo "✅ index.php corregido\n";
}

// Función para corregir archivos con header Location
function fixHeaderLocations($filePath) {
    if (!file_exists($filePath)) {
        echo "❌ Archivo no encontrado: $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    $original = $content;

    // Reemplazar header Location por redirectTo
    $content = preg_replace(
        '/header\s*\(\s*["\']Location:\s*([^"\']+)["\']\s*\)\s*;\s*(exit\s*\(\s*\)\s*;)?/i',
        'redirectTo($1);',
        $content
    );

    // Agregar include de session si no existe y se usa redirectTo
    if (strpos($content, 'redirectTo(') !== false && strpos($content, 'config/session.php') === false) {
        // Buscar la primera línea después de <?php
        $content = preg_replace(
            '/<\?php\s*/',
            "<?php\nrequire_once 'config/session.php';\n",
            $content,
            1
        );
    }

    if ($content !== $original) {
        file_put_contents($filePath, $content);
        return true;
    }

    return false;
}

// Archivos a corregir
$filesToFix = [
    'modules/usuarios/acciones.php',
    'modules/requerimientos/imprimir.php',
    'modules/seguimiento/seguimiento.php'
];

foreach ($filesToFix as $file) {
    if (fixHeaderLocations($file)) {
        echo "✅ $file corregido\n";
    } else {
        echo "ℹ️  $file ya estaba correcto\n";
    }
}

// Verificar session.php
if (file_exists('config/session.php')) {
    $sessionContent = file_get_contents('config/session.php');
    if (strpos($sessionContent, 'function redirectTo') === false) {
        echo "❌ config/session.php necesita la función redirectTo\n";
        echo "Ejecuta el siguiente código manualmente:\n";
        echo "1. Abre config/session.php\n";
        echo "2. Agrega esta función al final:\n\n";
        echo "function redirectTo(\$path) {\n";
        echo "    \$base_path = '/opticap/';\n";
        echo "    \$absolute_path = \$base_path . ltrim(\$path, '/');\n";
        echo "    header(\"Location: \" . \$absolute_path);\n";
        echo "    exit();\n";
        echo "}\n";
    } else {
        echo "✅ config/session.php tiene la función redirectTo\n";
    }
}

echo "\n🎯 CORRECCIÓN COMPLETADA\n";
echo "========================\n";
echo "Archivos corregidos:\n";
echo "- index.php\n";
echo "- modules/usuarios/acciones.php\n"; 
echo "- modules/requerimientos/imprimir.php\n";
echo "- modules/seguimiento/seguimiento.php\n";
echo "\n⚠️  Si config/session.php no tiene redirectTo, agrégalo manualmente\n";
echo "</pre>";

echo "<p><a href='/opticap/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Probar Login</a></p>";
?>