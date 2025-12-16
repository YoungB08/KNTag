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
}
