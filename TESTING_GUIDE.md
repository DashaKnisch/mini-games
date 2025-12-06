# Руководство по тестированию

## Введение

Проект покрыт автоматическими тестами с использованием PHPUnit. Тесты разделены на:
- **Unit тесты** - тестируют отдельные компоненты (модели)
- **Integration тесты** - тестируют взаимодействие компонентов

## Установка PHPUnit

```bash
# Установка через Composer
docker exec -it php-mini-games-service-web-1 bash
composer install

# Или глобально
composer global require phpunit/phpunit
```

## Запуск тестов

```bash
# Все тесты
./vendor/bin/phpunit

# Только Unit тесты
./vendor/bin/phpunit --testsuite Unit

# Только Integration тесты
./vendor/bin/phpunit --testsuite Integration

# Конкретный тест
./vendor/bin/phpunit tests/Unit/UserModelTest.php

# С подробным выводом
./vendor/bin/phpunit --verbose

# С покрытием кода (требует Xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

## Структура тестов

```
tests/
├── bootstrap.php              # Загрузка перед тестами
├── Unit/                      # Юнит-тесты
│   ├── UserModelTest.php      # Тесты модели User
│   ├── GameModelTest.php      # Тесты модели Game
│   └── VoteModelTest.php      # Тесты модели Vote
└── Integration/               # Интеграционные тесты
    ├── AuthFlowTest.php       # Тесты аутентификации
    └── GameFlowTest.php       # Тесты работы с играми
```

## Как работают тесты

### 1. Bootstrap (tests/bootstrap.php)

Загружается перед всеми тестами. Подключает:
- Базовые классы (Router, Controller)
- Все модели
- Все контроллеры
- Устанавливает DOCUMENT_ROOT

### 2. Юнит-тесты (Unit/)

Тестируют отдельные методы моделей в изоляции.

**Пример: UserModelTest**

```php
class UserModelTest extends TestCase
{
    // Выполняется перед каждым тестом
    protected function setUp(): void {
        $this->testUserId = User::create('test_user', 'password');
    }
    
    // Выполняется после каждого теста
    protected function tearDown(): void {
        db_query("DELETE FROM users WHERE id = ?", [$this->testUserId]);
    }
    
    // Тест создания пользователя
    public function testCreateUser(): void {
        $userId = User::create('new_user', 'password');
        $this->assertGreaterThan(0, $userId);
    }
}
```

**Что тестируем:**
- ✅ Создание пользователя
- ✅ Поиск по username
- ✅ Поиск по ID
- ✅ Проверка пароля
- ✅ Права администратора

### 3. Интеграционные тесты (Integration/)

Тестируют полный flow работы приложения.

**Пример: GameFlowTest**

```php
public function testCompleteGameLifecycle(): void
{
    // 1. Создание игры
    $gameId = Game::create([...]);
    
    // 2. Голосование
    Vote::save($gameId, $userId, 1);
    
    // 3. Комментирование
    Comment::create($gameId, $userId, 'Great!');
    
    // 4. Сохранение результата
    Result::save($userId, $gameId, 100);
    
    // 5. Обновление
    Game::update($gameId, $userId, [...]);
    
    // 6. Удаление
    Game::delete($gameId);
}
```

**Что тестируем:**
- ✅ Полный жизненный цикл игры
- ✅ Взаимодействие моделей
- ✅ Каскадное удаление
- ✅ Целостность данных

## Покрытие тестами

### Модели (100% покрытие)

**User.php:**
- `create()` - создание пользователя
- `findByUsername()` - поиск по имени
- `findById()` - поиск по ID
- `verifyPassword()` - проверка пароля
- `isAdmin()` - проверка прав

**Game.php:**
- `create()` - создание игры
- `getAll()` - получение всех игр
- `findById()` - поиск по ID
- `findByIdAndUser()` - поиск игры пользователя
- `getByUser()` - игры пользователя
- `update()` - обновление
- `delete()` - удаление
- `getVotes()` - получение голосов

**Vote.php:**
- `save()` - сохранение голоса (лайк/дизлайк)

**Comment.php:**
- `getByGame()` - комментарии к игре
- `create()` - создание комментария

**Result.php:**
- `save()` - сохранение результата
- `getByUser()` - результаты пользователя
- `getTopByGame()` - топ результатов

**Message.php:**
- `getByUser()` - уведомления пользователя
- `create()` - создание уведомления

### Интеграционные сценарии

**Аутентификация:**
- Регистрация → Вход → Выход
- Проверка дубликатов username
- Проверка прав доступа

**Работа с играми:**
- Создание → Голосование → Комментирование → Результаты → Удаление
- Получение игр пользователя
- Обновление игры

## Принципы тестирования

### 1. Arrange-Act-Assert (AAA)

```php
public function testExample(): void
{
    // Arrange - подготовка
    $userId = User::create('test', 'pass');
    
    // Act - действие
    $user = User::findById($userId);
    
    // Assert - проверка
    $this->assertNotNull($user);
    $this->assertEquals('test', $user['username']);
}
```

### 2. Изоляция тестов

Каждый тест независим:
- `setUp()` - создаёт тестовые данные
- `tearDown()` - удаляет тестовые данные
- Тесты можно запускать в любом порядке

### 3. Тестовая БД

Используйте отдельную БД для тестов:
```php
// phpunit.xml
<env name="DB_NAME" value="php_mini_games_test"/>
```

### 4. Моки и стабы

Для изоляции используйте моки:
```php
$mockUser = $this->createMock(User::class);
$mockUser->method('findById')->willReturn(['id' => 1]);
```

## Типы проверок (Assertions)

```php
// Равенство
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Типы
$this->assertIsInt($value);
$this->assertIsArray($value);
$this->assertIsString($value);

