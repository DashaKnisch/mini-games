<?php
/**
 * Bootstrap для PHPUnit тестов
 * Загружается перед запуском всех тестов
 */

// Устанавливаем DOCUMENT_ROOT для тестов
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';

// Подключаем автозагрузчик Composer (если установлен)
if (file_exists('/var/www/html/vendor/autoload.php')) {
    require_once '/var/www/html/vendor/autoload.php';
}

// Подключаем базовые классы
require_once '/var/www/html/src/lib/database.php';
require_once '/var/www/html/src/core/Controller.php';
require_once '/var/www/html/src/core/Router.php';

// Подключаем все модели
require_once '/var/www/html/src/models/User.php';
require_once '/var/www/html/src/models/Game.php';
require_once '/var/www/html/src/models/Result.php';
require_once '/var/www/html/src/models/Vote.php';
require_once '/var/www/html/src/models/Comment.php';
require_once '/var/www/html/src/models/Message.php';

// Подключаем контроллеры
require_once '/var/www/html/src/controllers/HomeController.php';
require_once '/var/www/html/src/controllers/AuthController.php';
require_once '/var/www/html/src/controllers/GameController.php';
require_once '/var/www/html/src/controllers/ProfileController.php';
require_once '/var/www/html/src/controllers/ApiController.php';

echo "Bootstrap loaded successfully!\n";
