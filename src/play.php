<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

// Получаем ID игры
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /');
    exit;
}

// Получаем данные игры из БД
$stmt = db_query('SELECT g.*, u.username FROM games g JOIN users u ON g.user_id = u.id WHERE g.id = ?', [$id]);
$game = $stmt->fetch();
if (!$game) {
    http_response_code(404);
    echo 'Игра не найдена.';
    exit;
}

// Полный путь к папке игры
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

if (!$indexHtml) {
    echo '<h2>Игра недоступна</h2><p>В каталоге игры отсутствует index.html.</p>';
    exit;
}

// Относительный путь для iframe
$projectRoot = realpath(__DIR__);
$rel = str_replace('\\', '/', substr($indexHtml, strlen($projectRoot)));
$rel = '/' . ltrim($rel, '/');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($game['title']) ?> — Мини-игры</title>
    <link rel="stylesheet" href="/assets/css/play.css">
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
    <h2><?= htmlspecialchars($game['title']) ?></h2>
    <p>Автор: <?= htmlspecialchars($game['username']) ?></p>
    
    <?php if (!empty($game['description'])): ?>
        <p class="game-description"><?= nl2br(htmlspecialchars($game['description'])) ?></p>
    <?php endif; ?>

    <div class="game-frame">
        <iframe src="<?= htmlspecialchars($rel) ?>" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
    </div>
</main>

<footer>
    <p>© <?= date("Y") ?> Мини-игры</p>
</footer>

</body>
</html>
