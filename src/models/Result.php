<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/lib/database.php';

class Result {
    public static function save(int $userId, int $gameId, int $score, $meta = null): bool {
        db_query(
            'INSERT INTO results (user_id, game_id, score, meta) VALUES (?, ?, ?, ?)',
            [$userId, $gameId, $score, $meta ? json_encode($meta) : null]
        );
        return true;
    }
    
    public static function getByUser(int $userId): array {
        $stmt = db_query("
            SELECT r.id, r.game_id, r.score, r.meta, r.played_at, g.title
            FROM results r
            LEFT JOIN games g ON r.game_id = g.id
            WHERE r.user_id = ?
            ORDER BY r.played_at DESC
        ", [$userId]);
        return $stmt->fetchAll();
    }
    
    public static function getTopByGame(int $gameId, int $limit = 3): array {
        $stmt = db_query("
            SELECT r.score, u.username 
            FROM results r
            JOIN users u ON r.user_id = u.id
            WHERE r.game_id = ?
            ORDER BY r.score DESC, r.played_at ASC
            LIMIT ?
        ", [$gameId, $limit]);
        return $stmt->fetchAll();
    }
}
