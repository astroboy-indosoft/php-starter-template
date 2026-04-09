<?php

declare(strict_types=1);

/**
 * Autoloader — maps App\* to src/*.php
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = __DIR__ . '/../src/' . $relative . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
