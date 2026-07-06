<?php
declare(strict_types=1);

namespace Core;

class DB
{
    private static ?\PDO $pdo = null;

    public static function connect(array $config): void
    {
        $driver  = $config['driver'] ?? 'mysql';
        $host    = $config['host']   ?? 'localhost';
        $name    = $config['name']   ?? '';
        $user    = $config['user']   ?? 'root';
        $pass    = $config['pass']   ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = match($driver) {
            'sqlite' => "sqlite:$name",
            default  => "mysql:host=$host;dbname=$name;charset=$charset",
        };

        self::$pdo = new \PDO($dsn, $driver === 'sqlite' ? null : $user, $driver === 'sqlite' ? null : $pass, [
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    /** Returns all rows as associative arrays */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Returns first row or null — uses array_first() (PHP 8.5) internally */
    public static function row(string $sql, array $params = []): ?array
    {
        $rows = self::query($sql, $params);
        return array_key_exists(0, $rows) ? $rows[0] : null;
    }

    /** Returns single scalar value or null */
    public static function value(string $sql, array $params = []): mixed
    {
        $row = self::row($sql, $params);
        return $row !== null ? array_values($row)[0] : null;
    }

    /** INSERT / UPDATE / DELETE — returns affected row count */
    #[\NoDiscard]
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Returns last inserted ID */
    public static function lastId(): string
    {
        return self::pdo()->lastInsertId();
    }

    /** Runs callable in a transaction; auto-rollback on Throwable */
    #[\NoDiscard]
    public static function transaction(callable $fn): mixed
    {
        self::pdo()->beginTransaction();
        try {
            $result = $fn();
            self::pdo()->commit();
            return $result;
        } catch (\Throwable $e) {
            self::pdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Bulk insert with chunking.
     * $rows: array of arrays with identical keys.
     * $chunkSize: rows per INSERT statement (default 500).
     */
    public static function insertBatch(string $table, array $rows, int $chunkSize = 500): void
    {
        if (empty($rows)) {
            return;
        }

        $columns = array_keys($rows[0]);
        $colList = implode(', ', array_map(fn($c) => "`$c`", $columns));

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $placeholderRow = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $placeholders   = implode(', ', array_fill(0, count($chunk), $placeholderRow));
            $params         = [];
            foreach ($chunk as $row) {
                foreach ($columns as $col) {
                    $params[] = $row[$col];
                }
            }
            self::execute("INSERT INTO `$table` ($colList) VALUES $placeholders", $params);
        }
    }

    /** Expose raw PDO for edge cases (use sparingly — only within core/) */
    public static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('DB not connected. Call DB::connect() first.');
        }
        return self::$pdo;
    }
}
