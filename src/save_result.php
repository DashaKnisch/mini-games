<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';

// Проверка авторизации
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Получаем данные из JSON или POST
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$game_id = isset($data['game_id']) ? (int)$data['game_id'] : 0;
$score   = isset($data['score']) ? (int)$data['score'] : 0;
$meta    = isset($data['meta']) ? $data['meta'] : null;

if ($game_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid game_id']);
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// Сохраняем результат в базу
try {
    db_query(
        'INSERT INTO results (user_id, game_id, score, meta) VALUES (?, ?, ?, ?)',
        [$userId, $game_id, $score, $meta ? json_encode($meta) : null]
    );
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error', 'message' => $e->getMessage()]);
}
