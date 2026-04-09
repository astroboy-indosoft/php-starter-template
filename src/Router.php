<?php

class Node {
    public $children = [];
    public $paramChild = null;
    public $handler = null;
    public $paramName = null;
}

class Router {
    private $trees = [];

    public function add($method, $path, $handler) {
        $segments = $this->split($path);

        if (!isset($this->trees[$method])) {
            $this->trees[$method] = new Node();
        }

        $current = $this->trees[$method];

        foreach ($segments as $segment) {
            if (str_starts_with($segment, ':')) {
                if (!$current->paramChild) {
                    $node = new Node();
                    $node->paramName = substr($segment, 1);
                    $current->paramChild = $node;
                }
                $current = $current->paramChild;
            } else {
                if (!isset($current->children[$segment])) {
                    $current->children[$segment] = new Node();
                }
                $current = $current->children[$segment];
            }
        }

        $current->handler = $handler;
    }

    public function dispatch($method, $path) {
        if (!isset($this->trees[$method])) return null;

        $segments = $this->split($path);
        $current = $this->trees[$method];
        $params = [];

        foreach ($segments as $segment) {
            if (isset($current->children[$segment])) {
                $current = $current->children[$segment];
            } elseif ($current->paramChild) {
                $params[$current->paramChild->paramName] = $segment;
                $current = $current->paramChild;
            } else {
                return null;
            }
        }

        return $current->handler ? [$current->handler, $params] : null;
    }

    private function split($path) {
        return array_values(array_filter(explode('/', $path)));
    }
}
