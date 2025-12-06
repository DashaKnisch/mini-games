# Как работает приложение

## Архитектура: MVC + Front Controller

### Поток запроса

```
Браузер → Apache → .htaccess → index.php → Router → Controller → Model → Database
                                                          ↓
                                                        View → HTML → Браузер
```

## 1. Точка входа (Front Controller)

**Файл:** `public/index.php`

```php
// Подключаем все необходимые классы
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/core/Router.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/HomeController.php';
// ...

// Создаём роутер
$router = new Router();

// Регистрируем маршруты
$router->get('/', 'HomeController', 'index');
$router->get('/game/play/{id}', 'GameController', 'play');

// Обрабатываем запрос
$router->dispatch();
```

**Что происходит:**
1. Apache перенаправляет все запросы на `index.php` (через `.htaccess`)
2. Загружаются все классы
3. Создаётся роутер и регистрируются маршруты
4. `dispatch()` находит подходящий маршрут и вызывает контроллер

## 2. Маршрутизация (Router)

**Файл:** `src/core/Router.php`

```php
class Router {
    public function dispatch(): void {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);
            
            if (preg_match($pattern, $requestUri, $matches)) {
                $controller = new $route['controller']();
                call_user_func_array([$controller, $route['action']], $matches);
                return;
            }
        }
    }
}
```

**Пример:**
- URL: `/game/play/5`
- Паттерн: `/game/play/{id}`
- Regex: `#^/game/play/([a-zA-Z0-9_-]+)$#`
- Результат: вызов `GameController::play('5')`

## 3. Контроллер (Controller)

**Файл:** `src/controllers/GameController.php`

```php
class GameController extends Controller {
    public function play(string $id): void {
        // 1. Проверка авторизации
        $user = $this->requireAuth();
        
        // 2. Получение данных из моделей
        $game = Game::findById((int)$id);
        $votes = Game::getVotes($gameId, $userId);
        $comments = Comment::getByGame($gameId);
        
        // 3. Передача данных в представление
        $this->view('game/play', [
            'game' => $game,
            'votes' => $votes,
            'comments' => $comments
        ]);
    }
}
```

**Ответственность контроллера:**
- Валидация входных данных
- Вызов методов моделей
- Передача данных в представление
- Обработка ошибок

## 4. Модель (Model)

**Файл:** `src/models/Game.php`

```php
class Game {
    public static function findById(int $id): ?array {
        $stmt = db_query(
            'SELECT g.*, u.username FROM games g 
             JOIN users u ON g.user_id = u.id 
             WHERE g.id = ?', 
            [$id]
        );
        $game = $stmt->fetch();
        return $game ?: null;
    }
}
```

**Ответственность модели:**
- Работа с базой данных
- Бизнес-логика данных
- Валидация на уровне данных
- Возврат структурированных данных

## 5. Представление (View)

**Файл:** `src/views/game/play.php`

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($game['title']) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($game['title']) ?></h1>
    <p>Автор: <?= htmlspecialchars($game['username']) ?></p>
    
    <div class="votes">
        👍 <?= (int)$votes['likes'] ?>
        👎 <?= (int)$votes['dislikes'] ?>
    </div>
</body>
</html>
```

**Ответственность представления:**
- Отображение данных
- HTML разметка
- Минимум PHP логики
- Экранирование данных (`htmlspecialchars`)

## 6. База данных (Database)

**Файл:** `src/lib/database.php`

```php
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    
    $dsn = sprintf('mysql:host=%s;dbname=%s', 
        getenv('DB_HOST'), 
        getenv('DB_NAME')
    );
    
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    return $pdo;
}

