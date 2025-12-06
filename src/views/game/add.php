<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить игру</title>
    <link rel="stylesheet" href="/assets/css/add_game.css">
</head>
<body>
<header>
    <h1>Добавить игру</h1>
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

        <form method="post" action="/game/add" enctype="multipart/form-data" class="game-form">
            <label>Название (обязательно):<br><input type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"></label>

            <label>Тип игры:</label><br>
                <select name="engine" required>
                    <option value="js" <?= (isset($_POST['engine']) && $_POST['engine']==='js')?'selected':'' ?>>JavaScript</option>
                    <option value="unity" <?= (isset($_POST['engine']) && $_POST['engine']==='unity')?'selected':'' ?>>Unity WebGL</option>
                </select>
                <br><br>

            <label>Правила (обязательно):<br><textarea name="rules" required><?= htmlspecialchars($_POST['rules'] ?? '') ?></textarea></label>
            <label>Описание (опционально):<br><textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea></label>
            <label>Иконка (png/jpg) (опционально):<br><input type="file" name="icon" accept="image/*"></label>
            <label>ZIP с игрой (обязательно):<br><input type="file" name="zip" accept=".zip" required></label>
            <button type="submit">Добавить игру</button>
        </form>
    </div>

    <div class="hint-column">
        <div id="hint"></div>
    </div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/footer.php'; ?>

<script src="/assets/js/add_game_hints.js"></script>
</body>
</html>
