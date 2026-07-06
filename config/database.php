<?php

return [
    'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'   => $_ENV['DB_HOST']   ?? 'localhost',
    'name'   => $_ENV['DB_NAME']   ?? 'app_db',
    'user'   => $_ENV['DB_USER']   ?? 'root',
    'pass'   => $_ENV['DB_PASS']   ?? '',
    'charset' => 'utf8mb4',
];
