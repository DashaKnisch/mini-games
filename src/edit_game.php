<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

if (empty($_SESSION['user'])) {
    header('Location: /auth/auth.php?mode=login');
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$gameId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем игру пользователя
$stmt = db_query('SELECT * FROM games WHERE id = ? AND user_id = ?', [$gameId, $userId]);
$game = $stmt->fetch();

if (!$game) {
    header('Location: /profile.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $rules = trim($_POST['rules'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '') $errors[] = 'Название обязательно для заполнения.';
    if ($rules === '') $errors[] = 'Правила обязательно для заполнения.';

    $iconRelPath = $game['icon_path'];
    $gamePath = $game['path'];

    // Загрузка новой иконки
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $iconName = preg_replace('/[^a-z0-9.\-_]/i', '_', basename($_FILES['icon']['name']));
        $iconDest = __DIR__ . '/' . $gamePath . '/icon_' . $iconName;
        if (move_uploaded_file($_FILES['icon']['tmp_name'], $iconDest)) {
            $iconRelPath = $gamePath . '/icon_' . $iconName;
        }
    }

    // Загрузка нового ZIP (замена игры)
    if (isset($_FILES['zip']) && $_FILES['zip']['error'] === UPLOAD_ERR_OK) {
        $zip = new ZipArchive();
        if ($zip->open($_FILES['zip']['tmp_name']) === true) {
            // Очистка старой папки игры
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__ . '/' . $gamePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            // Извлечение нового ZIP
            $zip->extractTo(__DIR__ . '/' . $gamePath);
            $zip->close();
        } else {
            $errors[] = 'Невозможно открыть ZIP файл.';
        }
    }

    if (empty($errors)) {
        db_query(
            'UPDATE games SET title = ?, description = ?, rules = ?, icon_path = ? WHERE id = ? AND user_id = ?',
            [$title, $description, $rules, $iconRelPath, $gameId, $userId]
        );
        header('Location: /profile.php');
        exit;
    }
}
?>
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
        <a href="/profile.php">Профиль</a>
        <a href="/auth/auth.php?mode=logout">Выйти</a>
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

        <form method="post" enctype="multipart/form-data" class="game-form">
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

<footer>
    <p>© <?= date("Y") ?> Мини-игры</p>
</footer>
</body>
</html>
