<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

// Анти-кэширование
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user'])) {
    header("Location: /auth/auth.php?mode=login");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $rules = trim($_POST['rules'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $selectedEngine = $_POST['engine'] ?? '';

    if ($title === '') $errors[] = 'Название обязательно для заполнения.';
    if ($rules === '') $errors[] = 'Правила обязательно для заполнения.';
    if (!isset($_FILES['zip']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) $errors[] = 'ZIP с игрой обязателен.';

    if (empty($errors)) {
        $userId = (int)$_SESSION['user']['id'];
        $isAdmin = db_query("SELECT is_admin FROM users WHERE id = ?", [$userId])->fetchColumn() == 1;

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

        if (!mkdir($finalDir, 0775, true) && !is_dir($finalDir)) $errors[] = 'Не удалось создать папку для игры. Проверьте права.';

        // Иконка
        $iconRelPath = null;
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $iconName = preg_replace('/[^a-z0-9.\-_]/i', '_', basename($_FILES['icon']['name']));
            $iconDest = $finalDir . '/icon_' . $iconName;
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $iconDest)) {
                $iconRelPath = 'repository/' . basename($finalDir) . '/icon_' . $iconName;
            }
        }

        $zip = new ZipArchive();
        if ($zip->open($_FILES['zip']['tmp_name']) === true) {

            // Проверяем содержимое ZIP до распаковки
            $fileList = [];
            for ($j = 0; $j < $zip->numFiles; $j++) {
                $fileList[] = $zip->getNameIndex($j);
            }

            if ($selectedEngine === 'js') {
                // JS не должен иметь Build/ в корне
                foreach ($fileList as $f) {
                    if (preg_match('#^Build/#', $f)) {
                        $errors[] = 'Выбрана игра JavaScript, но в архиве обнаружена папка Build/.';
                        break;
                    }
                }
            }

            if ($selectedEngine === 'unity') {
                // Unity должна иметь Build/ в корне
                $hasBuild = false;
                foreach ($fileList as $f) {
                    if (preg_match('#^Build/#', $f)) {
                        $hasBuild = true;
                        break;
                    }
                }
                if (!$hasBuild) $errors[] = 'Выбрана игра Unity, но в архиве не найдена папка Build/.';
            }

            // Если ошибок нет, распаковываем
            if (empty($errors)) {
                $zip->extractTo($finalDir);
                $zip->close();

                // Определяем путь к index.html
                $indexHtml = null;
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($finalDir));
                foreach ($it as $file) {
                    if (preg_match('/^index(\.[a-z0-9]+)?$/i', $file->getFilename())) {
                        $indexHtml = $file->getPathname();
                        break;
                    }
                }

                if ($indexHtml === null && $selectedEngine === 'js') {
                    $errors[] = 'В архиве не найден index.html.';
                } else {
                    $relativePath = 'repository/' . basename($finalDir);
                    if ($indexHtml !== $finalDir) {
                        $sub = str_replace($finalDir, '', dirname($indexHtml));
                        $relativePath = rtrim($relativePath, '/') . str_replace('\\', '/', $sub);
                    }

                    $isSystem = $isAdmin ? 1 : 0;

                    db_query("INSERT INTO games (user_id, title, description, rules, engine, path, icon_path, is_system) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$userId, $title, $description, $rules, $selectedEngine, $relativePath, $iconRelPath, $isSystem]);

                    header('Location: /');
                    exit;
                }
            } else {
                $zip->close();
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

<footer>
    <p>© <?= date("Y") ?> Мини-игры</p>
</footer>

<script src="/assets/js/add_game_hints.js"></script>
</body>
</html>
