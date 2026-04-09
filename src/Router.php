<?php

declare(strict_types=1);

namespace App;

/**
 * RadixNode — a radix-tree router with named parameter support.
 *
 * Supports:
 *   - Static segments:   /users/profile
 *   - Named parameters:  /users/{id}
 *   - Optional suffix:   /posts/{slug}
 *   - Wildcard catch-all: /files/{path*}
 */
class RouterNode
{
    public string $segment;
    public bool $isParam = false;
    public bool $isWildcard = false;
    public string $paramName = '';

    /** @var RouterNode[] */
    public array $children = [];

    /** @var array<string, array{handler: callable, middleware: callable[]}> */
    public array $handlers = [];

    public function __construct(string $segment)
    {
        $this->segment = $segment;

        if (str_starts_with($segment, '{') && str_ends_with($segment, '*}')) {
            $this->isWildcard = true;
            $this->isParam = true;
            $this->paramName = substr($segment, 1, -2);
        } elseif (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
            $this->isParam = true;
            $this->paramName = substr($segment, 1, -1);
        }
    }
}

class Router
{
    private RouterNode $root;

    /** @var callable[] */
    private array $globalMiddleware = [];

    /** @var array<string, string> */
    private array $namedRoutes = [];

    public function __construct()
    {
        $this->root = new RouterNode('/');
    }

    // ------------------------------------------------------------------
    // Public registration API
    // ------------------------------------------------------------------

    public function get(string $path, callable $handler, array $middleware = []): static
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): static
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): static
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable $handler, array $middleware = []): static
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): static
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function any(string $path, callable $handler, array $middleware = []): static
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler, $middleware);
        }
        return $this;
    }

    public function use(callable $middleware): static
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function name(string $routeName, string $path): static
    {
        $this->namedRoutes[$routeName] = $path;
        return $this;
    }

    /** Generate URL for a named route, filling in parameters. */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("No route named '{$name}'.");
        }
        $path = $this->namedRoutes[$name];
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }
        return $path;
    }

    // ------------------------------------------------------------------
    // Dispatch
    // ------------------------------------------------------------------

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = '/' . trim($uri, '/');

        // HEAD is handled as GET
        $lookupMethod = ($method === 'HEAD') ? 'GET' : $method;

        $result = $this->resolve($uri);

        if ($result === null) {
            $this->sendError(404, 'Not Found', $uri);
            return;
        }

        if (!isset($result['node']->handlers[$lookupMethod])) {
            $this->sendError(405, 'Method Not Allowed', $uri);
            return;
        }

        ['handler' => $handler, 'middleware' => $routeMiddleware] =
            $result['node']->handlers[$lookupMethod];

        $params  = $result['params'];
        $request = $this->buildRequest($method, $uri, $params);

        // Combine global + route middleware, then handler
        $chain = [...$this->globalMiddleware, ...$routeMiddleware];

        $this->runChain($chain, $request, $handler);
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function addRoute(string $method, string $path, callable $handler, array $middleware): static
    {
        $segments = $this->splitPath($path);
        $node     = $this->root;

        foreach ($segments as $segment) {
            $node = $this->findOrCreate($node, $segment);
        }

        $node->handlers[$method] = [
            'handler'    => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    private function findOrCreate(RouterNode $parent, string $segment): RouterNode
    {
        foreach ($parent->children as $child) {
            if ($child->segment === $segment) {
                return $child;
            }
        }
        $child = new RouterNode($segment);
        $parent->children[] = $child;
        return $child;
    }

    /**
     * Walk the radix tree, returning ['node' => ..., 'params' => [...]] or null.
     */
    private function resolve(string $uri): ?array
    {
        $segments = $this->splitPath($uri);
        $params   = [];

        $result = $this->matchNode($this->root, $segments, 0, $params);
        return $result;
    }

    private function matchNode(RouterNode $node, array $segments, int $depth, array &$params): ?array
    {
        if ($depth === count($segments)) {
            // Exact match at a node that has at least one handler
            if (!empty($node->handlers)) {
                return ['node' => $node, 'params' => $params];
            }
            return null;
        }

        $segment = $segments[$depth];

        foreach ($node->children as $child) {
            // Wildcard — consumes all remaining segments
            if ($child->isWildcard) {
                $params[$child->paramName] = implode('/', array_slice($segments, $depth));
                if (!empty($child->handlers)) {
                    return ['node' => $child, 'params' => $params];
                }
                return null;
            }

            // Named parameter
            if ($child->isParam) {
                $savedParams = $params;
                $params[$child->paramName] = rawurldecode($segment);
                $result = $this->matchNode($child, $segments, $depth + 1, $params);
                if ($result !== null) {
                    return $result;
                }
                $params = $savedParams;
                continue;
            }

            // Static segment
            if ($child->segment === $segment) {
                $result = $this->matchNode($child, $segments, $depth + 1, $params);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    private function splitPath(string $path): array
    {
        $parts = array_filter(explode('/', $path), fn($p) => $p !== '');
        return array_values($parts);
    }

    private function buildRequest(string $method, string $uri, array $params): array
    {
        $body = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw  = file_get_contents('php://input');
            $body = json_decode($raw ?: '{}', true) ?? [];
        } else {
            $body = $_POST;
        }

        return [
            'method'  => $method,
            'uri'     => $uri,
            'params'  => $params,
            'query'   => $_GET,
            'body'    => $body,
            'headers' => $this->getHeaders(),
        ];
    }

    private function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    private function runChain(array $chain, array $request, callable $handler): void
    {
        if (empty($chain)) {
            $handler($request);
            return;
        }

        $middleware = array_shift($chain);
        $next       = fn() => $this->runChain($chain, $request, $handler);

        $middleware($request, $next);
    }

    private function sendError(int $code, string $message, string $uri): void
    {
        http_response_code($code);

        $viewFile = __DIR__ . "/../views/errors/{$code}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
            return;
        }

        echo "<h1>{$code} {$message}</h1><p>Path: " . htmlspecialchars($uri) . "</p>";
    }
}
