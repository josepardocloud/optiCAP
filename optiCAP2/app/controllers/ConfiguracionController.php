<?php
class ConfiguracionController {
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Database();
        $this->checkAccess();
    }
    
    private function checkAccess() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login');
            exit;
        }
        
        // Solo administradores pueden acceder
        if ($this->auth->getUser()['rol_nombre'] !== 'Administrador') {
            http_response_code(403);
            $this->renderView('errors/403');
            exit;
        }
    }
    
    public function sistema() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->actualizarConfiguracionSistema($_POST);
                $_SESSION['success'] = "Configuración del sistema actualizada correctamente";
                header('Location: ' . SITE_URL . '/configuracion');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $configuracion = $this->obtenerConfiguracionSistema();
        
        $data = [
            'pageTitle' => 'Configuración del Sistema',
            'currentPage' => 'configuracion',
            'user' => $this->auth->getUser(),
            'configuracion' => $configuracion,
            'error' => $error ?? null
        ];
        
        $this->renderView('configuracion/sistema', $data);
    }
    
    public function email() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->actualizarConfiguracionEmail($_POST);
                
                // Probar configuración
                if (isset($_POST['probar_configuracion'])) {
                    $this->probarConfiguracionEmail($_POST);
                }
                
                $_SESSION['success'] = "Configuración de email actualizada correctamente";
                header('Location: ' . SITE_URL . '/configuracion/email');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $configuracion = $this->obtenerConfiguracionEmail();
        
        $data = [
            'pageTitle' => 'Configuración de Email',
            'currentPage' => 'configuracion',
            'user' => $this->auth->getUser(),
            'configuracion' => $configuracion,
            'error' => $error ?? null
        ];
        
        $this->renderView('configuracion/email', $data);
    }
    
    public function sla() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->actualizarConfiguracionSLA($_POST);
                $_SESSION['success'] = "Configuración de SLA actualizada correctamente";
                header('Location: ' . SITE_URL . '/configuracion/sla');
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $procesos = $this->obtenerProcesosConActividades();
        $configuracion = $this->obtenerConfiguracionSLA();
        
        $data = [
            'pageTitle' => 'Configuración de SLA',
            'currentPage' => 'configuracion',
            'user' => $this->auth->getUser(),
            'procesos' => $procesos,
            'configuracion' => $configuracion,
            'error' => $error ?? null
        ];
        
        $this->renderView('configuracion/sla', $data);
    }
    
    private function obtenerConfiguracionSistema() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion_sistema");
        $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'nombre_sistema' => $configuraciones['nombre_sistema'] ?? SITE_NAME,
            'logo_sistema' => $configuraciones['logo_sistema'] ?? '/public/assets/img/logo.png',
            'max_intentos_login' => $configuraciones['max_intentos_login'] ?? 4,
            'tiempo_bloqueo_minutos' => $configuraciones['tiempo_bloqueo_minutos'] ?? 30,
            'max_tamano_archivo_mb' => $configuraciones['max_tamano_archivo_mb'] ?? 10,
            'dias_alerta_vencimiento' => $configuraciones['dias_alerta_vencimiento'] ?? 3
        ];
    }
    
    private function actualizarConfiguracionSistema($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $configuraciones = [
                'nombre_sistema' => $data['nombre_sistema'],
                'logo_sistema' => $data['logo_sistema'],
                'max_intentos_login' => intval($data['max_intentos_login']),
                'tiempo_bloqueo_minutos' => intval($data['tiempo_bloqueo_minutos']),
                'max_tamano_archivo_mb' => intval($data['max_tamano_archivo_mb']),
                'dias_alerta_vencimiento' => intval($data['dias_alerta_vencimiento'])
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO configuracion_sistema (clave, valor, actualizado_en) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE valor = ?, actualizado_en = NOW()
            ");
            
            foreach ($configuraciones as $clave => $valor) {
                $stmt->execute([$clave, $valor, $valor]);
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    private function obtenerConfiguracionEmail() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'smtp_%'");
        $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'smtp_host' => $configuraciones['smtp_host'] ?? 'smtp.gmail.com',
            'smtp_user' => $configuraciones['smtp_user'] ?? '',
            'smtp_pass' => $configuraciones['smtp_pass'] ?? '',
            'smtp_port' => $configuraciones['smtp_port'] ?? 587,
            'smtp_secure' => $configuraciones['smtp_secure'] ?? 'tls'
        ];
    }
    
    private function actualizarConfiguracionEmail($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            $configuraciones = [
                'smtp_host' => $data['smtp_host'],
                'smtp_user' => $data['smtp_user'],
                'smtp_pass' => $data['smtp_pass'],
                'smtp_port' => intval($data['smtp_port']),
                'smtp_secure' => $data['smtp_secure']
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO configuracion_sistema (clave, valor, actualizado_en) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE valor = ?, actualizado_en = NOW()
            ");
            
            foreach ($configuraciones as $clave => $valor) {
                $stmt->execute([$clave, $valor, $valor]);
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    private function probarConfiguracionEmail($config) {
        $notificacion = new Notificacion();
        
        $asunto = "Prueba de Configuración - " . SITE_NAME;
        $mensaje = "
            Este es un email de prueba para verificar la configuración del servidor SMTP.
            
            Si está recibiendo este mensaje, la configuración de email está funcionando correctamente.
            
            Configuración probada:
            - Servidor: {$config['smtp_host']}
            - Puerto: {$config['smtp_port']}
            - Seguridad: {$config['smtp_secure']}
            
            Fecha y hora de envío: " . date('d/m/Y H:i:s') . "
        ";
        
        $notificacion->enviarEmail($config['smtp_user'], $asunto, $mensaje);
    }
    
    private function obtenerProcesosConActividades() {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("
            SELECT p.*, tp.nombre as tipo_proceso_nombre
            FROM procesos p
            JOIN tipos_proceso tp ON p.tipo_proceso_id = tp.id
            WHERE p.estado = 'activo'
            ORDER BY tp.nombre, p.nombre
        ");
        $procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener actividades para cada proceso
        foreach ($procesos as &$proceso) {
            $stmt = $pdo->prepare("
                SELECT * FROM actividades 
                WHERE proceso_id = ? AND estado = 'activo' 
                ORDER BY orden
            ");
            $stmt->execute([$proceso['id']]);
            $proceso['actividades'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $procesos;
    }
    
    private function obtenerConfiguracionSLA() {
        // Obtener configuración de SLA desde la base de datos
        $pdo = $this->db->getConnection();
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'sla_%'");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    private function actualizarConfiguracionSLA($data) {
        $pdo = $this->db->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Actualizar duraciones de actividades
            if (!empty($data['duraciones'])) {
                $stmt = $pdo->prepare("UPDATE actividades SET duracion_estimada = ? WHERE id = ?");
                
                foreach ($data['duraciones'] as $actividadId => $duracion) {
                    $stmt->execute([intval($duracion), $actividadId]);
                }
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        require_once APP_PATH . "/views/layouts/header.php";
        require_once APP_PATH . "/views/$view.php";
        require_once APP_PATH . "/views/layouts/footer.php";
    }
}
?>