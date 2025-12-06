<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/core/Router.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/HomeController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/AuthController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/GameController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/ProfileController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/controllers/ApiController.php';

$router = new Router();

// Auth routes
$router->get('/auth/login', 'AuthController', 'showLogin');
$router->post('/auth/login', 'AuthController', 'login');
$router->post('/auth/register', 'AuthController', 'register');
$router->get('/auth/logout', 'AuthController', 'logout');

// Home
$router->get('/', 'HomeController', 'index');
$router->post('/', 'HomeController', 'index');

// Profile
$router->get('/profile', 'ProfileController', 'index');
$router->post('/profile', 'ProfileController', 'index');

// Game routes
$router->get('/game/add', 'GameController', 'showAddForm');
$router->post('/game/add', 'GameController', 'add');
$router->get('/game/play/{id}', 'GameController', 'play');
$router->post('/game/play/{id}', 'GameController', 'play');
$router->get('/game/edit/{id}', 'GameController', 'showEditForm');
$router->post('/game/edit/{id}', 'GameController', 'edit');

// API routes
$router->post('/api/save-result', 'ApiController', 'saveResult');

$router->dispatch();
