<?php
// Простое подключение к базе данных через PDO
// Конфигурация берётся из переменных окружения или используются значения по умолчанию

function get_db_config(): array {
    return [
        'host' => getenv('DB_HOST') ?: 'mysql',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'php_mini_games',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ];
}

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = get_db_config();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);

    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // В простом проекте логируем ошибку и показываем минимальное сообщение
        error_log('DB connection error: ' . $e->getMessage());
        http_response_code(500);
        echo 'Database connection error';
        exit;
    }

    return $pdo;
}

// Маленькая обёртка для удобства
function db_query(string $sql, array $params = []) {
    $stmt = getPDO()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

?>
