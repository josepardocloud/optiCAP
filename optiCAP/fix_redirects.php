<?php
/**
 * fix_redirects.php
 *
 * Script para corregir automáticamente redirecciones (header Location) 
 * y añadir una helper function redirectTo() en archivos de configuración.
 *
 * Uso (desde navegador o CLI):
 * - Coloca este archivo en la raíz del proyecto (por ejemplo /opticap/)
 * - Visítalo en el navegador: http://localhost/opticap/fix_redirects.php
 * - O ejecútalo por CLI: php fix_redirects.php /ruta/a/opticap
 *
 * IMPORTANTE: siempre revisa las copias de seguridad (.bak) antes de confirmar los cambios.
 */

class RedirectFixer {
    private $basePath;
    private $filesFixed = 0;
    private $modifiedFiles = [];
    private $backups = [];

    public function __construct($basePath) {
        if (empty($basePath)) {
            throw new \InvalidArgumentException("Debe especificar la ruta base del proyecto.");
        }
        $real = realpath($basePath);
        if ($real === false) {
            throw new \InvalidArgumentException("La ruta especificada no existe: $basePath");
        }
        $this->basePath = $real;
    }

    /**
     * Ejecuta la corrección en todos los archivos PHP dentro de basePath.
     */
    public function fixAllFiles() {
        $files = $this->getPhpFiles($this->basePath);
        foreach ($files as $file) {
            $this->fixFile($file);
        }
    }

