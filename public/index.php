<?php
// Front Controller
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Request.php';
require_once __DIR__ . '/../src/Response.php';

$router = new Router();
require_once __DIR__ . '/../routes/web.php';

$request = new Request();
$result = $router->dispatch($request->method(), $request->path());

if ($result) {
    [$handler, $params] = $result;
    echo call_user_func($handler, $params);
} else {
    http_response_code(404);
    echo "404 Not Found";
}
