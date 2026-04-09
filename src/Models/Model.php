<?php

declare(strict_types=1);

namespace App\Models;

use App\App;
use App\Database;

/**
 * Model — active-record-style base class.
 *
 * Extend this for each entity:
 *
 *   class User extends Model
 *   {
 *       protected static string $table = 'users';
 *   }
 *
 *   $user  = User::find(1);
 *   $users = User::all();
 *   $id    = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
 *   User::update(['name' => 'Bob'], 'id = ?', [1]);
 *   User::delete('id = ?', [1]);
 */
abstract class Model
{
    protected static string $table = '';

    protected static function db(): Database
    {
        return App::make('db');
    }

    /** Fetch all rows. */
    public static function all(string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM `' . static::$table . '`';
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return static::db()->all($sql);
    }

    /** Find a row by primary key. */
    public static function find(int|string $id, string $pk = 'id'): ?array
    {
        return static::db()->first(
            'SELECT * FROM `' . static::$table . "` WHERE `{$pk}` = ? LIMIT 1",
            [$id]
        );
    }

    /** Find rows matching arbitrary WHERE. */
    public static function where(string $condition, array $bindings = []): array
    {
        return static::db()->all(
            'SELECT * FROM `' . static::$table . "` WHERE {$condition}",
            $bindings
        );
    }

    /** Find first row matching WHERE. */
    public static function firstWhere(string $condition, array $bindings = []): ?array
    {
        return static::db()->first(
            'SELECT * FROM `' . static::$table . "` WHERE {$condition} LIMIT 1",
            $bindings
        );
    }

    /** INSERT a new row and return the insert ID. */
    public static function create(array $data): string|false
    {
        return static::db()->insert(static::$table, $data);
    }

    /** UPDATE rows matching a WHERE clause. */
    public static function update(array $data, string $where, array $bindings = []): int
    {
        return static::db()->update(static::$table, $data, $where, $bindings);
    }

    /** DELETE rows matching a WHERE clause. */
    public static function delete(string $where, array $bindings = []): int
    {
        return static::db()->delete(static::$table, $where, $bindings);
    }

    /** Return total row count. */
    public static function count(string $where = '', array $bindings = []): int
    {
        $sql = 'SELECT COUNT(*) FROM `' . static::$table . '`';
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        return (int) static::db()->scalar($sql, $bindings);
    }
}