    /**
     * Obtiene lista recursiva de archivos .php
     */
    private function getPhpFiles($dir) {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            $path = $file->getPathname();
            // Omite directorios comunes que no deseas tocar (node_modules, vendor, .git)
            if (preg_match('#/(node_modules|vendor|\.git)/#', str_replace('\\', '/', $path))) continue;
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
                $files[] = $path;
            }
        }
        return $files;
    }

    /**
     * Realiza la corrección en un archivo PHP:
     *  - crea copia de seguridad .bak
     *  - reemplaza redirectTo('...');por redirectTo('...')
     *  - si el archivo es uno de los targets (config/session.php, includes/funciones.php)
     *    inserta la función redirectTo si no existe.
     */
    private function fixFile($fullPath) {
        $content = @file_get_contents($fullPath);
        if ($content === false) return;

        $original = $content;
        $changed = false;

        // 1) Reemplazo de redirectTo('...');y redirectTo('...');//    Considera variaciones con espacios y concatenaciones simples.
        $patternHeader = '/header\s*\(\s*(?:\'|")\s*Location\s*:\s*([^\'"]+)(?:\'|")\s*\)\s*;?/i';
        if (preg_match_all($patternHeader, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $location = trim($m[1]);
                // Si contiene .php? o ../ etc, lo mantenemos tal cual como argumento
                $replacement = "redirectTo(" . var_export($location, true) . ");";
                $content = str_replace($m[0], $replacement, $content);
                $changed = true;
            }
        }

        // 2) Reemplazo de redirectTo($var); - caso simple con concatenación
        //    Ejemplo: redirectTo($url);;
        $patternConcat = '/header\s*\(\s*(?:\'|")\s*Location\s*:\s*(?:\'|")\s*\.\s*([^)\;]+)\)/i';
        if (preg_match_all($patternConcat, $content, $matches2, PREG_SET_ORDER)) {
            foreach ($matches2 as $m) {
                $expr = trim($m[1]);
                $replacement = "redirectTo(" . '$this->safeConcat(' . $expr . '));';
                // fallback: reemplazamos por una llamada simple usando la expresión cruda
                // (siempre es preferible revisar manualmente estos casos)
                $content = str_replace($m[0], "redirectTo(" . $expr . ");", $content);
                $changed = true;
            }
        }

        // 3) Si es uno de los archivos de configuración objetivo, asegurar que exista redirectTo()
        $normalized = str_replace('\\', '/', $fullPath);
        $basename = strtolower(basename($fullPath));
        $insertedHelper = false;

        if (preg_match('#/config/session\.php$#i', $normalized) || preg_match('#/includes/funciones\.php$#i', $normalized)) {
            if (strpos($content, 'function redirectTo(') === false) {
                $helper = $this->getRedirectHelperPhp();
                // Insertar el helper al principio del archivo después de la etiqueta <?php
                if (preg_match('/\<\?php\s*/i', $content, $mpos)) {
                    $content = preg_replace('/\<\?php\s*/i', "<?php\n" . $helper . "\n", $content, 1);
                } else {
                    // si el archivo no tiene <?php (raro), lo anteponemos
                    $content = "<?php\n" . $helper . "\n" . $content;
                }
                $changed = true;
                $insertedHelper = true;
            }
        }

        if ($changed && $content !== $original) {
            // Crear copia de seguridad si no existe ya
            $bakPath = $fullPath . '.bak';
            if (!file_exists($bakPath)) {
                @copy($fullPath, $bakPath);
                $this->backups[] = $bakPath;
            }
            // Guardar cambios
            @file_put_contents($fullPath, $content);
            $this->filesFixed++;
            $this->modifiedFiles[] = [
                'file' => $fullPath,
                'helper_added' => $insertedHelper
            ];
        }
    }

    /**
     * Contenido de la función helper redirectTo() que insertaremos.
     * Esta función es segura: limpia encabezados ya enviados y usa exit.
     */
    private function getRedirectHelperPhp() {
        // Usamos NOWDOC para evitar problemas con comillas en la inserción.
        return <<<'PHP'
/**
 * Helper: redirectTo
 * Uso: redirectTo('/ruta/destino.php'); o redirectTo('login.php?m=1');
 * Hace un header Location seguro y termina la ejecución con exit.
 */
if (!function_exists('redirectTo')) {
    function redirectTo($location) {
        // Normalizar: si la ubicación no contiene esquema y no empieza por '/', la dejamos tal cual
        if (!headers_sent()) {
            // Si la ruta parece relativa local, resuelve mínimamente:
            redirectTo($location);;
        } else {
            // Si headers ya fueron enviados, usar meta refresh como fallback
            echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '">';
            echo '<script>window.location.href=' . json_encode($location) . ';</script>';
        }
        exit;
    }
}
PHP;
    }

    /**
     * Genera un resumen HTML con los cambios aplicados.
     */
    public function getSummaryHtml() {
        ob_start();
        echo "<h2>Fix Redirects - Resultado</h2>\n";
        echo "<p>Base path: <code>" . htmlspecialchars($this->basePath) . "</code></p>\n";
        echo "<p>Archivos modificados: <strong>" . intval($this->filesFixed) . "</strong></p>\n";

        if (!empty($this->modifiedFiles)) {
            echo "<ul>";
            foreach ($this->modifiedFiles as $m) {
                $f = htmlspecialchars(str_replace($this->basePath, '', $m['file']));
                echo "<li>{$f}";
                if (!empty($m['helper_added'])) {
                    echo " — <em>helper redirectTo() añadido</em>";
                }
                echo "</li>";
            }
            echo "</ul>";
        }

        if (!empty($this->backups)) {
            echo "<h3>Copias de seguridad creadas (.bak)</h3>";
            echo "<ul>";
            foreach ($this->backups as $b) {
                echo "<li>" . htmlspecialchars(str_replace($this->basePath, '', $b)) . "</li>";
            }
            echo "</ul>";
        }

        echo "<p><strong>Nota:</strong> Revisa manualmente los archivos marcados. Los reemplazos automáticos intentaron cubrir casos simples de <code>redirectTo('...');</code>. Si tus redirecciones usan lógica compleja (variables concatenadas, constantes, funciones de enrutamiento), verifica manualmente.</p>";
        echo "<p><a href=\"/\">Volver a la raíz</a></p>";

        return ob_get_clean();
    }
}

// ------------- EJECUCIÓN -------------

try {
    // Base path por defecto: directorio actual
    $basePathArg = __DIR__;
    // Permite pasar ruta por CLI: php fix_redirects.php /ruta/a/proyecto
    if (isset($argv) && count($argv) > 1) {
        $basePathArg = $argv[1];
    } elseif (!empty($_GET['base'])) {
        $basePathArg = $_GET['base'];
    }

    $fixer = new RedirectFixer($basePathArg);
    $fixer->fixAllFiles();

    // Si se está ejecutando desde navegador, mostrar HTML
    if (php_sapi_name() !== 'cli') {
        echo "<!doctype html><html><head><meta charset='utf-8'><title>Fix Redirects</title></head><body style='font-family:Arial,Helvetica,sans-serif;padding:1rem;'>";
        echo $fixer->getSummaryHtml();
        echo "</body></html>";
    } else {
        // CLI output
        echo "Fix Redirects - Resultado\n";
        echo "Base: {$basePathArg}\n";
        echo "Archivos modificados: " . $fixer->filesFixed . "\n";
    }

} catch (Throwable $e) {
    // Mensaje de error amigable
    if (php_sapi_name() !== 'cli') {
        echo "<h2>Error</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    } else {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
    exit(1);
}
?>