<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Response;

/**
 * AuthMiddleware — blocks unauthenticated requests.
 *
 * Checks for a session user or a bearer token header.
 * Replace the logic inside with your real auth strategy.
 */
class AuthMiddleware
{
    public function __invoke(array $request, callable $next): void
    {
        // Example: session-based auth
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            // API request → 401 JSON; browser request → redirect
            $wantsJson = str_contains($request['headers']['ACCEPT'] ?? '', 'application/json');

            if ($wantsJson) {
                (new Response())->json(['error' => 'Unauthorized'], 401);
            } else {
                (new Response())->redirect('/login');
            }
            return;
        }

        $next();
    }
}

/**
 * JsonMiddleware — ensures the response will be JSON and parses JSON bodies.
 */
class JsonMiddleware
{
    public function __invoke(array $request, callable $next): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $next();
    }
}

/**
 * CorsMiddleware — adds CORS headers (tune origins for production).
 */
class CorsMiddleware
{
    public function __invoke(array $request, callable $next): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($request['method'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $next();
    }
}
