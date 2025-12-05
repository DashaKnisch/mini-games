<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

if (empty($_SESSION['user'])) {
    header('Location: /auth/auth.php?mode=login');
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// === Получаем уведомления пользователя ===
$messagesStmt = db_query("SELECT id, message, created_at FROM user_messages WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
$messages = $messagesStmt->fetchAll();

// === Удаление игры ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game_id'])) {
    $deleteId = (int)$_POST['delete_game_id'];
    $stmt = db_query('SELECT path FROM games WHERE id = ? AND user_id = ?', [$deleteId, $userId]);
    $game = $stmt->fetch();
    if ($game) {
        $gameDir = __DIR__ . '/' . $game['path'];
        if (is_dir($gameDir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($gameDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($gameDir);
        }
        db_query('DELETE FROM games WHERE id = ?', [$deleteId]);
        db_query('DELETE FROM results WHERE game_id = ?', [$deleteId]);
        header('Location: /profile.php');
        exit;
    }
}

// Получаем игры пользователя
$gamesStmt = db_query('SELECT id, title, path, icon_path, description, created_at FROM games WHERE user_id = ? ORDER BY created_at DESC', [$userId]);
$myGames = $gamesStmt->fetchAll();

// Получаем результаты пользователя (LEFT JOIN для сохранённых результатов)
$resStmt = db_query("
    SELECT r.id, r.game_id, r.score, r.meta, r.played_at, g.title
    FROM results r
    LEFT JOIN games g ON r.game_id = g.id
    WHERE r.user_id = ?
    ORDER BY r.played_at DESC
", [$userId]);
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

    <!-- Блок уведомлений -->
    <?php if(!empty($messages)): ?>
        <div class="user-messages" style="border:1px solid #f00; padding:10px; margin-bottom:20px; background:#fee;">
            <h3>Уведомления:</h3>
            <ul>
                <?php foreach($messages as $m): ?>
                    <li><?= htmlspecialchars($m['message']) ?> <small style="color:#666;">(<?= $m['created_at'] ?>)</small></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

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
                        <div class="game-info">
                            <strong><?= htmlspecialchars($g['title']) ?></strong>
                            <?php if(!empty($g['description'])): ?>
                                <p class="game-description"><?= htmlspecialchars($g['description']) ?></p>
                            <?php endif; ?>
                            <a href="/play.php?id=<?= (int)$g['id'] ?>" class="game-button">Играть</a>
                        </div>
                        <div class="game-actions" style="display: flex; gap: 10px; align-items: center; white-space: nowrap;">
                            <a href="/edit_game.php?id=<?= (int)$g['id'] ?>" 
                               style="display: inline-block; background-color: #35424a; color: #fff; padding: 6px 12px; border-radius: 3px; text-decoration: none; font-size: 0.9em;"
                               class="edit-btn">Редактировать</a>

                            <form method="post" class="delete-form" onsubmit="return confirm('Вы уверены, что хотите удалить игру?');" 
                                style="margin: 0; display: inline-block;">
                                <input type="hidden" name="delete_game_id" value="<?= (int)$g['id'] ?>">
                                <button type="submit" class="delete-btn" 
                                        style="color: white; background-color: red; border: none; padding: 6px 12px; cursor: pointer; border-radius: 3px; font-weight: bold;">
                                    Удалить
                                </button>
                            </form>
                        </div>

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
                            <td><?= htmlspecialchars($r['title'] ?? 'Игра удалена') ?></td>
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
