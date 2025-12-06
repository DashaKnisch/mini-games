<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/lib/database.php';

class Message {
    public static function getByUser(int $userId): array {
        $stmt = db_query("SELECT id, message, created_at FROM user_messages WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
        return $stmt->fetchAll();
    }
    
    public static function create(int $userId, string $message): bool {
        db_query("INSERT INTO user_messages (user_id, message) VALUES (?, ?)", [$userId, $message]);
        return true;
    }
}
