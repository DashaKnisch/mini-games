<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мини-игры — Главная</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/header.php'; ?>

<main class="container">
    <h2>Системные игры</h2>
    <?php if (empty($systemGames)): ?>
        <p class="no-games">Системные игры отсутствуют.</p>
    <?php else: ?>
        <div class="games-list">
            <?php foreach ($systemGames as $g): ?>
                <div class="game-card">
                    <?php if (!empty($g['icon_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($g['icon_path'], '/'))): ?>
                        <img class="game-icon" src="/<?= htmlspecialchars(ltrim($g['icon_path'], '/')); ?>" alt="icon">
                    <?php else: ?>
                        <div class="game-icon placeholder">ICON</div>
                    <?php endif; ?>

                    <div class="game-info">
                        <h3><?= htmlspecialchars($g['title']) ?></h3>
                        <p class="game-author">Автор: <?= htmlspecialchars($g['username']) ?></p>

                        <a class="game-button" href="/game/play/<?= (int)$g['id'] ?>">Играть</a>

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

    <h2>Игры пользователей</h2>
    <?php if (empty($userGames)): ?>
        <p class="no-games">Игры пользователей отсутствуют.</p>
    <?php else: ?>
        <div class="games-list">
            <?php foreach ($userGames as $g): ?>
                <div class="game-card">
                    <?php if (!empty($g['icon_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($g['icon_path'], '/'))): ?>
                        <img class="game-icon" src="/<?= htmlspecialchars(ltrim($g['icon_path'], '/')); ?>" alt="icon">
                    <?php else: ?>
                        <div class="game-icon placeholder">ICON</div>
                    <?php endif; ?>

                    <div class="game-info">
                        <h3><?= htmlspecialchars($g['title']) ?></h3>
                        <p class="game-author">Автор: <?= htmlspecialchars($g['username']) ?></p>

                        <a class="game-button" href="/game/play/<?= (int)$g['id'] ?>">Играть</a>

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

<?php include $_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/footer.php'; ?>

</body>
</html>
