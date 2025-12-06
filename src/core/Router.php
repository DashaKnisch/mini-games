<?php

class Router {
    private array $routes = [];
    
    public function get(string $path, string $controller, string $method): void {
        $this->addRoute('GET', $path, $controller, $method);
    }
    
    public function post(string $path, string $controller, string $method): void {
        $this->addRoute('POST', $path, $controller, $method);
    }
    
    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'controller' => $controller,
            'action' => $method
        ];
    }
    
    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);
            
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches);
                $controllerName = $route['controller'];
                $action = $route['action'];
                
                $controller = new $controllerName();
                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }
        
        http_response_code(404);
        echo '404 - Страница не найдена';
    }
    
    private function convertToRegex(string $path): string {
        $path = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);
        return '#^' . $path . '$#';
    }
}
