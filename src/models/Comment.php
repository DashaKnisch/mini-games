<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/lib/database.php';

class Comment {
    public static function getByGame(int $gameId): array {
        $stmt = db_query("
            SELECT c.comment, c.created_at, u.username
            FROM game_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.game_id=? ORDER BY c.created_at ASC
        ", [$gameId]);
        return $stmt->fetchAll();
    }
    
    public static function create(int $gameId, int $userId, string $text): bool {
        db_query("INSERT INTO game_comments (game_id, user_id, comment) VALUES (?, ?, ?)", [$gameId, $userId, $text]);
        return true;
    }
}
