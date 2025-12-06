<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/lib/database.php';

class User {
    public static function findByUsername(string $username): ?array {
        $stmt = db_query("SELECT * FROM users WHERE username = ?", [$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    public static function findById(int $id): ?array {
        $stmt = db_query("SELECT * FROM users WHERE id = ?", [$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    public static function create(string $username, string $password): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db_query("INSERT INTO users (username, password_hash) VALUES (?, ?)", [$username, $hash]);
        return (int)getPDO()->lastInsertId();
    }
    
    public static function verifyPassword(array $user, string $password): bool {
        return password_verify($password, $user['password_hash']);
    }
    
    public static function isAdmin(int $userId): bool {
        $stmt = db_query("SELECT is_admin FROM users WHERE id = ?", [$userId]);
        return $stmt->fetchColumn() == 1;
    }
}
