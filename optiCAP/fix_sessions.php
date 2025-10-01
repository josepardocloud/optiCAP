<?php
/**
 * Script para eliminar session_start() duplicados
 */
class SessionFixer {
    private $basePath;
    
    public function __construct($basePath) {
        $this->basePath = realpath($basePath);
    }
    
    public function fixAllFiles() {
        echo "<pre>";
        echo "🔧 CORRIGIENDO session_start() DUPLICADOS\n";
        echo "========================================\n\n";
        
        $files = $this->findPHPFiles();
        $fixedCount = 0;
        
        foreach ($files as $file) {
            if ($this->fixFile($file)) {
                $fixedCount++;
            }
        }
        
        echo "\n========================================\n";
        echo "✅ CORRECCIÓN COMPLETADA!\n";
        echo "Archivos corregidos: {$fixedCount}\n";
        echo "</pre>";
    }
    
    private function findPHPFiles() {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->basePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Excluir session.php mismo
                if (strpos($file->getPathname(), 'session.php') === false) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }
    
    private function fixFile($filePath) {
        $content = file_get_contents($filePath);
        $original = $content;
        
        // Solo corregir archivos que incluyan session.php
        if (strpos($content, "require_once") !== false && 
            (strpos($content, "session.php") !== false || strpos($content, "config/session.php") !== false)) {
            
            // Eliminar session_start() si existe después de include session.php
            $content = preg_replace('/session_start\s*\(\s*\)\s*;/', '', $content);
            
            if ($content !== $original) {
                file_put_contents($filePath, $content);
                echo "✅ Corregido: " . str_replace($this->basePath . '/', '', $filePath) . "\n";
                return true;
            }
        }
        
        return false;
    }
}

// Ejecutar
echo "<h2>Corrector de session_start() Duplicados</h2>";
$fixer = new SessionFixer(__DIR__);
$fixer->fixAllFiles();

echo "<hr>";
echo "<p><strong>⚠️ NOTA:</strong> Este script elimina session_start() de archivos que ya incluyen session.php</p>";
echo "<p><a href='/opticap/login.php'>Probar Login</a></p>";
?>