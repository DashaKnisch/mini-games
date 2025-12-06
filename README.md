# PHP Mini Games Service (MVC Architecture)

## Описание проекта
Сервис для работы с мини-играми на PHP, переведённый на MVC архитектуру. Позволяет запускать игры, учитывать результаты, добавлять новые игры, голосовать и комментировать.

## Архитектура MVC

Проект использует паттерн Model-View-Controller:
- **Models** - работа с данными (User, Game, Result, Vote, Comment)
- **Views** - HTML шаблоны для отображения
- **Controllers** - обработка запросов и бизнес-логика
- **Router** - маршрутизация запросов

Подробнее см. [MVC_MIGRATION.md](MVC_MIGRATION.md)

## Структура проекта
```
php-mini-games-service/
├── public/                    # Публичная директория (document root)
│   ├── index.php             # Единая точка входа
│   └── .htaccess             # Правила маршрутизации
├── src/
│   ├── core/                 # Ядро (Router, Controller)
│   ├── models/               # Модели данных
│   ├── controllers/          # Контроллеры
│   ├── views/                # HTML шаблоны
│   ├── lib/                  # Библиотеки (database.php)
│   └── assets/               # CSS, JS
├── repository/               # Загруженные игры
├── db/
│   └── schema.sql            # Схема БД
└── docker-compose.yml        # Docker конфигурация
```

## Установка и запуск

### Требования
- Docker и Docker Compose

### Запуск
```bash
# Клонируйте репозиторий
git clone <URL_репозитория>
cd php-mini-games-service

# Запустите контейнеры
docker-compose up -d

# Проверьте логи
docker-compose logs -f web
```

Приложение будет доступно по адресу: **http://localhost:8080**

### Первый запуск
При первом запуске схема БД применится автоматически. Если нужно пересоздать БД:
```bash
docker-compose down
docker volume rm php-mini-games-service_db_data
docker-compose up -d
```

## Основные маршруты

- `/` - Главная страница (список игр)
- `/auth/login` - Вход/регистрация
- `/profile` - Профиль пользователя
- `/game/add` - Добавить игру
- `/game/play/{id}` - Играть
- `/game/edit/{id}` - Редактировать игру

## Функционал

- ✅ Регистрация и авторизация
- ✅ Добавление игр (JavaScript и Unity WebGL)
- ✅ Голосование (лайки/дизлайки)
- ✅ Комментарии к играм
- ✅ Рейтинг результатов
- ✅ Редактирование и удаление игр
- ✅ Админ-панель (удаление игр с уведомлениями)

## Технологии

- PHP 8+ с Apache
- MySQL 8.0
- Docker & Docker Compose
- MVC архитектура
- PDO для работы с БД

## Разработка

Для добавления нового функционала:
1. Создайте модель в `src/models/`
2. Создайте контроллер в `src/controllers/`
3. Создайте представление в `src/views/`
4. Добавьте маршрут в `public/index.php`

## Вклад
Создайте форк репозитория и отправьте pull request с вашими изменениями.
