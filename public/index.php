<?php

declare(strict_types=1);

// ------------------------------------------------------------------
// Bootstrap
// ------------------------------------------------------------------

require_once __DIR__ . '/../src/Autoloader.php';

use App\App;
use App\Router;
use App\View;

// Load config
$config = require __DIR__ . '/../config/app.php';
App::loadConfig($config);

// Configure error reporting based on environment
if (App::config('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Set timezone
date_default_timezone_set(App::config('app.timezone', 'UTC'));

// Configure the view renderer
View::setPath(App::config('paths.views'));

// Configure the logger
\App\Logger::setPath(App::config('paths.logs'));

// Register the database as a lazy singleton
// (only connects when first used)
App::bind('db', fn() => new \App\Database(App::config('db')));

// ------------------------------------------------------------------
// Router
// ------------------------------------------------------------------

$router = new Router();

require __DIR__ . '/../routes/web.php';

$router->dispatch();
