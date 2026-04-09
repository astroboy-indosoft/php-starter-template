<?php

require_once __DIR__ . '/../src/Middleware.php';

$pipeline = new MiddlewarePipeline();

// Logging middleware
$pipeline->add(function($req, $next) {
    error_log('Request incoming');
    return $next($req);
});

// Auth middleware example
$pipeline->add(function($req, $next) {
    if (!isset($_GET['token'])) {
        return 'Unauthorized';
    }
    return $next($req);
});

$response = $pipeline->handle($_SERVER, function($req) {
    return "OK";
});

echo $response;
