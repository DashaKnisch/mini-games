<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../lib/database.php';

// === LOGOUT ===
if (($_GET['action'] ?? '') === 'logout') {
    // Удаляем все данные сессии
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    // Перенаправляем сразу на страницу входа
    header("Location: /auth/auth.php?mode=login");
    exit;
}

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$errors = [];
$mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $action   = $_POST['action'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = "Введите имя пользователя и пароль.";
    } else {
        // LOGIN
        if ($action === 'login') {
            $stmt = db_query("SELECT id, password_hash FROM users WHERE username = ?", [$username]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = "Неверное имя пользователя или пароль.";
            } else {
                $_SESSION['user'] = ['id' => $user['id'], 'username' => $username];
                header("Location: /");
                exit;
            }
        }

        // REGISTER
        if ($action === 'register') {
            $stmt = db_query("SELECT id FROM users WHERE username = ?", [$username]);
            if ($stmt->fetch()) {
                $errors[] = "Имя пользователя уже занято.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                db_query("INSERT INTO users (username, password_hash) VALUES (?, ?)", [$username, $hash]);
                $id = getPDO()->lastInsertId();
                $_SESSION['user'] = ['id' => $id, 'username' => $username];
                header("Location: /");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $mode === 'register' ? "Регистрация" : "Вход" ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-box">
        <h2><?= $mode === 'register' ? "Регистрация" : "Вход в аккаунт" ?></h2>

        <?php if ($errors): ?>
            <div class="errors">
                <?php foreach ($errors as $e): ?>
                    <p><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" placeholder="Имя пользователя" required>
            <input type="password" name="password" placeholder="Пароль" required>

            <?php if ($mode === 'login'): ?>
                <button type="submit" name="action" value="login">Войти</button>
                <p class="switch-link">Нет аккаунта? <a href="?mode=register">Зарегистрируйтесь</a></p>
            <?php else: ?>
                <button type="submit" name="action" value="register">Зарегистрироваться</button>
                <p class="switch-link">Уже есть аккаунт? <a href="?mode=login">Войти</a></p>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
