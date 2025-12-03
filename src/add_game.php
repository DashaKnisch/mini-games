<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

if (empty($_SESSION['user'])) {
    header('Location: /auth/auth.php?mode=login');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '') {
        $errors[] = 'Укажите название игры.';
    }

    if (!isset($_FILES['zip']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Нужно загрузить ZIP с игрой.';
    }

    if (empty($errors)) {
        $userId = (int)$_SESSION['user']['id'];

        // Генерируем безопасный slug
        $slug = preg_replace('/[^a-z0-9-_]/i', '-', mb_strtolower($title));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'game-' . time();

        $finalDir = __DIR__ . '/repository/' . $slug;
        $i = 1;
        while (file_exists($finalDir)) {
            $finalDir = __DIR__ . '/repository/' . $slug . '-' . $i;
            $i++;
        }

        if (!mkdir($finalDir, 0755, true)) {
            $errors[] = 'Не удалось создать папку для игры.';
        }

        // Иконка
        $iconRelPath = null;
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $iconName = preg_replace('/[^a-z0-9.\-_]/i', '_', basename($_FILES['icon']['name']));
            $iconDest = $finalDir . '/icon_' . $iconName;
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $iconDest)) {
                $iconRelPath = 'repository/' . basename($finalDir) . '/icon_' . $iconName;
            }
        }

        // ZIP
        $zip = new ZipArchive();
        if ($zip->open($_FILES['zip']['tmp_name']) === true) {
            $zip->extractTo($finalDir);
            $zip->close();

            $indexHtml = null;
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($finalDir));
            foreach ($it as $file) {
                if (preg_match('/^index(\.[a-z0-9]+)?$/i', $file->getFilename())) {
                    $indexHtml = $file->getPathname();
                    break;
                }
            }

            if ($indexHtml === null) {
                $errors[] = 'В архиве не найден index.html.';
            } else {
                $relativePath = 'repository/' . basename($finalDir);
                if ($indexHtml !== $finalDir) {
                    $sub = str_replace($finalDir, '', dirname($indexHtml));
                    $relativePath = rtrim($relativePath, '/') . str_replace('\\','/',$sub);
                }

                db_query(
                    'INSERT INTO games (user_id, title, description, path, icon_path) VALUES (?, ?, ?, ?, ?)',
                    [$userId, $title, $description, $relativePath, $iconRelPath]
                );
                header('Location: /');
                exit;
            }
        } else {
            $errors[] = 'Невозможно открыть ZIP файл.';
        }
    }
}
?>
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
        <a href="/profile.php">Профиль</a>
        <a href="/auth/auth.php?mode=logout">Выйти</a>
    </nav>
</header>

<main class="container">
    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="game-form">
        <label>Название (обязательно):<br><input type="text" name="title" required></label><br>
        <label>Описание (опционально):<br><textarea name="description"></textarea></label><br>
        <label>Иконка (png/jpg) (опционально):<br><input type="file" name="icon" accept="image/*"></label><br>
        <label>ZIP с игрой (обязательно):<br><input type="file" name="zip" accept=".zip" required></label><br>
        <button type="submit">Добавить игру</button>
    </form>
</main>

<footer>
    <p>© <?= date("Y") ?> Мини-игры</p>
</footer>
</body>
</html>
