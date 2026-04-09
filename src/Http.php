<?php

declare(strict_types=1);

namespace App;

/**
 * Response — fluent HTTP response builder.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers  = [];
    private string $body    = '';

    public function status(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(mixed $data, int $status = 200): void
    {
        $this->status($status)
             ->header('Content-Type', 'application/json; charset=UTF-8');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->send();
    }

    public function html(string $html, int $status = 200): void
    {
        $this->status($status)
             ->header('Content-Type', 'text/html; charset=UTF-8');
        $this->body = $html;
        $this->send();
    }

    public function text(string $text, int $status = 200): void
    {
        $this->status($status)
             ->header('Content-Type', 'text/plain; charset=UTF-8');
        $this->body = $text;
        $this->send();
    }

    public function redirect(string $url, int $status = 302): void
    {
        $this->status($status)->header('Location', $url);
        $this->send();
    }

    public function view(string $template, array $data = [], int $status = 200): void
    {
        $this->status($status)
             ->header('Content-Type', 'text/html; charset=UTF-8');
        $this->body = View::render($template, $data);
        $this->send();
    }

    private function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
        exit;
    }
}

/**
 * View — simple template renderer with layout support.
 */
class View
{
    private static string $viewPath = '';

    public static function setPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/');
    }

    /**
     * Render a view template, optionally wrapped in a layout.
     *
     * @param string $template  e.g. 'pages/home' or 'pages/user/profile'
     * @param array  $data      Variables extracted into the template scope
     */
    public static function render(string $template, array $data = []): string
    {
        $file = self::$viewPath . '/' . $template . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$file}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        // If the view defined a layout, wrap it
        if (isset($layout)) {
            $layoutFile = self::$viewPath . '/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout not found: {$layoutFile}");
            }
            ob_start();
            require $layoutFile;
            $content = ob_get_clean();
        }

        return $content;
    }

    /** Escape a value for safe HTML output. */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
