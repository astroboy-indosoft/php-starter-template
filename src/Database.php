<?php

declare(strict_types=1);

namespace App;

/**
 * Database — thin PDO wrapper with a fluent query builder.
 *
 * Register as a singleton in public/index.php:
 *
 *   App::bind('db', fn() => new Database(App::config('db')));
 *
 * Then use anywhere:
 *
 *   $db = App::make('db');
 *
 *   // Raw query
 *   $rows = $db->query('SELECT * FROM users WHERE active = ?', [1])->fetchAll();
 *
 *   // Convenience methods
 *   $user  = $db->first('SELECT * FROM users WHERE id = ?', [$id]);
 *   $users = $db->all('SELECT * FROM users');
 *   $id    = $db->insert('users', ['name' => 'Alice', 'email' => 'alice@example.com']);
 *   $db->update('users', ['name' => 'Bob'], 'id = ?', [1]);
 *   $db->delete('users', 'id = ?', [1]);
 */
class Database
{
    private \PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset'] ?? 'utf8mb4'
        );

        $this->pdo = new \PDO($dsn, $config['user'], $config['password'], [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /** Execute a raw prepared statement and return the statement. */
    public function query(string $sql, array $bindings = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /** Fetch all rows. */
    public function all(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    /** Fetch the first row, or null. */
    public function first(string $sql, array $bindings = []): ?array
    {
        $row = $this->query($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch a single scalar value. */
    public function scalar(string $sql, array $bindings = []): mixed
    {
        return $this->query($sql, $bindings)->fetchColumn();
    }

    /**
     * INSERT a row and return the last insert ID.
     *
     * @param string               $table
     * @param array<string, mixed> $data
     */
    public function insert(string $table, array $data): string|false
    {
        $cols        = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql         = "INSERT INTO `{$table}` ({$cols}) VALUES ({$placeholders})";

        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    /**
     * UPDATE rows matching a WHERE clause.
     *
     * @param string               $table
     * @param array<string, mixed> $data
     * @param string               $where   e.g. 'id = ?'
     * @param array                $bindings  bindings for $where
     */
    public function update(string $table, array $data, string $where, array $bindings = []): int
    {
        $set = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";

        $stmt = $this->query($sql, [...array_values($data), ...$bindings]);
        return $stmt->rowCount();
    }

    /**
     * DELETE rows matching a WHERE clause.
     */
    public function delete(string $table, string $where, array $bindings = []): int
    {
        $stmt = $this->query("DELETE FROM `{$table}` WHERE {$where}", $bindings);
        return $stmt->rowCount();
    }

    /** Begin a transaction. */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /** Commit a transaction. */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /** Roll back a transaction. */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Run a callable inside a transaction; rolls back on exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /** Expose raw PDO for advanced use. */
    public function pdo(): \PDO
    {
        return $this->pdo;
    }
}
