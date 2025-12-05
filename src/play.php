<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

$userId = $_SESSION['user']['id'] ?? 0;

// Проверка, является ли текущий пользователь админом
$isAdmin = db_query("SELECT is_admin FROM users WHERE id = ?", [$userId])->fetchColumn() == 1;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: /'); exit; }

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Лайки/дизлайки
    if (isset($_POST['vote'])) {
        $vote = (int)$_POST['vote'];
        if (in_array($vote, [-1,1])) {
            db_query("INSERT INTO game_votes (game_id, user_id, vote) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote=VALUES(vote)", [$id, $userId, $vote]);
        }
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    // Комментарии
    if (!empty($_POST['comment_text'])) {
        $text = trim($_POST['comment_text']);
        db_query("INSERT INTO game_comments (game_id, user_id, comment) VALUES (?, ?, ?)", [$id, $userId, $text]);
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    // Удаление игры админом (только для обычных игр)
    if ($isAdmin && isset($_POST['delete_game'], $_POST['delete_reason'])) {
        $reason = trim($_POST['delete_reason']);
        if ($reason !== '') {
            $gameData = db_query("SELECT user_id, is_system FROM games WHERE id = ?", [$id])->fetch();
            if ($gameData && (int)$gameData['is_system'] === 0) {
                $authorId = $gameData['user_id'];
                db_query("INSERT INTO user_messages (user_id, message) VALUES (?, ?)", [$authorId, "Ваша игра была удалена по причине: $reason"]);
                db_query("DELETE FROM games WHERE id = ?", [$id]);
            }
            header("Location: /"); exit;
        }
    }
}

// Получаем данные игры
$game = db_query('SELECT g.*, u.username FROM games g JOIN users u ON g.user_id = u.id WHERE g.id = ?', [$id])->fetch();
if (!$game) { http_response_code(404); echo 'Игра не найдена.'; exit; }

// Определяем, системная ли игра
$isSystemGame = (int)$game['is_system'] === 1;

// Лайки/дизлайки
$votes = db_query("
    SELECT 
        IFNULL(SUM(CASE WHEN vote=1 THEN 1 ELSE 0 END),0) AS likes,
        IFNULL(SUM(CASE WHEN vote=-1 THEN 1 ELSE 0 END),0) AS dislikes,
        COALESCE((SELECT vote FROM game_votes WHERE game_id=? AND user_id=?),0) AS user_vote
    FROM game_votes WHERE game_id=?
", [$id, $userId, $id])->fetch();

// Комментарии
$comments = db_query("
    SELECT c.comment, c.created_at, u.username
    FROM game_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.game_id=? ORDER BY c.created_at ASC
", [$id])->fetchAll();

// Топ-3 результатов
$topResults = db_query("
    SELECT r.score, u.username 
    FROM results r
    JOIN users u ON r.user_id = u.id
    WHERE r.game_id = ?
    ORDER BY r.score DESC, r.played_at ASC
    LIMIT 3
", [$id])->fetchAll();

// Путь к index.html игры
$gamePath = rtrim($game['path'], '/');
$absPath = realpath(__DIR__ . '/' . $gamePath);
$indexHtml = null;
if ($absPath && is_dir($absPath)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absPath));
    foreach ($it as $file) {
        if (preg_match('/^index(\.[a-z0-9]+)?$/i', $file->getFilename())) {
            $indexHtml = $file->getPathname();
            break;
        }
    }
}
if (!$indexHtml) { echo '<h2>Игра недоступна</h2><p>В каталоге игры отсутствует index.html.</p>'; exit; }
$projectRoot = realpath(__DIR__);
$rel = '/' . ltrim(str_replace('\\','/', substr($indexHtml, strlen($projectRoot))), '/');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($game['title']) ?> — Мини-игры</title>
<link rel="stylesheet" href="/assets/css/play.css">
<style>
.comments, #rating-container { margin-top:20px; }
.comment { padding:5px 10px; border-bottom:1px solid #ccc; }
.comment-author { font-weight:bold; }
.comment-text { margin:3px 0; }
.comment-form textarea { width:100%; height:60px; margin-bottom:5px; }
#save-result-btn { margin-top: 10px; display:none; }

/* Админская форма удаления */
.admin-delete-form { display:none; margin-top:10px; border:1px solid #f00; padding:10px; background:#fee; }
.admin-delete-form textarea { width:100%; height:60px; margin-bottom:5px; }
.admin-delete-form button { margin-right:5px; }

.game-interactions { margin-top:20px; display:flex; align-items:center; gap:10px; }
.game-interactions button { padding:5px 10px; cursor:pointer; }
.likes-dislikes { display:flex; align-items:center; gap:5px; }
</style>
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
<div style="margin-bottom: 15px;">
    <button id="back-btn" style="padding:5px 10px;">← Назад</button>
</div>
<div class="game-wrapper">
    <div class="game-info">
        <h2><?= htmlspecialchars($game['title']) ?></h2>
        <p><strong>Автор:</strong> <?= htmlspecialchars($game['username']) ?></p>
        <p><strong>Тип игры:</strong> <?= ($game['engine'] === 'unity') ? 'Unity WebGL' : 'JavaScript' ?></p>
        <?php if (!empty($game['rules'])): ?>
            <p><strong>Правила:</strong> <?= nl2br(htmlspecialchars($game['rules'])) ?></p>
        <?php endif; ?>

        <!-- Лайки/дизлайки + кнопка удалить админом -->
        <div class="game-interactions">
            <div class="likes-dislikes">
                <form method="post">
                    <input type="hidden" name="vote" value="1">
                    <button type="submit" class="<?= ((int)$votes['user_vote']===1)?'liked':'' ?>">👍 <?= (int)$votes['likes'] ?></button>
                </form>
                <form method="post">
                    <input type="hidden" name="vote" value="-1">
                    <button type="submit" class="<?= ((int)$votes['user_vote']===-1)?'disliked':'' ?>">👎 <?= (int)$votes['dislikes'] ?></button>
                </form>
            </div>

            <?php if ($isAdmin && !$isSystemGame): ?>
            <button id="show-delete-form-btn" style="background:#f99;">Удалить игру</button>
            <?php endif; ?>
        </div>

        <!-- Админская форма удаления -->
        <?php if ($isAdmin && !$isSystemGame): ?>
        <div class="admin-delete-form">
            <form method="post">
                <textarea name="delete_reason" placeholder="Введите причину удаления" required></textarea>
                <input type="hidden" name="delete_game" value="1">
                <button type="submit">Удалить</button>
                <button type="button" id="cancel-delete-btn">Отмена</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Контейнер переключения комментариев/рейтинга -->
        <div class="game-interactions">
            <button id="show-comments-btn">Комментарии</button>
            <button id="show-rating-btn">Рейтинг</button>
        </div>

        <!-- Контейнер комментариев -->
        <div id="comments-container" style="margin-top:10px;">
            <form method="post" class="comment-form">
                <textarea name="comment_text" placeholder="Оставьте комментарий..."></textarea>
                <button type="submit">Отправить</button>
            </form>

            <?php foreach($comments as $c): ?>
                <div class="comment">
                    <div class="comment-author"><?= htmlspecialchars($c['username']) ?></div>
                    <div class="comment-text"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                    <div class="comment-date"><?= $c['created_at'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Контейнер рейтинга -->
        <div id="rating-container" style="margin-top:10px; display:none;">
            <h3>Топ 3 лучших результатов</h3>
            <?php if(empty($topResults)): ?>
                <p>Результаты пока отсутствуют.</p>
            <?php else: ?>
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #ccc; padding:5px;">Игрок</th>
                            <th style="border:1px solid #ccc; padding:5px;">Очки</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($topResults as $r): ?>
                            <tr>
                                <td style="border:1px solid #ccc; padding:5px;"><?= htmlspecialchars($r['username']) ?></td>
                                <td style="border:1px solid #ccc; padding:5px;"><?= (int)$r['score'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

    <!-- Игровой iframe -->
    <div class="game-frame" style="margin-top:15px;">
        <iframe id="game-frame" src="<?= htmlspecialchars($rel) ?>" sandbox="allow-scripts allow-same-origin allow-forms" style="width:100%; height:500px;"></iframe>
        <button id="save-result-btn">Сохранить результат</button>
        <button id="restart-game-btn" style="margin-left:10px;">Начать заново</button>
    </div>
</div>
</main>

<footer>
<p>© <?= date("Y") ?> Мини-игры</p>
</footer>

<script>
let lastGameResult = null;

// Ловим результат из iframe
window.addEventListener('message', function(event) {
    if (!event.data || event.data.type !== 'game_result') return;
    lastGameResult = {
        game_id: <?= $id ?>,
        score: event.data.score,
        meta: event.data.meta || null
    };
    document.getElementById('save-result-btn').style.display = 'inline-block';
});

// Сохраняем результат
document.getElementById('save-result-btn').addEventListener('click', function() {
    if (!lastGameResult) return;
    fetch('/save_result.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(lastGameResult)
    }).then(r => r.json())
      .then(resp => {
          if(resp.ok){
              alert('Результат сохранён!');
              document.getElementById('save-result-btn').style.display = 'none';
              lastGameResult = null;
          } else {
              alert('Ошибка сохранения результата');
          }
      });
});

// Кнопка назад
document.getElementById('back-btn').addEventListener('click', function() {
    window.location.href = '/';
});

// Перезапуск игры
document.getElementById('restart-game-btn').addEventListener('click', function() {
    const iframe = document.getElementById('game-frame');
    const src = iframe.src;
    iframe.src = '';
    setTimeout(() => { iframe.src = src; }, 50);
});

// Админ: показать/скрыть форму удаления
<?php if ($isAdmin && !$isSystemGame): ?>
document.getElementById('show-delete-form-btn').addEventListener('click', function() {
    document.querySelector('.admin-delete-form').style.display = 'block';
});
document.getElementById('cancel-delete-btn').addEventListener('click', function() {
    document.querySelector('.admin-delete-form').style.display = 'none';
});
<?php endif; ?>

// Переключение комментарии/рейтинг
document.getElementById('show-comments-btn').addEventListener('click', function() {
    document.getElementById('comments-container').style.display = 'block';
    document.getElementById('rating-container').style.display = 'none';
});
document.getElementById('show-rating-btn').addEventListener('click', function() {
    document.getElementById('comments-container').style.display = 'none';
    document.getElementById('rating-container').style.display = 'block';
});
</script>
</body>
</html>
