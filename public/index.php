<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\{AuthController, HomeController};
use App\Controllers\Api;
use App\Middleware;
use Core\Router;

$router = new Router();
require_once __DIR__ . '/../app/routes.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI']
);
