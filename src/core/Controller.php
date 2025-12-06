<?php

class Controller {
    protected function view(string $viewName, array $data = []): void {
        extract($data);
        $viewPath = $_SERVER['DOCUMENT_ROOT'] . '/src/views/' . $viewName . '.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "View not found: $viewName";
        }
    }
    
    protected function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
    
    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function requireAuth(): array {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user'])) {
            $this->redirect('/auth/login');
        }
        
        return $_SESSION['user'];
    }
}
