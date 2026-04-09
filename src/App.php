<?php

declare(strict_types=1);

namespace App;

/**
 * Container — lightweight dependency container / service locator.
 *
 * Usage:
 *   App::bind('db', fn() => new Database(config('db')));
 *   $db = App::make('db');
 */
class App
{
    /** @var array<string, callable> */
    private static array $bindings = [];

    /** @var array<string, mixed> Singletons already resolved */
    private static array $instances = [];

    /** @var array<string, mixed> Config values */
    private static array $config = [];

    // ------------------------------------------------------------------
    // Container
    // ------------------------------------------------------------------

    public static function bind(string $abstract, callable $factory, bool $singleton = true): void
    {
        self::$bindings[$abstract] = $factory;
        if ($singleton) {
            unset(self::$instances[$abstract]);
        }
    }

    public static function make(string $abstract): mixed
    {
        if (isset(self::$instances[$abstract])) {
            return self::$instances[$abstract];
        }

        if (!isset(self::$bindings[$abstract])) {
            throw new \RuntimeException("No binding registered for '{$abstract}'.");
        }

        $instance = (self::$bindings[$abstract])();
        self::$instances[$abstract] = $instance;
        return $instance;
    }

    // ------------------------------------------------------------------
    // Config
    // ------------------------------------------------------------------

    public static function loadConfig(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Get a config value using dot notation.
     * config('db.host') → self::$config['db']['host']
     */
    public static function config(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = self::$config;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}

/** Global shortcut for App::config() */
function config(string $key, mixed $default = null): mixed
{
    return App::config($key, $default);
}
