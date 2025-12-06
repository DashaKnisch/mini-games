<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/lib/database.php';

class Vote {
    public static function save(int $gameId, int $userId, int $vote): bool {
        db_query(
            "INSERT INTO game_votes (game_id, user_id, vote) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote=VALUES(vote)",
            [$gameId, $userId, $vote]
        );
        return true;
    }
}
