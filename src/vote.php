<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/lib/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success'=>false, 'error'=>'Не авторизован']);
    exit;
}

// Проверяем, пришёл ли обычный POST
$data = $_POST;

$gameId = (int)($data['game_id'] ?? 0);
$vote = (int)($data['vote'] ?? 0);

if(!in_array($vote, [-1,1]) || $gameId<=0){
    echo json_encode(['success'=>false, 'error'=>'Неверные данные']);
    exit;
}

$userId = (int)$_SESSION['user']['id'];

try {
    // Сохраняем или обновляем голос
    db_query("
        INSERT INTO game_votes (game_id, user_id, vote)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE vote=VALUES(vote)
    ", [$gameId, $userId, $vote]);

    // Получаем обновлённые суммы
    $stmt = db_query("SELECT
        SUM(CASE WHEN vote=1 THEN 1 ELSE 0 END) AS likes,
        SUM(CASE WHEN vote=-1 THEN 1 ELSE 0 END) AS dislikes,
        MAX(CASE WHEN user_id=? THEN vote ELSE 0 END) AS user_vote
        FROM game_votes
        WHERE game_id=?
    ", [$userId, $gameId]);
    $res = $stmt->fetch();

    echo json_encode([
        'success'=>true,
        'likes'=>(int)$res['likes'],
        'dislikes'=>(int)$res['dislikes'],
        'user_vote'=>(int)$res['user_vote']
    ]);
} catch(Exception $e){
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}