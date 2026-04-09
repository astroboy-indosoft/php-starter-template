<?php

declare(strict_types=1);

use App\Router;
use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;

/**
 * routes/web.php — all application routes.
 *
 * Register routes on the $router instance provided by public/index.php.
 *
 * Route syntax:
 *   $router->get('/path', handler, [middleware, ...]);
 *
 * Parameter syntax (RadixNode):
 *   {id}    — named segment parameter   → $request['params']['id']
 *   {path*} — wildcard, rest of URL     → $request['params']['path']
 */

// ------------------------------------------------------------------
// Global middleware (runs on every request)
// ------------------------------------------------------------------
$router->use(new CorsMiddleware());

// ------------------------------------------------------------------
// Public web pages
// ------------------------------------------------------------------
$router->get('/', [new HomeController(), 'index']);
$router->get('/about', [new HomeController(), 'about']);

// ------------------------------------------------------------------
// API routes — /api prefix, JSON middleware on all
// ------------------------------------------------------------------

// Users resource
$router->get('/api/users', [new UserController(), 'index'],
    [new JsonMiddleware()]);

$router->post('/api/users', [new UserController(), 'store'],
    [new JsonMiddleware()]);

$router->get('/api/users/{id}', [new UserController(), 'show'],
    [new JsonMiddleware()]);

$router->put('/api/users/{id}', [new UserController(), 'update'],
    [new JsonMiddleware()]);

$router->delete('/api/users/{id}', [new UserController(), 'destroy'],
    [new JsonMiddleware()]);

// ------------------------------------------------------------------
// Protected routes — require auth middleware
// ------------------------------------------------------------------
$router->get('/dashboard', function (array $request): void {
    (new \App\Response())->view('pages/dashboard', ['title' => 'Dashboard']);
}, [new AuthMiddleware()]);

// ------------------------------------------------------------------
// Wildcard static file catch-all example
// /files/{path*} → serves from storage/
// ------------------------------------------------------------------
$router->get('/files/{path*}', function (array $request): void {
    $path = $request['params']['path'] ?? '';
    $file = __DIR__ . '/../storage/' . $path;

    if (!file_exists($file) || !is_file($file)) {
        http_response_code(404);
        echo 'File not found';
        return;
    }

    // Basic MIME detection
    $mime = mime_content_type($file) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($file);
});

// ------------------------------------------------------------------
// Named route example — use $router->url('route.name', ['id' => 5])
// ------------------------------------------------------------------
$router->name('user.show', '/api/users/{id}');
$router->name('home', '/');
