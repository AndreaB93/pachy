<?php
declare(strict_types=1);

use App\Controllers\{AuthController, HomeController};
use App\Controllers\Api\UserController as ApiUserController;
use App\Middleware\{AuthSession, AuthBearer, CsrfCheck, ValidateJson, RateLimit};

/**
 * Route definitions.
 * Handler format: [Middleware1::class, ..., [Controller::class, 'method']]
 * $router is injected from public/index.php.
 */

// ─── Auth (no auth required) ─────────────────────────────────────────────────

$router->get('/login',    [[AuthController::class, 'showLogin']]);
$router->post('/login',   [RateLimit::class, [AuthController::class, 'login']]);
$router->get('/logout',   [[AuthController::class, 'logout']]);
$router->get('/register', [[AuthController::class, 'showRegister']]);
$router->post('/register',[RateLimit::class, [AuthController::class, 'register']]);

// ─── Web routes (session auth) ────────────────────────────────────────────────

$router->get('/', [AuthSession::class, [HomeController::class, 'index']]);

// ─── REST API routes (JWT auth) ───────────────────────────────────────────────

$router->post('/api/auth/login', [RateLimit::class, ValidateJson::class, [ApiUserController::class, 'login']]);

$router->get('/api/users',         [AuthBearer::class, [ApiUserController::class, 'index']]);
$router->get('/api/users/{id:\d+}', [AuthBearer::class, [ApiUserController::class, 'show']]);
$router->post('/api/users',        [AuthBearer::class, ValidateJson::class, [ApiUserController::class, 'store']]);
$router->put('/api/users/{id:\d+}', [AuthBearer::class, ValidateJson::class, [ApiUserController::class, 'update']]);
$router->delete('/api/users/{id:\d+}', [AuthBearer::class, [ApiUserController::class, 'destroy']]);
