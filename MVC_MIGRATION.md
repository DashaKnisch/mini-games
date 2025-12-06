# Миграция на MVC архитектуру

## Описание

Проект успешно переведён на классическую MVC (Model-View-Controller) архитектуру с использованием паттерна Front Controller.

## Структура проекта

```
php-mini-games-service/
├── public/                      # Document Root (единственная публичная папка)
│   ├── index.php               # Front Controller - единая точка входа
│   └── .htaccess               # Правила маршрутизации Apache
│
├── src/                        # Исходный код приложения
│   ├── core/                   # Ядро фреймворка
│   │   ├── Router.php          # Маршрутизатор с поддержкой параметров
│   │   └── Controller.php      # Базовый контроллер
│   │
│   ├── models/                 # Модели (Data Layer)
│   │   ├── User.php            # Пользователи
│   │   ├── Game.php            # Игры
│   │   ├── Result.php          # Результаты игр
│   │   ├── Vote.php            # Голосование (лайки/дизлайки)
│   │   ├── Comment.php         # Комментарии
│   │   └── Message.php         # Уведомления пользователям
│   │
│   ├── controllers/            # Контроллеры (Business Logic Layer)
│   │   ├── HomeController.php      # Главная страница
│   │   ├── AuthController.php      # Аутентификация
│   │   ├── GameController.php      # Управление играми
│   │   ├── ProfileController.php   # Профиль пользователя
│   │   └── ApiController.php       # API endpoints
│   │
│   ├── views/                  # Представления (Presentation Layer)
│   │   ├── layouts/
│   │   │   ├── header.php      # Шапка сайта
│   │   │   └── footer.php      # Подвал сайта
│   │   ├── home/
│   │   │   └── games_list.php  # Список игр (главная)
│   │   ├── auth/
│   │   │   └── login.php       # Вход/регистрация
│   │   ├── game/
│   │   │   ├── add.php         # Добавление игры
│   │   │   ├── edit.php        # Редактирование игры
│   │   │   └── play.php        # Страница игры
│   │   └── profile/
│   │       └── profile.php     # Профиль пользователя
│   │
│   ├── lib/                    # Вспомогательные библиотеки
│   │   └── database.php        # Работа с БД через PDO
│   │
│   └── assets/                 # Статические ресурсы
│       ├── css/                # Стили
│       └── js/                 # JavaScript
│
├── repository/                 # Загруженные игры (вне public)
│
├── db/
│   └── schema.sql              # Схема базы данных
│
├── docker-compose.yml          # Docker конфигурация
├── php.Dockerfile              # Docker образ PHP
└── README.md                   # Документация
```

## Принципы MVC

### Model (Модель)
**Ответственность:** Работа с данными и бизнес-логика

**Примеры:**
- `User::findByUsername($username)` - поиск пользователя
- `Game::getAll($userId)` - получение всех игр
- `Result::save($userId, $gameId, $score)` - сохранение результата

**Особенности:**
- Статические методы для простоты
- Использование PDO для безопасности
- Возвращают массивы или примитивы

### View (Представление)
**Ответственность:** Отображение данных пользователю

**Примеры:**
- `views/home/games_list.php` - список игр
- `views/game/play.php` - страница игры
- `views/layouts/header.php` - переиспользуемый header

**Особенности:**
- Чистый PHP + HTML
- Минимум логики (только отображение)
- Использование `htmlspecialchars()` для безопасности

### Controller (Контроллер)
**Ответственность:** Обработка запросов и координация Model-View

**Примеры:**
- `HomeController::index()` - главная страница
- `GameController::play($id)` - запуск игры
- `AuthController::login()` - аутентификация

**Особенности:**
- Наследуются от базового `Controller`
- Методы `view()`, `redirect()`, `json()`
- Валидация входных данных

### Router (Маршрутизатор)
**Ответственность:** Связывание URL с контроллерами

**Пример:**
```php
$router->get('/game/play/{id}', 'GameController', 'play');
```

## Маршруты приложения

```php
// Аутентификация
GET  /auth/login          -> AuthController::showLogin()
POST /auth/login          -> AuthController::login()
POST /auth/register       -> AuthController::register()
GET  /auth/logout         -> AuthController::logout()

// Главная
GET  /                    -> HomeController::index()
POST /                    -> HomeController::index() (голосование)

// Профиль
GET  /profile             -> ProfileController::index()
POST /profile             -> ProfileController::index() (удаление игры)

// Игры
GET  /game/add            -> GameController::showAddForm()
POST /game/add            -> GameController::add()
GET  /game/play/{id}      -> GameController::play()
POST /game/play/{id}      -> GameController::play() (комментарии, голоса)
GET  /game/edit/{id}      -> GameController::showEditForm()
POST /game/edit/{id}      -> GameController::edit()

// API
POST /api/save-result     -> ApiController::saveResult()
```

## Преимущества MVC архитектуры

1. **Разделение ответственности**
   - Модели отвечают за данные
   - Контроллеры за логику
   - Представления за отображение

2. **Переиспользование кода**
   - Модели используются в разных контроллерах
   - Layouts переиспользуются во views
   - Базовый Controller предоставляет общие методы

3. **Тестируемость**
   - Каждый слой можно тестировать отдельно
   - Модели не зависят от HTTP
   - Контроллеры легко мокировать

4. **Масштабируемость**
   - Легко добавлять новые функции
   - Понятная структура для команды
   - Простая навигация по коду

5. **Безопасность**
   - Единая точка входа (Front Controller)
   - Валидация в контроллерах
   - Экранирование в представлениях

## Запуск проекта

```bash
# Остановить старые контейнеры
docker-compose down

# Запустить с пересборкой
docker-compose up --build
```

Приложение доступно: **http://localhost:8080**

## Обновление БД

Схема БД обновлена. Добавлены:
- `users.is_admin` - флаг администратора
- `games.engine` - тип игры (js/unity)
- `games.is_system` - системная игра
- Таблица `user_messages` - уведомления

Для применения:
```bash
docker-compose down
docker volume rm php-mini-games-service_db_data
docker-compose up -d
```

## Соответствие MVC паттерну

### ✅ Полное соответствие

**Model:**
- 6 моделей для работы с данными
- Статические методы для простоты
- Инкапсуляция SQL запросов

**View:**
- 8 представлений
- Разделение на layouts
- Минимум PHP логики

**Controller:**
- 5 контроллеров
- Наследование от базового
- Чёткое разделение ответственности

**Router:**
- Централизованная маршрутизация
- Поддержка параметров в URL
- RESTful подход

### Дополнительные паттерны

- **Front Controller** - единая точка входа (public/index.php)
- **Template View** - переиспользуемые layouts
- **Active Record** - модели работают с БД напрямую

## Миграция завершена

Все старые файлы удалены. Проект полностью переведён на MVC архитектуру с сохранением всего функционала.
