<?php
/**
 * Script para verificar archivos que usan header Location
 */
function findHeaderLocations($directory) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (preg_match('/header\s*\(\s*["\']Location:/', $content)) {
                $files[] = $file->getPathname();
            }
        }
    }
    
    return $files;
}

echo "Buscando archivos con header Location...\n";
$files = findHeaderLocations(__DIR__);

if (empty($files)) {
    echo "✅ No se encontraron archivos con header Location.\n";
} else {
    echo "Archivos que necesitan corrección:\n";
    foreach ($files as $file) {
        echo "📁 " . str_replace(__DIR__ . '/', '', $file) . "\n";
    }
}
?>