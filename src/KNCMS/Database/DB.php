<?php
declare(strict_types=1);

namespace KNCMS\Database;

use PDO;
use KNCMS\Core\Env;

final class DB
{
    private static ?PDO $pdo = null;

    public static function connect(array $cfg): void
    {
        if (self::$pdo instanceof PDO) return;

        // If both `local` and `server` configs are provided, choose based on APP_LOCAL
        // Otherwise fall back to the provided root-level config array.
        $useLocal = false;
        try {
            $useLocal = Env::bool('APP_LOCAL', false);
        } catch (\Throwable $e) {
            // Env not available or other error -> default to false
            $useLocal = false;
        }

        if (isset($cfg['local']) && isset($cfg['server'])) {
            $sel = $useLocal ? $cfg['local'] : $cfg['server'];
        } else {
            $sel = $cfg;
        }

        $host = (string)($sel['host'] ?? $cfg['host'] ?? '127.0.0.1');
        $port = (int)($sel['port'] ?? $cfg['port'] ?? 3306);
        $db   = (string)($sel['db'] ?? $cfg['db'] ?? '');
        $user = (string)($sel['user'] ?? $cfg['user'] ?? '');
        $pass = (string)($sel['pass'] ?? $cfg['pass'] ?? '');

        if ($db === '') {
            throw new \RuntimeException('DB database name is empty.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) throw new \RuntimeException('DB not connected. Call DB::connect() first.');
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function row(string $sql, array $params = []): array|null
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    public static function insertGetId(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int)self::pdo()->lastInsertId();
    }
}
