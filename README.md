# 🎮 Mini Games Service

> Веб-платформа для размещения и игры в мини-игры с использованием архитектуры MVC

## 📋 Описание

**Mini Games Service** — это полнофункциональная веб-платформа, которая позволяет пользователям:
- Регистрироваться и авторизоваться в системе
- Загружать собственные игры (JavaScript, Unity WebGL)
- Играть в игры других пользователей
- Сохранять результаты и соревноваться в таблице лидеров
- Управлять своими играми через личный профиль

Проект реализован с использованием **MVC архитектуры**, что обеспечивает чистоту кода, легкость поддержки и возможность расширения функциональности.

---

## 🏗️ Архитектура

Проект построен на паттерне **Model-View-Controller (MVC)**:

### 📦 Компоненты

- **Models** — бизнес-логика и работа с данными
  - `User` — управление пользователями
  - `Game` — управление играми
  - `Result` — сохранение и получение результатов

- **Views** — HTML шаблоны для отображения
  - Страницы авторизации
  - Список игр
  - Игровая страница
  - Профиль пользователя

- **Controllers** — обработка HTTP запросов
  - `AuthController` — регистрация и вход
  - `HomeController` — главная страница
  - `GameController` — управление играми
  - `ProfileController` — профиль пользователя
  - `ApiController` — REST API для сохранения результатов

- **Core** — ядро системы
  - `Router` — маршрутизация запросов
  - `Controller` — базовый класс контроллеров
  - `Database` — работа с MySQL через PDO

---

## 📁 Структура проекта

```
php-mini-games-service/
│
├── public/                      # Публичная директория (Document Root)
│   ├── index.php               # Front Controller (единая точка входа)
│   └── .htaccess               # URL rewriting для Apache
│
├── src/                        # Исходный код приложения
│   ├── core/                   # Ядро системы
│   │   ├── Router.php          # Маршрутизатор
│   │   └── Controller.php      # Базовый контроллер
│   │
│   ├── models/                 # Модели данных
│   │   ├── User.php            # Модель пользователя
│   │   ├── Game.php            # Модель игры
│   │   └── Result.php          # Модель результатов
│   │
│   ├── controllers/            # Контроллеры
│   │   ├── AuthController.php
│   │   ├── HomeController.php
│   │   ├── GameController.php
│   │   ├── ProfileController.php
│   │   └── ApiController.php
│   │
│   ├── views/                  # Представления (HTML шаблоны)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── game/
│   │   └── profile/
│   │
│   ├── lib/                    # Библиотеки
│   │   └── database.php        # Работа с БД
│   │
│   └── assets/                 # Статические файлы
│       ├── css/                # Стили
│       └── js/                 # JavaScript
│
├── repository/                 # Файловое хранилище игр
│   ├── 1234/                   # Папка игры (по ID)
│   │   ├── index.html
│   │   ├── script.js
│   │   └── style.css
│   └── ...
│
├── db/
│   └── schema.sql              # Схема базы данных
│
├── tests/                      # Тесты (PHPUnit)
│   ├── Unit/                   # Unit тесты
│   └── Integration/            # Интеграционные тесты
│
├── docker-compose.yml          # Docker конфигурация
├── php.Dockerfile              # Dockerfile для PHP
├── composer.json               # Зависимости PHP
└── phpunit.xml                 # Конфигурация тестов
```

---

## ⚙️ Функциональность

### 👤 Пользователи
- ✅ Регистрация с уникальным username
- ✅ Авторизация с хешированием паролей
- ✅ Управление сессиями
- ✅ Личный профиль

### 🎮 Игры
- ✅ Загрузка игр в формате ZIP архива
- ✅ Поддержка JavaScript и Unity WebGL игр
- ✅ Автоматическая распаковка и валидация
- ✅ Редактирование и удаление своих игр
- ✅ Загрузка иконок для игр

### 🏆 Результаты
- ✅ Сохранение результатов через REST API
- ✅ Таблица лидеров для каждой игры
- ✅ Поддержка метаданных (уровень, время, жизни)
- ✅ История результатов пользователя

### 🔒 Безопасность
- ✅ Хеширование паролей (`password_hash`)
- ✅ Prepared Statements (защита от SQL-инъекций)
- ✅ Проверка прав доступа
- ✅ Валидация всех входных данных

---
### Требования
- Docker
- Docker Compose

---

## 🛠️ Технологии

| Компонент | Технология |
|-----------|-----------|
| Backend | PHP 8.1 |
| Database | MySQL 8.0 |
| Web Server | Apache 2.4 |
| Architecture | MVC Pattern |
| Database Access | PDO (Prepared Statements) |
| Containerization | Docker & Docker Compose |
| Testing | PHPUnit 10 |
| Frontend | HTML5, CSS3, JavaScript |

---


## 🔗 API Endpoints

### Аутентификация
- `GET /auth/login` — форма входа/регистрации
- `POST /auth/login` — вход в систему
- `POST /auth/register` — регистрация
- `GET /auth/logout` — выход

### Игры
- `GET /` — список всех игр
- `GET /game/play/{id}` — играть в игру
- `GET /game/add` — форма добавления игры
- `POST /game/add` — загрузка игры
- `GET /game/edit/{id}` — форма редактирования
- `POST /game/edit/{id}` — обновление игры
- `POST /game/delete/{id}` — удаление игры

### Профиль
- `GET /profile` — профиль пользователя

### API
- `POST /api/save-result` — сохранение результата (JSON)

---