<?php

declare(strict_types=1);

/**
 * config/app.php — application configuration.
 *
 * Access via: config('app.name'), config('db.host'), etc.
 * Never commit real secrets here — use a .env file in production.
 */

return [

    'app' => [
        'name'    => $_ENV['APP_NAME']  ?? 'PHP Starter',
        'env'     => $_ENV['APP_ENV']   ?? 'development',
        'debug'   => ($_ENV['APP_DEBUG'] ?? 'true') === 'true',
        'url'     => $_ENV['APP_URL']   ?? 'http://localhost:8000',
        'timezone'=> 'UTC',
    ],

    'db' => [
        'driver'   => $_ENV['DB_DRIVER']   ?? 'mysql',
        'host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
        'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
        'name'     => $_ENV['DB_NAME']     ?? 'app',
        'user'     => $_ENV['DB_USER']     ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset'  => 'utf8mb4',
    ],

    'session' => [
        'name'     => 'app_session',
        'lifetime' => 7200, // seconds
        'secure'   => ($_ENV['APP_ENV'] ?? '') === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'paths' => [
        'views'   => __DIR__ . '/../views',
        'storage' => __DIR__ . '/../storage',
        'logs'    => __DIR__ . '/../storage/logs',
    ],

];
