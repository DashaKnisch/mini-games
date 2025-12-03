<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';
header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;
$gameId = (int)($data['game_id'] ?? 0);

// Проверка валидности game_id
if ($gameId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID игры']);
    exit;
}

try {
    if ($action === 'add') {
        $text = trim($data['text'] ?? '');
        if ($text === '') {
            echo json_encode(['success' => false, 'error' => 'Комментарий не может быть пустым']);
            exit;
        }

        db_query(
            "INSERT INTO game_comments (game_id, user_id, text, created_at) VALUES (?, ?, ?, NOW())",
            [$gameId, $userId, $text]
        );

        echo json_encode(['success' => true]);
        exit;

    } elseif ($action === 'list') {
        $stmt = db_query(
            "SELECT c.id, c.text, c.created_at, u.username 
             FROM game_comments c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.game_id = ? 
             ORDER BY c.created_at ASC",
            [$gameId]
        );
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'comments' => $comments]);
        exit;

    } else {
        echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
