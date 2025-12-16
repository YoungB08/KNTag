<?php
declare(strict_types=1);

namespace KNCMS\Core;

use Dotenv\Dotenv;

final class Env
{
    private static bool $loaded = false;

    public static function load(string $basePath): void
    {
        if (self::$loaded) return;
        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->safeLoad();
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) return $_ENV[$key];
        if (array_key_exists($key, $_SERVER)) return $_SERVER[$key];
        return $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key, null);
        if ($v === null) return $default;
        $s = strtolower(trim((string)$v));
        return in_array($s, ['1','true','yes','on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key, null);
        if ($v === null) return $default;
        return (int)$v;
    }
}
