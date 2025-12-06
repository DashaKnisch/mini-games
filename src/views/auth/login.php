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

        <form method="post" action="/auth/<?= $mode === 'register' ? 'register' : 'login' ?>">
            <input type="text" name="username" placeholder="Имя пользователя" required>
            <input type="password" name="password" placeholder="Пароль" required>

            <?php if ($mode === 'login'): ?>
                <button type="submit">Войти</button>
                <p class="switch-link">Нет аккаунта? <a href="/auth/login?mode=register">Зарегистрируйтесь</a></p>
            <?php else: ?>
                <button type="submit">Зарегистрироваться</button>
                <p class="switch-link">Уже есть аккаунт? <a href="/auth/login?mode=login">Войти</a></p>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
