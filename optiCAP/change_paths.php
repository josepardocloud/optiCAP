<?php
/**
 * Script para cambiar rutas relativas de CSS/JS a rutas absolutas
 * Ejecutar una sola vez desde la raíz del proyecto
 */

class PathUpdater {
    private $basePath;
    private $filesChanged = 0;
    
    public function __construct($basePath) {
        $this->basePath = realpath($basePath);
    }
    
    public function updateAllFiles() {
        echo "Iniciando actualización de rutas CSS/JS...\n";
        echo "Directorio base: " . $this->basePath . "\n\n";
        
        $this->updateDirectory($this->basePath);
        
        echo "\n✅ Proceso completado!\n";
        echo "Archivos modificados: " . $this->filesChanged . "\n";
    }
    
    private function updateDirectory($dir) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            $fullPath = $dir . '/' . $file;
            
            if (is_dir($fullPath)) {
                // Excluir directorios que no necesitan cambios
                if (!in_array($file, ['assets', 'vendor', 'logs', '.git', 'node_modules'])) {
                    $this->updateDirectory($fullPath);
                }
            } elseif (is_file($fullPath) && pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                $this->updateFile($fullPath);
            }
        }
    }
    
    private function updateFile($filePath) {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Patrones para buscar rutas CSS y JS
        $patterns = [
            // Rutas CSS
            '/href="(\.\.\/)+assets\/css\/([^"]+)"/',
            '/href=\'(\.\.\/)+assets\/css\/([^\']+)\'/',
            
            // Rutas JS
            '/src="(\.\.\/)+assets\/js\/([^"]+)"/',
            '/src=\'(\.\.\/)+assets\/js\/([^\']+)\'/',
            
            // Rutas de imágenes
            '/src="(\.\.\/)+assets\/img\/([^"]+)"/',
            '/src=\'(\.\.\/)+assets\/img\/([^\']+)\'/',
            
            // Rutas uploads
            '/src="(\.\.\/)+assets\/uploads\/([^"]+)"/',
            '/src=\'(\.\.\/)+assets\/uploads\/([^\']+)\'/',

            // Background images en CSS
            '/url\(\'(\.\.\/)+assets\/([^\']+)\'\)/',
            '/url\("(\.\.\/)+assets\/([^"]+)"\)/',
            '/url\((\.\.\/)+assets\/([^)]+)\)/'
        ];
        
        // Reemplazos
        $content = preg_replace('/href="(\.\.\/)+assets\/css\/([^"]+)"/', 'href="/opticap/assets/css/$2"', $content);
        $content = preg_replace('/href=\'(\.\.\/)+assets\/css\/([^\']+)\'/', 'href=\'/opticap/assets/css/$2\'', $content);
        
        $content = preg_replace('/src="(\.\.\/)+assets\/js\/([^"]+)"/', 'src="/opticap/assets/js/$2"', $content);
        $content = preg_replace('/src=\'(\.\.\/)+assets\/js\/([^\']+)\'/', 'src=\'/opticap/assets/js/$2\'', $content);
        
        $content = preg_replace('/src="(\.\.\/)+assets\/img\/([^"]+)"/', 'src="/opticap/assets/img/$2"', $content);
        $content = preg_replace('/src=\'(\.\.\/)+assets\/img\/([^\']+)\'/', 'src=\'/opticap/assets/img/$2\'', $content);
        
        $content = preg_replace('/src="(\.\.\/)+assets\/uploads\/([^"]+)"/', 'src="/opticap/assets/uploads/$2"', $content);
        $content = preg_replace('/src=\'(\.\.\/)+assets\/uploads\/([^\']+)\'/', 'src=\'/opticap/assets/uploads/$2\'', $content);

        // Background images
        $content = preg_replace('/url\(\'(\.\.\/)+assets\/([^\']+)\'\)/', 'url(\'/opticap/assets/$2\')', $content);
        $content = preg_replace('/url\("(\.\.\/)+assets\/([^"]+)"\)/', 'url("/opticap/assets/$2")', $content);
        $content = preg_replace('/url\((\.\.\/)+assets\/([^)]+)\)/', 'url(/opticap/assets/$2)', $content);
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->filesChanged++;
            echo "✅ Actualizado: " . str_replace($this->basePath . '/', '', $filePath) . "\n";
            
            // Mostrar cambios específicos
            $this->showChanges($originalContent, $content, $filePath);
        }
    }
    
    private function showChanges($original, $updated, $filePath) {
        $originalLines = explode("\n", $original);
        $updatedLines = explode("\n", $updated);
        
        for ($i = 0; $i < count($originalLines); $i++) {
            if (isset($updatedLines[$i]) && $originalLines[$i] !== $updatedLines[$i]) {
                if (preg_match('/assets\/(css|js|img|uploads)/', $originalLines[$i])) {
                    echo "   📍 Línea " . ($i + 1) . ": " . trim($originalLines[$i]) . "\n";
                    echo "   ➡️  Línea " . ($i + 1) . ": " . trim($updatedLines[$i]) . "\n";
                }
            }
        }
    }
}

// Ejecutar el script
if (php_sapi_name() === 'cli') {
    // Desde línea de comandos
    $updater = new PathUpdater(__DIR__);
    $updater->updateAllFiles();
} else {
    // Desde navegador web
    echo "<pre>";
    $updater = new PathUpdater(__DIR__);
    $updater->updateAllFiles();
    echo "</pre>";
}
?>