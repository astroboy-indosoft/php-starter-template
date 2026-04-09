<?php

class MiddlewarePipeline {
    private $middlewares = [];

    public function add($middleware) {
        $this->middlewares[] = $middleware;
    }

    public function handle($request, $handler) {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middleware) {
                return function ($request) use ($middleware, $next) {
                    return $middleware($request, $next);
                };
            },
            function ($request) use ($handler) {
                return $handler($request);
            }
        );

        return $pipeline($request);
    }
}
