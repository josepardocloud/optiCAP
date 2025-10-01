<?php
class Router {
    private $routes = [];
    
    public function add($route, $controllerAction) {
        $this->routes[$route] = $controllerAction;
    }
    
    public function dispatch($url) {
        $url = $this->sanitizeUrl($url);
        
        foreach ($this->routes as $route => $controllerAction) {
            $pattern = $this->convertToPattern($route);
            
            if (preg_match($pattern, $url, $matches)) {
                array_shift($matches);
                return $this->callController($controllerAction, $matches);
            }
        }
        
        // 404 - Página no encontrada
        http_response_code(404);
        $this->renderView('errors/404');
        exit;
    }
    
    private function sanitizeUrl($url) {
        $url = trim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return $url ?: '';
    }
    
    private function convertToPattern($route) {
        $pattern = str_replace('/', '\/', $route);
        $pattern = preg_replace('/\(\\\\d\+\)/', '(\d+)', $pattern);
        $pattern = '/^' . $pattern . '$/';
        return $pattern;
    }
    
    private function callController($controllerAction, $params = []) {
        list($controller, $method) = explode('@', $controllerAction);
        
        if (!class_exists($controller)) {
            throw new Exception("Controller $controller no encontrado");
        }
        
        $controllerInstance = new $controller();
        
        if (!method_exists($controllerInstance, $method)) {
            throw new Exception("Método $method no existe en $controller");
        }
        
        call_user_func_array([$controllerInstance, $method], $params);
    }
    
    private function renderView($view, $data = []) {
        extract($data);
        $viewFile = APP_PATH . "/views/$view.php";
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            throw new Exception("Vista $view no encontrada");
        }
    }
}
?>