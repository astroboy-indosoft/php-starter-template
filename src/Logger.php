<?php

declare(strict_types=1);

namespace App;

/**
 * Logger — PSR-3-inspired file logger.
 *
 * Usage:
 *   Logger::info('User logged in', ['user_id' => 5]);
 *   Logger::error('DB connection failed', ['exception' => $e->getMessage()]);
 */
class Logger
{
    public const DEBUG   = 'DEBUG';
    public const INFO    = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR   = 'ERROR';

    private static string $logFile = '';

    public static function setPath(string $path): void
    {
        self::$logFile = rtrim($path, '/') . '/app.log';
    }

    public static function debug(string $message, array $context = []): void
    {
        self::write(self::DEBUG, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write(self::INFO, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write(self::WARNING, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write(self::ERROR, $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $ctx       = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $line      = "[{$timestamp}] {$level}: {$message}{$ctx}" . PHP_EOL;

        $file = self::$logFile ?: sys_get_temp_dir() . '/php-starter.log';

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
