<?php

$router->add('GET', '/', function() {
    return "Home Page";
});

$router->add('GET', '/hello/:name', function($params) {
    return "Hello, " . $params['name'];
});

$router->add('GET', '/user/:id', function($params) {
    return Response::json(['user_id' => $params['id']]);
});
