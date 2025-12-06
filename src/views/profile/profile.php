<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль — <?= htmlspecialchars($username) ?></title>
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body>
<header>
    <h1>Профиль пользователя</h1>
    <nav>
        <a href="/">Главная</a>
        <a href="/game/add">Добавить игру</a>
        <a href="/auth/logout">Выйти</a>
    </nav>
</header>

<main class="container">
    <h2><?= htmlspecialchars($username) ?></h2>

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
                            <a href="/game/play/<?= (int)$g['id'] ?>" class="game-button">Играть</a>
                        </div>
                        <div class="game-actions" style="display: flex; gap: 10px; align-items: center; white-space: nowrap;">
                            <a href="/game/edit/<?= (int)$g['id'] ?>" 
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

<?php include $_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/footer.php'; ?>

<script>
function showTab(name){
    document.getElementById('tab-games').style.display = (name==='games')?'block':'none';
    document.getElementById('tab-results').style.display = (name==='results')?'block':'none';
}
</script>
</body>
</html>
