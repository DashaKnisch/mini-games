CREATE DATABASE IF NOT EXISTS `php_mini_games` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `php_mini_games`;

-- Таблица пользователей
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица игр
DROP TABLE IF EXISTS `games`;
CREATE TABLE `games` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `rules` TEXT DEFAULT NULL,
  `path` VARCHAR(500) NOT NULL,
  `icon_path` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_games_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица результатов
DROP TABLE IF EXISTS `results`;
CREATE TABLE `results` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `game_id` INT UNSIGNED NOT NULL,
  `score` INT NOT NULL DEFAULT 0,
  `meta` JSON DEFAULT NULL,
  `played_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_game` (`game_id`),
  CONSTRAINT `fk_results_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_results_game` FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица голосов (лайки/дизлайки)
CREATE TABLE IF NOT EXISTS `game_votes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `game_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `vote` TINYINT NOT NULL, -- 1 = лайк, -1 = дизлайк
    UNIQUE KEY `unique_vote` (`game_id`, `user_id`),
    CONSTRAINT `fk_game_votes_game` FOREIGN KEY (`game_id`) REFERENCES games(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_game_votes_user` FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица комментариев
CREATE TABLE IF NOT EXISTS `game_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `game_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_game_comments_game` FOREIGN KEY (`game_id`) REFERENCES games(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_game_comments_user` FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Конец схемы
