<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

// Анти-кэширование, чтобы браузер всегда загружал свежую версию
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

    if ($title === '') $errors[] = 'Название обязательно для заполнения.';
    if ($rules === '') $errors[] = 'Правила обязательно для заполнения.';
    if (!isset($_FILES['zip']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) $errors[] = 'ZIP с игрой обязателен.';

    if (empty($errors)) {
        $userId = (int)$_SESSION['user']['id'];

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

            if ($indexHtml === null) $errors[] = 'В архиве не найден index.html.';
            else {
                $relativePath = 'repository/' . basename($finalDir);
                if ($indexHtml !== $finalDir) {
                    $sub = str_replace($finalDir, '', dirname($indexHtml));
                    $relativePath = rtrim($relativePath, '/') . str_replace('\\','/',$sub);
                }

                db_query(
                    'INSERT INTO games (user_id, title, description, rules, path, icon_path) VALUES (?, ?, ?, ?, ?, ?)',
                    [$userId, $title, $description, $rules, $relativePath, $iconRelPath]
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
            <label>Название (обязательно):<br><input type="text" name="title" required></label>
            <label>Правила (обязательно):<br><textarea name="rules" required></textarea></label>
            <label>Описание (опционально):<br><textarea name="description"></textarea></label>
            <label>Иконка (png/jpg) (опционально):<br><input type="file" name="icon" accept="image/*"></label>
            <label>ZIP с игрой (обязательно):<br><input type="file" name="zip" accept=".zip" required></label>
            <button type="submit">Добавить игру</button>
        </form>
    </div>

    <div class="hint-column">
        <div id="hint">
            <h2>Инструкция</h2>
            <p>Наш сайт поддерживает только простые игры на JavaScript. Чтобы ваша игра корректно загружалась и работала, следуйте этим рекомендациям:</p>
            <ol>
                <li>Создайте вашу игру полностью на HTML/JS/CSS. Убедитесь, что все ресурсы (JS, CSS, изображения) подключены корректно.</li>
                <li>В корне вашей игры обязательно должен быть файл <strong>index.html</strong>. Сайт ищет именно этот файл для отображения игры.</li>
                <li>Все файлы игры должны лежать <strong>в корне ZIP-архива</strong>. Не упаковывайте их в дополнительные подпапки.</li>
                <li>ZIP-архив обязателен для загрузки на сайт.</li>
                <li>Сохраняйте все файлы вашей игры в формате <strong>UTF-8 без BOM (без сигнатуры)</strong>, чтобы русский текст отображался корректно.</li>
            </ol>

            <p>Если вы хотите передавать результаты игры на сайт (например, очки, прогресс, победа/поражение), используйте универсальный код:</p>
<pre>
// Универсальная функция для отправки результатов игры
function sendGameResult(score, meta = null) {
    window.parent.postMessage({
        type: "game_result",
        score: score,
        meta: meta
    }, "*");
}

// Использование:
// sendGameResult(123); // отправляет результат (например, 123 очка)
</pre>
        </div>
    </div>
</main>

<footer>
    <p>© <?= date("Y") ?> Мини-игры</p>
</footer>

<script>
const hints = {
    title: "Придумайте имя для вашей игры. Это поле обязательно для заполнения.",
    rules: "Придумайте правила для вашей игры. Это поле обязательно для заполнения.",
    description: "Добавьте описание вашей игры (опционально).",
    icon: "Загрузите изображение для иконки игры (опционально).",
    zip: "Загрузите ZIP-файл с вашей игрой. Это поле обязательно для заполнения."
};

document.querySelectorAll('.game-form input, .game-form textarea').forEach(el => {
    el.addEventListener('focus', () => {
        document.getElementById('hint').innerHTML = `<p>${hints[el.name]}</p>`;
    });
    el.addEventListener('blur', () => {
        document.getElementById('hint').innerHTML = `
<h2>Инструкция</h2>
<p>Наш сайт поддерживает только простые игры на JavaScript. Чтобы ваша игра корректно загружалась и работала, следуйте этим рекомендациям:</p>
<ol>
    <li>Создайте вашу игру полностью на HTML/JS/CSS. Убедитесь, что все ресурсы (JS, CSS, изображения) подключены корректно.</li>
    <li>В корне вашей игры обязательно должен быть файл <strong>index.html</strong>. Сайт ищет именно этот файл для отображения игры.</li>
    <li>Все файлы игры должны лежать <strong>в корне ZIP-архива</strong>. Не упаковывайте их в дополнительные подпапки.</li>
    <li>ZIP-архив обязателен для загрузки на сайт.</li>
    <li>Сохраняйте все файлы вашей игры в формате <strong>UTF-8 без BOM (без сигнатуры)</strong>, чтобы русский текст отображался корректно.</li>
</ol>
<p>Если вы хотите передавать результаты игры на сайт (например, очки, прогресс, победа/поражение), используйте универсальный код:</p>
<pre>
// Универсальная функция для отправки результатов игры
function sendGameResult(score, meta = null) {
    window.parent.postMessage({
        type: "game_result",
        score: score,
        meta: meta
    }, "*");
}

// Использование:
// sendGameResult(score); // отправляет результат
</pre>
`;
    });
});
</script>
</body>
</html>
