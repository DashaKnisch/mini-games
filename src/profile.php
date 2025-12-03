<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

if (empty($_SESSION['user'])) {
    header('Location: /auth/auth.php?mode=login');
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// Получаем игры пользователя
$gamesStmt = db_query('SELECT id, title, path, icon_path, created_at FROM games WHERE user_id = ? ORDER BY created_at DESC', [$userId]);
$myGames = $gamesStmt->fetchAll();

// Получаем результаты пользователя
$resStmt = db_query('SELECT r.id, r.game_id, r.score, r.meta, r.played_at, g.title FROM results r JOIN games g ON r.game_id = g.id WHERE r.user_id = ? ORDER BY r.played_at DESC', [$userId]);
$results = $resStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль — <?= htmlspecialchars($_SESSION['user']['username']) ?></title>
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body>
<header>
    <h1>Профиль пользователя</h1>
    <nav>
        <a href="/">Главная</a>
        <a href="/add_game.php">Добавить игру</a>
        <a href="/auth/auth.php?mode=logout">Выйти</a>
    </nav>
</header>

<main class="container">
    <h2><?= htmlspecialchars($_SESSION['user']['username']) ?></h2>

    <div class="tabs">
        <button onclick="showTab('games')">Мои игры</button>
        <button onclick="showTab('results')">Мои достижения</button>
    </div>

    <div id="tab-games" class="tab-content">
        <?php if(empty($myGames)): ?>
            <p>Вы ещё не добавили игр.</p>
        <?php else: ?>
            <ul class="game-list">
                <?php foreach($myGames as $g): ?>
                    <li class="game-item">
                        <?php if(!empty($g['icon_path'])): ?>
                            <img src="/<?= ltrim($g['icon_path'],'/') ?>" alt="icon" class="game-icon">
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($g['title']) ?></strong>
                        — <a href="/play.php?id=<?= (int)$g['id'] ?>">Играть</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div id="tab-results" class="tab-content" style="display:none;">
        <?php if(empty($results)): ?>
            <p>Результаты пока отсутствуют.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Игра</th><th>Очки</th><th>Дата</th></tr></thead>
                <tbody>
                    <?php foreach($results as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['title']) ?></td>
                            <td><?= (int)$r['score'] ?></td>
                            <td><?= htmlspecialchars($r['played_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>© <?= date("Y") ?> Мини-игры</p>
</footer>

<script>
function showTab(name){
    document.getElementById('tab-games').style.display = (name==='games')?'block':'none';
    document.getElementById('tab-results').style.display = (name==='results')?'block':'none';
}
</script>
</body>
</html>
