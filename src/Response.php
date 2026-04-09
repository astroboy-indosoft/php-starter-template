<?php

class Response {
    public static function json($data) {
        header('Content-Type: application/json');
        return json_encode($data);
    }

    public static function text($text) {
        header('Content-Type: text/plain');
        return $text;
    }
}
