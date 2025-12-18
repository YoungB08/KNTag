<?php

declare(strict_types=1);

namespace KNCMS\Security;

final class RateLimit
{
    public static function allow(string $key, int $max, int $seconds): bool
    {
        $dir = sys_get_temp_dir() . '/kncms_rate';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $file = $dir . '/' . sha1($key) . '.json';
        $now = time();

        $data = ['count' => 0, 'reset' => $now + $seconds];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
            if ($now > $data['reset']) {
                $data = ['count' => 0, 'reset' => $now + $seconds];
            }
        }

        if ($data['count'] >= $max) return false;

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }
    private static string $dir = PROJECT_ROOT . '/storage/rl';

    public static function init(): void
    {
        if (!is_dir(self::$dir)) @mkdir(self::$dir, 0775, true);
    }

    /**
     * @return array{ok:bool, remaining:int, retry_after:int}
     */
    public static function hit(string $key, int $limit, int $windowSec): array
    {
        self::init();
        $now = time();
        $bucket = intdiv($now, $windowSec); // time bucket

        $safe = preg_replace('/[^a-zA-Z0-9_\-:.@]/', '_', $key);
        $file = self::$dir . "/{$safe}.json";

        $data = ['bucket' => $bucket, 'count' => 0];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $tmp = $raw ? json_decode($raw, true) : null;
            if (is_array($tmp)) $data = $tmp;
        }

        if (($data['bucket'] ?? -1) !== $bucket) {
            $data = ['bucket' => $bucket, 'count' => 0];
        }

        $data['count'] = (int)($data['count'] ?? 0) + 1;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        $ok = $data['count'] <= $limit;
        $remaining = max(0, $limit - $data['count']);
        $retryAfter = $ok ? 0 : (($bucket + 1) * $windowSec - $now);

        return ['ok' => $ok, 'remaining' => $remaining, 'retry_after' => $retryAfter];
    }
}
