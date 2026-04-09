<?php

class Request {
    public function method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function path() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }
}
