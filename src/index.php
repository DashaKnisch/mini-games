<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

// Если не авторизован, редирект на вход
if (!isset($_SESSION['user'])) {
    header("Location: /auth/auth.php?mode=login");
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// Обработка лайков/дизлайков через обычный POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'], $_POST['vote'])) {
    $gameId = (int)$_POST['game_id'];
    $vote = (int)$_POST['vote'];

    if ($gameId > 0 && in_array($vote, [-1, 1])) {
        db_query("
            INSERT INTO game_votes (game_id, user_id, vote)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE vote=VALUES(vote)
        ", [$gameId, $userId, $vote]);
    }
    // После обработки перезагружаем страницу, чтобы показать обновлённые лайки
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// Получаем список всех игр с описанием + лайки/дизлайки
try {
    $stmt = db_query("
        SELECT g.id, g.title, g.description, g.icon_path, g.created_at, u.username,
            IFNULL(SUM(CASE WHEN v.vote=1 THEN 1 ELSE 0 END), 0) AS likes,
            IFNULL(SUM(CASE WHEN v.vote=-1 THEN 1 ELSE 0 END), 0) AS dislikes,
            COALESCE(
                (SELECT vote FROM game_votes WHERE game_id = g.id AND user_id = ?),
                0
            ) AS user_vote
        FROM games g
        JOIN users u ON g.user_id = u.id
        LEFT JOIN game_votes v ON g.id = v.game_id
        GROUP BY g.id
        ORDER BY g.created_at DESC
    ", [$userId]);
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

                        <!-- Лайки и дизлайки -->
                        <div class="game-votes">
                            <form method="post">
                                <input type="hidden" name="game_id" value="<?= (int)$g['id'] ?>">
                                <input type="hidden" name="vote" value="1">
                                <button type="submit" class="<?= $g['user_vote']==1 ? 'liked' : '' ?>">👍 <?= (int)$g['likes'] ?></button>
                            </form>

                            <form method="post">
                                <input type="hidden" name="game_id" value="<?= (int)$g['id'] ?>">
                                <input type="hidden" name="vote" value="-1">
                                <button type="submit" class="<?= $g['user_vote']==-1 ? 'disliked' : '' ?>">👎 <?= (int)$g['dislikes'] ?></button>
                            </form>
                        </div>

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
