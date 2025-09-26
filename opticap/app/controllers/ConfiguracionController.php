<?php
class ConfiguracionController {
    private $configuracionModel;
    
    public function __construct() {
        AuthHelper::requireRole('admin');
        $this->configuracionModel = new Configuracion();
    }
    
    public function sistema() {
        $configuracion = $this->configuracionModel->obtener();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre_sistema' => filter_var($_POST['nombre_sistema'], FILTER_SANITIZE_STRING),
                'tiempo_maximo_proceso' => (int)$_POST['tiempo_maximo_proceso'],
                'email_notificaciones' => filter_var($_POST['email_notificaciones'], FILTER_SANITIZE_EMAIL)
            ];
            
            if ($this->configuracionModel->actualizar($datos)) {
                $this->registrarLog('ACTUALIZAR_CONFIGURACION', 'Configuración del sistema actualizada');
                $_SESSION['success'] = 'Configuración actualizada exitosamente';
                header('Location: ' . BASE_URL . 'configuracion/sistema');
                exit();
            } else {
                $_SESSION['error'] = 'Error al actualizar la configuración';
            }
        }
        
        $datos = [
            'configuracion' => $configuracion
        ];
        
        require_once 'app/views/configuracion/sistema.php';
    }
    
    public function logo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_FILES['logo']['name'])) {
                $uploadHelper = new UploadHelper();
                $resultado = $uploadHelper->subirArchivo($_FILES['logo'], 'logos', ['png', 'jpg', 'jpeg', 'gif']);
                
                if ($resultado['success']) {
                    if ($this->configuracionModel->actualizarLogo($resultado['file_path'])) {
                        $this->registrarLog('ACTUALIZAR_LOGO', 'Logo del sistema actualizado');
                        $_SESSION['success'] = 'Logo actualizado exitosamente';
                    } else {
                        $_SESSION['error'] = 'Error al actualizar el logo en la base de datos';
                    }
                } else {
                    $_SESSION['error'] = $resultado['error'];
                }
            } else {
                $_SESSION['error'] = 'Debe seleccionar un archivo';
            }
            
            header('Location: ' . BASE_URL . 'configuracion/logo');
            exit();
        }
        
        $configuracion = $this->configuracionModel->obtener();
        $datos = [
            'configuracion' => $configuracion
        ];
        
        require_once 'app/views/configuracion/logo.php';
    }
    
    private function registrarLog($accion, $descripcion) {
        $usuarioModel = new Usuario();
        $usuarioModel->registrarLog($_SESSION['user_id'], $accion, $descripcion);
    }
}
?>