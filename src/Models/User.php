<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User model — example concrete model.
 *
 * Assumes a table:
 *   CREATE TABLE users (
 *     id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     name       VARCHAR(120)  NOT NULL,
 *     email      VARCHAR(200)  NOT NULL UNIQUE,
 *     password   VARCHAR(255)  NOT NULL,
 *     created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
 *   );
 */
class User extends Model
{
    protected static string $table = 'users';

    /** Find by email address. */
    public static function findByEmail(string $email): ?array
    {
        return static::firstWhere('email = ?', [$email]);
    }

    /**
     * Create a user with a hashed password.
     *
     * @param array{name: string, email: string, password: string} $data
     */
    public static function register(array $data): string|false
    {
        return static::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
    }

    /** Verify a plain-text password against the stored hash. */
    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
