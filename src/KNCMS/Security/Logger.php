<?php
declare(strict_types=1);

namespace KNCMS\Security;

final class Logger
{
    public const USER    = 'USER';
    public const SYSTEM  = 'SYSTEM';
    public const WARNING = 'WARNING';
    public const SECURITY= 'SECURITY';

    private static string $logDir = PROJECT_ROOT . '/storage/logs';

    public static function init(?string $dir = null): void
    {
        if ($dir) self::$logDir = rtrim($dir, '/');
        if (!is_dir(self::$logDir)) @mkdir(self::$logDir, 0775, true);
    }

    public static function user(string $event, array $ctx = []): void
    {
        self::write(self::USER, $event, $ctx);
    }

    public static function system(string $event, array $ctx = []): void
    {
        self::write(self::SYSTEM, $event, $ctx);
    }

    public static function warning(string $event, array $ctx = []): void
    {
        self::write(self::WARNING, $event, $ctx);
    }

    public static function security(string $event, array $ctx = []): void
    {
        self::write(self::SECURITY, $event, $ctx);
    }

    private static function write(string $level, string $event, array $ctx): void
    {
        self::init();

        $row = [
            'ts'    => gmdate('c'),
            'level' => $level,
            'event' => $event,
            'req'   => self::reqMeta(),
            'ctx'   => self::sanitize($ctx),
        ];

        $file = self::$logDir . '/' . strtolower($level) . '-' . gmdate('Y-m-d') . '.log';
        @file_put_contents($file, json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function reqMeta(): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $rid = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

        return [
            'rid' => $rid,
            'ip'  => $ip,
            'xff' => $xff,
            'ua'  => mb_substr($ua, 0, 220),
            'm'   => $method,
            'uri' => mb_substr($uri, 0, 500),
        ];
    }

    private static function sanitize(array $ctx): array
    {
        // Tránh log quá nặng + tránh lộ secrets
        $denyKeys = ['password','pass','token','authorization','jwt','cookie','set-cookie'];
        $out = [];

        foreach ($ctx as $k => $v) {
            $lk = strtolower((string)$k);
            if (in_array($lk, $denyKeys, true)) {
                $out[$k] = '[REDACTED]';
                continue;
            }
            if (is_string($v)) {
                $out[$k] = mb_substr($v, 0, 2000);
            } elseif (is_array($v)) {
                $out[$k] = self::sanitize($v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