function db_query(string $sql, array $params = []) {
    $stmt = getPDO()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
```

**Особенности:**
- Singleton паттерн для PDO
- Prepared statements (защита от SQL injection)
- Автоматическое подключение
- Обработка ошибок

## Примеры работы

### Пример 1: Просмотр игры

**1. Пользователь открывает:** `http://localhost:8080/game/play/5`

**2. Apache + .htaccess:**
```apache
RewriteRule ^(.*)$ index.php [QSA,L]
```
Перенаправляет на `index.php`

**3. Router:**
```php
$router->get('/game/play/{id}', 'GameController', 'play');
```
Находит маршрут и вызывает `GameController::play('5')`

**4. Controller:**
```php
public function play(string $id): void {
    $user = $this->requireAuth(); // Проверка авторизации
    $game = Game::findById((int)$id); // Получение игры
    $this->view('game/play', ['game' => $game]); // Отображение
}
```

**5. Model:**
```php
public static function findById(int $id): ?array {
    return db_query('SELECT * FROM games WHERE id = ?', [$id])->fetch();
}
```

**6. View:**
```php
<h1><?= htmlspecialchars($game['title']) ?></h1>
```

**7. Результат:** HTML страница с игрой

### Пример 2: Добавление комментария

**1. Пользователь отправляет форму:**
```html
<form method="post">
    <textarea name="comment_text">Отличная игра!</textarea>
    <button type="submit">Отправить</button>
</form>
```

**2. Controller обрабатывает POST:**
```php
if (!empty($_POST['comment_text'])) {
    $text = trim($_POST['comment_text']);
    Comment::create($gameId, $userId, $text);
    $this->redirect($_SERVER['REQUEST_URI']);
}
```

**3. Model сохраняет в БД:**
```php
public static function create(int $gameId, int $userId, string $text): bool {
    db_query(
        "INSERT INTO game_comments (game_id, user_id, comment) VALUES (?, ?, ?)",
        [$gameId, $userId, $text]
    );
    return true;
}
```

**4. Redirect:** Перезагрузка страницы с новым комментарием

### Пример 3: API запрос (сохранение результата)

**1. JavaScript отправляет:**
```javascript
fetch('/api/save-result', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        game_id: 5,
        score: 100,
        meta: {level: 10}
    })
})
```

**2. Router:**
```php
$router->post('/api/save-result', 'ApiController', 'saveResult');
```

**3. ApiController:**
```php
public function saveResult(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    Result::save($userId, $data['game_id'], $data['score']);
    $this->json(['ok' => true]);
}
```

**4. Ответ:** `{"ok": true}`

## Безопасность

### 1. SQL Injection - защита через Prepared Statements

**❌ Плохо:**
```php
$sql = "SELECT * FROM users WHERE username = '$username'";
```

**✅ Хорошо:**
```php
db_query("SELECT * FROM users WHERE username = ?", [$username]);
```

### 2. XSS - защита через htmlspecialchars

**❌ Плохо:**
```php
<h1><?= $game['title'] ?></h1>
```

**✅ Хорошо:**
```php
<h1><?= htmlspecialchars($game['title']) ?></h1>
```

### 3. CSRF - защита через проверку сессии

```php
protected function requireAuth(): array {
    if (!isset($_SESSION['user'])) {
        $this->redirect('/auth/login');
    }
    return $_SESSION['user'];
}
```

### 4. Password Hashing

```php
// Хеширование
$hash = password_hash($password, PASSWORD_DEFAULT);

// Проверка
password_verify($password, $hash);
```

## Тестирование

### Unit тесты - тестируют модели

```php
public function testCreateUser(): void {
    // Arrange
    $username = 'test_user';
    $password = 'password123';
    
    // Act
    $userId = User::create($username, $password);
    
    // Assert
    $this->assertGreaterThan(0, $userId);
    $user = User::findById($userId);
    $this->assertEquals($username, $user['username']);
}
```

### Integration тесты - тестируют flow

```php
public function testGameLifecycle(): void {
    // Создание
    $gameId = Game::create([...]);
    
    // Голосование
    Vote::save($gameId, $userId, 1);
    
    // Комментарий
    Comment::create($gameId, $userId, 'Great!');
    
    // Результат
    Result::save($userId, $gameId, 100);
    
    // Удаление
    Game::delete($gameId);
}
```

## Docker окружение

```yaml
services:
  web:
    volumes:
      - ./public:/var/www/html        # Document root
      - ./src:/var/www/html/src        # Исходники
      - ./repository:/var/www/html/repository  # Игры
```

**Структура в контейнере:**
```
/var/www/html/              (public/)
├── index.php
├── .htaccess
├── src/                    (монтируется)
│   ├── core/
│   ├── models/
│   ├── controllers/
│   └── views/
└── repository/             (монтируется)
    └── game-1/
        └── index.html
```

## Заключение

**MVC разделяет ответственность:**
- **Model** - данные и бизнес-логика
- **View** - отображение
- **Controller** - координация

**Front Controller обеспечивает:**
- Единую точку входа
- Централизованную маршрутизацию
- Безопасность

**Тесты гарантируют:**
- Корректность работы
- Защиту от регрессий
- Документацию кода

Всё работает вместе для создания безопасного, тестируемого и масштабируемого приложения!
