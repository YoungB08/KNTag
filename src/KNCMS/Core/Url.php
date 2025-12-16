<?php
declare(strict_types=1);

namespace KNCMS\Core;

final class Url
{
    private static ?string $baseUrl = null;

    public static function init(): void
    {
        $envBase = Env::get('BASE_URL');

        if ($envBase) {
            self::$baseUrl = rtrim($envBase, '/');
            return;
        }

        // Auto detect
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // detect thư mục project (VD: /KNTag)
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('/index.php', '', $script);

        self::$baseUrl = rtrim($scheme . '://' . $host . $basePath, '/');
    }

    public static function base(): string
    {
        if (!self::$baseUrl) {
            self::init();
        }
        return self::$baseUrl;
    }

    public static function to(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        return self::base() . $path;
    }
}
