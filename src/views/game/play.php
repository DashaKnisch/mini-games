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
.admin-delete-form { display:none; margin-top:10px; border:1px solid #f00; padding:10px; background:#fee; }
.admin-delete-form textarea { width:100%; height:60px; margin-bottom:5px; }
.admin-delete-form button { margin-right:5px; }
.game-interactions { margin-top:20px; display:flex; align-items:center; gap:10px; }
.game-interactions button { padding:5px 10px; cursor:pointer; }
.likes-dislikes { display:flex; align-items:center; gap:5px; }
</style>
</head>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/header.php'; ?>

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

        <div class="game-interactions">
            <button id="show-comments-btn">Комментарии</button>
            <button id="show-rating-btn">Рейтинг</button>
        </div>

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

    <div class="game-frame" style="margin-top:15px;">
        <iframe id="game-frame" src="<?= htmlspecialchars($gameUrl) ?>" sandbox="allow-scripts allow-same-origin allow-forms" style="width:100%; height:500px;"></iframe>
        <button id="save-result-btn">Сохранить результат</button>
        <button id="restart-game-btn" style="margin-left:10px;">Начать заново</button>
    </div>
</div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/footer.php'; ?>

<script>
let lastGameResult = null;

window.addEventListener('message', function(event) {
    if (!event.data || event.data.type !== 'game_result') return;
    lastGameResult = {
        game_id: <?= $gameId ?>,
        score: event.data.score,
        meta: event.data.meta || null
    };
    document.getElementById('save-result-btn').style.display = 'inline-block';
});

document.getElementById('save-result-btn').addEventListener('click', function() {
    if (!lastGameResult) return;
    fetch('/api/save-result', {
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

document.getElementById('back-btn').addEventListener('click', function() {
    window.location.href = '/';
});

document.getElementById('restart-game-btn').addEventListener('click', function() {
    const iframe = document.getElementById('game-frame');
    const src = iframe.src;
    iframe.src = '';
    setTimeout(() => { iframe.src = src; }, 50);
});

<?php if ($isAdmin && !$isSystemGame): ?>
document.getElementById('show-delete-form-btn').addEventListener('click', function() {
    document.querySelector('.admin-delete-form').style.display = 'block';
});
document.getElementById('cancel-delete-btn').addEventListener('click', function() {
    document.querySelector('.admin-delete-form').style.display = 'none';
});
<?php endif; ?>

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
