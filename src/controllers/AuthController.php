<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/models/User.php';

class AuthController extends Controller {
    
    public function showLogin(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (isset($_SESSION['user'])) {
            $this->redirect('/');
        }
        
        $mode = $_GET['mode'] ?? 'login';
        $this->view('auth/login', ['mode' => $mode, 'errors' => []]);
    }
    
    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];
        
        if ($username === '' || $password === '') {
            $errors[] = "Введите имя пользователя и пароль.";
        } else {
            $user = User::findByUsername($username);
            if (!$user || !User::verifyPassword($user, $password)) {
                $errors[] = "Неверное имя пользователя или пароль.";
            } else {
                $_SESSION['user'] = ['id' => $user['id'], 'username' => $username];
                $this->redirect('/');
            }
        }
        
        $this->view('auth/login', ['mode' => 'login', 'errors' => $errors]);
    }
    
    public function register(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];
        
        if ($username === '' || $password === '') {
            $errors[] = "Введите имя пользователя и пароль.";
        } else {
            if (User::findByUsername($username)) {
                $errors[] = "Имя пользователя уже занято.";
            } else {
                $id = User::create($username, $password);
                $_SESSION['user'] = ['id' => $id, 'username' => $username];
                $this->redirect('/');
            }
        }
        
        $this->view('auth/login', ['mode' => 'register', 'errors' => $errors]);
    }
    
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        $this->redirect('/auth/login');
    }
}
