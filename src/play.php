<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

$userId = $_SESSION['user']['id'] ?? 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: /'); exit; }

// Обработка лайков/дизлайков через POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['vote'])) {
        $vote = (int)$_POST['vote'];
        if (in_array($vote, [-1,1])) {
            db_query("INSERT INTO game_votes (game_id, user_id, vote) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote=VALUES(vote)", [$id, $userId, $vote]);
        }
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    if (!empty($_POST['comment_text'])) {
        $text = trim($_POST['comment_text']);
        db_query("INSERT INTO game_comments (game_id, user_id, comment) VALUES (?, ?, ?)", [$id, $userId, $text]);
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }
}

// Получаем данные игры
$game = db_query('SELECT g.*, u.username FROM games g JOIN users u ON g.user_id = u.id WHERE g.id = ?', [$id])->fetch();
if (!$game) { http_response_code(404); echo 'Игра не найдена.'; exit; }

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
.comments { margin-top:20px; }
.comment { padding:5px 10px; border-bottom:1px solid #ccc; }
.comment-author { font-weight:bold; }
.comment-text { margin:3px 0; }
.comment-form textarea { width:100%; height:60px; margin-bottom:5px; }
#save-result-btn { margin-top: 10px; display:none; }
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
<div class="game-wrapper">
    <div class="game-info">
        <h2><?= htmlspecialchars($game['title']) ?></h2>
        <p><strong>Автор:</strong> <?= htmlspecialchars($game['username']) ?></p>
        <?php if (!empty($game['description'])): ?>
            <p><strong>Описание:</strong> <?= nl2br(htmlspecialchars($game['description'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($game['rules'])): ?>
            <p><strong>Правила:</strong> <?= nl2br(htmlspecialchars($game['rules'])) ?></p>
        <?php endif; ?>

        <!-- Лайки/дизлайки -->
        <div class="game-votes">
            <form method="post">
                <input type="hidden" name="vote" value="1">
                <button type="submit" class="<?= ((int)$votes['user_vote']===1)?'liked':'' ?>">👍 <?= (int)$votes['likes'] ?></button>
            </form>
            <form method="post">
                <input type="hidden" name="vote" value="-1">
                <button type="submit" class="<?= ((int)$votes['user_vote']===-1)?'disliked':'' ?>">👎 <?= (int)$votes['dislikes'] ?></button>
            </form>
        </div>

        <!-- Комментарии -->
        <div class="comments">
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
    </div>

    <!-- Игровой iframe -->
    <div class="game-frame">
        <iframe id="game-frame" src="<?= htmlspecialchars($rel) ?>" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
        <button id="save-result-btn">Сохранить результат</button>
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

// Сохраняем результат по кнопке
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
</script>
</body>
</html>
