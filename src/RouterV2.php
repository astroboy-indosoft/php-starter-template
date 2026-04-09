<?php

// Compressed Radix Tree Node
class Node {
    public $prefix;
    public $children = [];
    public $handler = null;
    public $paramName = null;
    public $isParam = false;

    public function __construct($prefix = '') {
        $this->prefix = $prefix;
    }
}

class RouterV2 {
    private $trees = [];

    public function add($method, $path, $handler) {
        if (!isset($this->trees[$method])) {
            $this->trees[$method] = new Node();
        }

        $this->insert($this->trees[$method], $path, $handler);
    }

    private function insert($node, $path, $handler) {
        foreach ($node->children as $child) {
            $common = $this->commonPrefix($path, $child->prefix);

            if ($common === '') continue;

            if ($common !== $child->prefix) {
                $split = new Node(substr($child->prefix, strlen($common)));
                $split->children = $child->children;
                $split->handler = $child->handler;
                $split->paramName = $child->paramName;
                $split->isParam = $child->isParam;

                $child->prefix = $common;
                $child->children = [$split];
                $child->handler = null;
            }

            $remaining = substr($path, strlen($common));

            if ($remaining === '') {
                $child->handler = $handler;
                return;
            }

            return $this->insert($child, $remaining, $handler);
        }

        if (preg_match('#^/:([^/]+)#', $path, $m)) {
            $param = new Node('/');
            $param->isParam = true;
            $param->paramName = $m[1];

            $remaining = substr($path, strlen($m[0]));

            if ($remaining === '') {
                $param->handler = $handler;
            } else {
                $this->insert($param, $remaining, $handler);
            }

            $node->children[] = $param;
            return;
        }

        $new = new Node($path);
        $new->handler = $handler;
        $node->children[] = $new;
    }

    public function dispatch($method, $path) {
        if (!isset($this->trees[$method])) return null;

        $params = [];
        return $this->search($this->trees[$method], $path, $params);
    }

    private function search($node, $path, &$params) {
        foreach ($node->children as $child) {

            if ($child->isParam) {
                if (preg_match('#^/([^/]+)#', $path, $m)) {
                    $params[$child->paramName] = $m[1];
                    $remaining = substr($path, strlen($m[0]));

                    if ($remaining === '' && $child->handler) {
                        return [$child->handler, $params];
                    }

                    return $this->search($child, $remaining, $params);
                }
            }

            if (str_starts_with($path, $child->prefix)) {
                $remaining = substr($path, strlen($child->prefix));

                if ($remaining === '' && $child->handler) {
                    return [$child->handler, $params];
                }

                return $this->search($child, $remaining, $params);
            }
        }

        return null;
    }

    private function commonPrefix($a, $b) {
        $len = min(strlen($a), strlen($b));
        $i = 0;
        while ($i < $len && $a[$i] === $b[$i]) $i++;
        return substr($a, 0, $i);
    }
}
