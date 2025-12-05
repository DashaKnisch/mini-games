<<<<<<< HEAD
# mini-games
=======
# PHP Mini Games Service — Docker instructions

Короткая инструкция по запуску проекта в Docker (локальная разработка).

1) Предварительные требования
- Docker и Docker Compose установлены на машине.

2) Сборка и запуск (PowerShell):

```powershell
cd d:\kursach\php-mini-games-service
docker-compose build
docker-compose up -d
```

3) Инициализация базы
- Файл `db/schema.sql` автоматически подключён в контейнер MySQL через `docker-entrypoint-initdb.d` и будет выполнен при первом запуске контейнера (если база данных ещё не создана).

4) Доступ к приложению
- Откройте в браузере: http://localhost:8080

5) Пара замечаний по структуре
- `src/` монтируется в контейнер как `/var/www/html` — это веб-корень приложения.
- `repository/` монтируется в `/var/www/html/repository` для хранения загруженных пользователями игр. Если папки `repository` нет — она будет создана при добавлении игры.

6) Переменные окружения
- `docker-compose.yml` настраивает переменные для доступа к БД (DB_HOST, DB_NAME, DB_USER, DB_PASS) для PHP-контейнера. Файл `src/lib/database.php` читает эти переменные.

7) Импорт/огона схемы вручную (если нужно повторно применить)

```powershell
# скопировать схему в контейнер и выполнить
docker cp .\db\schema.sql $(docker-compose ps -q db):/schema.sql
docker exec -it $(docker-compose ps -q db) bash -c "mysql -uroot -proot php_mini_games < /schema.sql"
```

8) Журнал и отладка
- Просмотреть логи:

```powershell
docker-compose logs -f web
docker-compose logs -f db
```

Если потребуется, могу добавить `docker-compose.override.yml` для разработки, или подготовить отдельный `nginx` + `php-fpm` стек — но текущее решение использует `php:apache` для простоты.
# php-mini-games-service

## Описание проекта
Данный проект представляет собой сервис для работы с встроенными мини-играми на PHP. Он позволяет запускать мини-игры, учитывать результаты и легко добавлять новые игры.

## Структура проекта
```
php-mini-games-service
├── db
│   └── schema.sql           # База данных
├── src
│   ├── index.php                # Основной файл приложения (главная страница сайта)
│   ├── add_game.php             # Файл с логикой добавления js игр
│   ├── comment.php              # Файл с обработкой комментариев
│   ├── edit_game.php            # Файл редакотора существующих игр
│   ├── play.php                 # Файл с логикой запуска игр
│   ├── profile.php              # Страница с профилем пользователя
│   ├── save_results.php         # Файл с логикой сохранения результатов
│   ├── vote.php                 # Файл с обработкой лайков/дизлайков
│   ├── games
│   │   └── example-game.php     # Пример мини-игры
│   ├── templates
│   │   └── layout.php           # Общий шаблон страниц
│   ├── assets
│   │   └── css
│   │      ├── add_game.css       # Файл стилей страницы add_game
│   │      ├── auth.css           # Файл стилей страницы auth
│   │      ├── main.css           # Файл стилей главной страницы 
│   │      ├── play.css           # Файл стилей страницы play
│   │      └── profile.css        # Файл стилей страницы профиля
│   ├── auth
│   │   └── auth.php              # Файл авторизации
│   ├── repository                # Папка распаковки игр
│   └── lib    
│       └── database.php         # Функции для работы с базой данных
├── docker-compose.yml           # Настройка и запуск контейнеров Docker
├── php.Dockerfile               # Инструкции для создания образа Docker
└── README.md                    # Документация проекта
```

## Установка
1. Клонируйте репозиторий:
   ```
   git clone <URL_репозитория>
   ```
2. Перейдите в директорию проекта:
   ```
   cd php-mini-games-service
   ```
3. Запустите Docker контейнеры:
   ```
   docker-compose up -d
   ```

## Использование
- Откройте браузер и перейдите по адресу `http://localhost:8080` для доступа к приложению.
- Добавляйте новые мини-игры, создавая файлы в директории `src/games`.

## Вклад
Если вы хотите внести свой вклад в проект, пожалуйста, создайте форк репозитория и отправьте пулл-реквест с вашими изменениями.
>>>>>>> ec92704 (Initial commit)
