<?php
/**
 * Script para verificar que las correcciones se aplicaron correctamente
 */
class FixVerifier {
    private $basePath;
    
    public function __construct($basePath) {
        $this->basePath = realpath($basePath);
    }
    
    public function verifyAllFiles() {
        echo "<pre>";
        echo "🔍 VERIFICANDO CORRECCIONES APLICADAS\n";
        echo "====================================\n\n";
        
        $filesToVerify = [
            'config/session.php' => ['function redirectTo'],
            'includes/funciones.php' => ['function redirectTo'],
            'index.php' => ['redirectTo'],
            'modules/requerimientos/imprimir.php' => ['redirectTo', 'require_once.*session'],
            'modules/seguimiento/seguimiento.php' => ['redirectTo'],
            'modules/usuarios/acciones.php' => ['redirectTo']
        ];
        
        $allGood = true;
        
        foreach ($filesToVerify as $file => $patterns) {
            $result = $this->verifyFile($file, $patterns);
            if (!$result) {
                $allGood = false;
            }
        }
        
        echo "\n====================================\n";
        if ($allGood) {
            echo "✅ TODAS LAS VERIFICACIONES PASARON!\n";
        } else {
            echo "❌ ALGUNAS VERIFICACIONES FALLARON\n";
        }
        echo "</pre>";
        
        return $allGood;
    }
    
    private function verifyFile($relativePath, $patterns) {
        $fullPath = $this->basePath . '/' . $relativePath;
        
        if (!file_exists($fullPath)) {
            echo "❌ Archivo no encontrado: {$relativePath}\n";
            return false;
        }
        
        $content = file_get_contents($fullPath);
        $allPatternsFound = true;
        
        echo "📁 Verificando: {$relativePath}\n";
        
        foreach ($patterns as $pattern) {
            if (preg_match("/{$pattern}/", $content)) {
                echo "   ✅ Encontrado: {$pattern}\n";
            } else {
                echo "   ❌ NO encontrado: {$pattern}\n";
                $allPatternsFound = false;
            }
        }
        
        // Verificar que no hay header Location antiguos
        if (preg_match('/header\s*\(\s*["\']Location:/', $content)) {
            echo "   ⚠️  AÚN EXISTEN header Location ANTIGUOS\n";
            $allPatternsFound = false;
        } else {
            echo "   ✅ No hay header Location antiguos\n";
        }
        
        return $allPatternsFound;
    }
}

// Ejecutar verificación
echo "<h2>Verificador de Correcciones - OptiCAP</h2>";
$verifier = new FixVerifier(__DIR__);
$result = $verifier->verifyAllFiles();

if ($result) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>✅ ¡Todo correcto!</strong> Las correcciones se aplicaron exitosamente.";
    echo "</div>";
    echo "<p><a href='/opticap/login.php' class='btn btn-success'>Probar Sistema</a></p>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ Hay problemas</strong> Algunas correcciones no se aplicaron correctamente.";
    echo "</div>";
    echo "<p><a href='/opticap/fix_redirects.php' class='btn btn-warning'>Ejecutar Corrección Nuevamente</a></p>";
}
?>