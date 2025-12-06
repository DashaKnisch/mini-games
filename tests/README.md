# Тесты проекта

## Быстрый старт

```bash
# 1. Установка зависимостей
docker exec -it php-mini-games-service-web-1 bash
composer install

# 2. Запуск всех тестов
./vendor/bin/phpunit

# 3. Запуск с подробным выводом
./vendor/bin/phpunit --verbose --testdox
```

## Что покрыто тестами

### ✅ Модели (Unit тесты)

**UserModelTest** - 6 тестов
- Создание пользователя
- Поиск по username
- Поиск по ID
- Проверка пароля (правильный/неправильный)
- Проверка прав администратора

**GameModelTest** - 6 тестов
- Создание игры
- Получение всех игр
- Поиск по ID
- Обновление игры
- Получение голосов
- Удаление игры

**VoteModelTest** - 3 теста
- Сохранение лайка
- Сохранение дизлайка
- Изменение голоса

### ✅ Интеграционные сценарии

**AuthFlowTest** - 3 теста
- Полный цикл регистрации и входа
- Попытка дублирования username
- Проверка прав доступа

**GameFlowTest** - 2 теста
- Полный жизненный цикл игры (создание → голосование → комментарии → результаты → обновление → удаление)
- Получение игр пользователя

## Структура

```
tests/
├── bootstrap.php           # Инициализация тестов
├── Unit/                   # Юнит-тесты моделей
│   ├── UserModelTest.php
│   ├── GameModelTest.php
│   └── VoteModelTest.php
└── Integration/            # Интеграционные тесты
    ├── AuthFlowTest.php
    └── GameFlowTest.php
```

## Примеры запуска

```bash
# Только юнит-тесты
./vendor/bin/phpunit --testsuite Unit

# Только интеграционные
./vendor/bin/phpunit --testsuite Integration

# Конкретный файл
./vendor/bin/phpunit tests/Unit/UserModelTest.php

# Конкретный тест
./vendor/bin/phpunit --filter testCreateUser

# С покрытием кода
./vendor/bin/phpunit --coverage-text
```

## Ожидаемый результат

```
PHPUnit 10.0.0

Unit Tests
 ✔ Create user
 ✔ Find by username
 ✔ Find non existent user
 ✔ Verify correct password
 ✔ Verify incorrect password
 ✔ Is admin
 ✔ Create game
 ✔ Get all games
 ✔ Find game by id
 ✔ Update game
 ✔ Get votes
 ✔ Delete game
 ✔ Save like
 ✔ Save dislike
 ✔ Change vote

Integration Tests
 ✔ Registration and login flow
 ✔ Duplicate username
 ✔ User permissions
 ✔ Complete game lifecycle
 ✔ Get user games

Time: 00:02.456, Memory: 12.00 MB

OK (20 tests, 65 assertions)
```

## Подробная документация

См. [TESTING_GUIDE.md](../TESTING_GUIDE.md) для полного руководства по тестированию.
