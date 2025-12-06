<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать игру — <?= htmlspecialchars($game['title']) ?></title>
    <link rel="stylesheet" href="/assets/css/add_game.css">
</head>
<body>
<header>
    <h1>Редактировать игру</h1>
    <nav>
        <a href="/">Главная</a>
        <a href="/profile">Профиль</a>
        <a href="/auth/logout">Выйти</a>
    </nav>
</header>

<main class="container add-game-container">
    <div class="form-column">
        <?php if ($errors): ?>
            <div class="errors">
                <?php foreach ($errors as $e): ?>
                    <p><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/game/edit/<?= (int)$game['id'] ?>" enctype="multipart/form-data" class="game-form">
            <label>Название (обязательно):<br>
                <input type="text" name="title" value="<?= htmlspecialchars($game['title']) ?>" required>
            </label>
            <label>Правила (обязательно):<br>
                <textarea name="rules" required><?= htmlspecialchars($game['rules']) ?></textarea>
            </label>
            <label>Описание (опционально):<br>
                <textarea name="description"><?= htmlspecialchars($game['description']) ?></textarea>
            </label>
            <label>Иконка (png/jpg) (опционально):<br>
                <input type="file" name="icon" accept="image/*">
            </label>
            <label>ZIP с игрой (опционально, заменяет старую игру):<br>
                <input type="file" name="zip" accept=".zip">
            </label>
            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/footer.php'; ?>
</body>
</html>