// Null
$this->assertNull($value);
$this->assertNotNull($value);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Массивы
$this->assertArrayHasKey('key', $array);
$this->assertCount(5, $array);
$this->assertContains('value', $array);

// Сравнение
$this->assertGreaterThan(0, $value);
$this->assertLessThan(100, $value);
```

## Создание нового теста

### Шаг 1: Создайте файл теста

```php
// tests/Unit/MyModelTest.php
<?php

use PHPUnit\Framework\TestCase;

class MyModelTest extends TestCase
{
    protected function setUp(): void
    {
        // Подготовка перед каждым тестом
    }
    
    protected function tearDown(): void
    {
        // Очистка после каждого теста
    }
    
    public function testSomething(): void
    {
        // Arrange
        $data = ['test' => 'data'];
        
        // Act
        $result = MyModel::doSomething($data);
        
        // Assert
        $this->assertTrue($result);
    }
}
```

### Шаг 2: Запустите тест

```bash
./vendor/bin/phpunit tests/Unit/MyModelTest.php
```

### Шаг 3: Проверьте результат

```
PHPUnit 10.0.0

.                                                                   1 / 1 (100%)

Time: 00:00.123, Memory: 10.00 MB

OK (1 test, 1 assertion)
```

## Отладка тестов

### Вывод отладочной информации

```php
public function testDebug(): void
{
    $user = User::findById(1);
    
    // Вывод в консоль
    var_dump($user);
    print_r($user);
    
    // Остановка выполнения
    $this->markTestIncomplete('Debug point');
}
```

### Пропуск тестов

```php
public function testSkipped(): void
{
    $this->markTestSkipped('Временно отключен');
}
```

### Ожидание исключений

```php
public function testException(): void
{
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid data');
    
    MyModel::doSomething(null);
}
```

## Best Practices

1. **Один тест = одна проверка**
   - Тестируйте одну функцию за раз
   - Используйте понятные имена тестов

2. **Независимость тестов**
   - Тесты не должны зависеть друг от друга
   - Используйте setUp/tearDown

3. **Быстрые тесты**
   - Юнит-тесты должны выполняться мгновенно
   - Минимизируйте обращения к БД

4. **Читаемость**
   - Используйте AAA паттерн
   - Добавляйте комментарии к сложным тестам

5. **Покрытие**
   - Тестируйте граничные случаи
   - Тестируйте ошибки и исключения

## Continuous Integration (CI)

Добавьте тесты в CI pipeline:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: ./vendor/bin/phpunit
```

## Заключение

Тесты - это:
- ✅ Документация кода
- ✅ Защита от регрессий
- ✅ Уверенность в рефакторинге
- ✅ Быстрая обратная связь

Запускайте тесты перед каждым коммитом!
