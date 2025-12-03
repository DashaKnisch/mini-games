<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

// Если не авторизован, редирект на вход
if (!isset($_SESSION['user'])) {
    header("Location: /auth/auth.php?mode=login");
    exit;
}

// Получаем список всех игр с описанием
try {
    $stmt = db_query("
        SELECT g.id, g.title, g.description, g.icon_path, g.created_at, u.username
        FROM games g
        JOIN users u ON g.user_id = u.id
        ORDER BY g.created_at DESC
    ");
    $games = $stmt->fetchAll();
} catch (Exception $e) {
    $games = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мини-игры — Главная</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<header>
    <h1>Мини-игры</h1>
    <nav>
        <ul>
            <li><a href="/">Главная</a></li>
            <li><a href="/profile.php">Профиль</a></li>
            <li><a href="/add_game.php">Добавить игру</a></li>
            <li><a href="/auth/auth.php?action=logout">Выйти</a></li>
        </ul>
    </nav>
</header>

<main class="container">
    <h2>Все игры пользователей</h2>

    <?php if (empty($games)): ?>
        <p class="no-games">Игры ещё не добавлены.</p>
    <?php else: ?>
        <div class="games-list">
            <?php foreach ($games as $g): ?>
                <div class="game-card">
                    <?php if (!empty($g['icon_path']) && file_exists(__DIR__ . '/' . ltrim($g['icon_path'], '/'))): ?>
                        <img class="game-icon" src="/<?= htmlspecialchars(ltrim($g['icon_path'], '/')); ?>" alt="icon">
                    <?php else: ?>
                        <div class="game-icon placeholder">ICON</div>
                    <?php endif; ?>

                    <div class="game-info">
                        <h3><?= htmlspecialchars($g['title']) ?></h3>
                        <p class="game-author">Автор: <?= htmlspecialchars($g['username']) ?></p>
                        <?php if (!empty($g['description'])): ?>
                            <p class="game-description"><?= nl2br(htmlspecialchars($g['description'])) ?></p>
                        <?php endif; ?>
                        <a class="game-button" href="/play.php?id=<?= (int)$g['id'] ?>">Играть</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>© <?= date("Y") ?> Мини-игры</p>
</footer>

</body>
</html>
