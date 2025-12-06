<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/lib/database.php';

class Game {
    public static function getAll(int $userId): array {
        $stmt = db_query("
            SELECT g.id, g.title, g.icon_path, g.created_at, g.is_system, u.username,
                IFNULL(SUM(CASE WHEN v.vote=1 THEN 1 ELSE 0 END), 0) AS likes,
                IFNULL(SUM(CASE WHEN v.vote=-1 THEN 1 ELSE 0 END), 0) AS dislikes,
                COALESCE((SELECT vote FROM game_votes WHERE game_id = g.id AND user_id = ?), 0) AS user_vote
            FROM games g
            JOIN users u ON g.user_id = u.id
            LEFT JOIN game_votes v ON g.id = v.game_id
            GROUP BY g.id
            ORDER BY g.created_at DESC
        ", [$userId]);
        return $stmt->fetchAll();
    }
    
    public static function findById(int $id): ?array {
        $stmt = db_query('SELECT g.*, u.username FROM games g JOIN users u ON g.user_id = u.id WHERE g.id = ?', [$id]);
        $game = $stmt->fetch();
        return $game ?: null;
    }
    
    public static function findByIdAndUser(int $id, int $userId): ?array {
        $stmt = db_query('SELECT * FROM games WHERE id = ? AND user_id = ?', [$id, $userId]);
        $game = $stmt->fetch();
        return $game ?: null;
    }
    
    public static function getByUser(int $userId): array {
        $stmt = db_query('SELECT * FROM games WHERE user_id = ? ORDER BY created_at DESC', [$userId]);
        return $stmt->fetchAll();
    }
    
    public static function create(array $data): int {
        db_query(
            "INSERT INTO games (user_id, title, description, rules, engine, path, icon_path, is_system) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'], $data['title'], $data['description'], 
                $data['rules'], $data['engine'], $data['path'], 
                $data['icon_path'], $data['is_system']
            ]
        );
        return (int)getPDO()->lastInsertId();
    }
    
    public static function update(int $id, int $userId, array $data): bool {
        db_query(
            'UPDATE games SET title = ?, description = ?, rules = ?, icon_path = ? WHERE id = ? AND user_id = ?',
            [$data['title'], $data['description'], $data['rules'], $data['icon_path'], $id, $userId]
        );
        return true;
    }
    
    public static function delete(int $id): bool {
        db_query('DELETE FROM games WHERE id = ?', [$id]);
        return true;
    }
    
    public static function getVotes(int $gameId, int $userId): array {
        $stmt = db_query("
            SELECT 
                IFNULL(SUM(CASE WHEN vote=1 THEN 1 ELSE 0 END),0) AS likes,
                IFNULL(SUM(CASE WHEN vote=-1 THEN 1 ELSE 0 END),0) AS dislikes,
                COALESCE((SELECT vote FROM game_votes WHERE game_id=? AND user_id=?),0) AS user_vote
            FROM game_votes WHERE game_id=?
        ", [$gameId, $userId, $gameId]);
        return $stmt->fetch();
    }
}
