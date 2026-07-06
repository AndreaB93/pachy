<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Core\{DB, Auth, Container};

// 1. Environment
(Dotenv::createImmutable(__DIR__))->load();

// 2. Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] === 'true' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/storage/logs/php-errors.log');

// 3. Timezone
date_default_timezone_set(require(__DIR__ . '/config/app.php')['timezone'] ?? 'UTC');

// 4. Database
DB::connect([
    'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'   => $_ENV['DB_HOST'],
    'name'   => $_ENV['DB_NAME'],
    'user'   => $_ENV['DB_USER'],
    'pass'   => $_ENV['DB_PASS'],
]);

// 5. Session (web context only)
if (php_sapi_name() !== 'cli') {
    Auth::startSession();
}

// 6. Dependency Container
$container = new Container();
require_once __DIR__ . '/config/bindings.php'; // registers singletons
