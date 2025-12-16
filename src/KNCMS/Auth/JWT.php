<?php
declare(strict_types=1);

namespace KNCMS\Auth;

final class JWT
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode(array $payload, string $secret, int $ttl = 86400): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;

        $h = self::base64UrlEncode(json_encode($header));
        $p = self::base64UrlEncode(json_encode($payload));

        $sig = hash_hmac('sha256', "$h.$p", $secret, true);
        $s = self::base64UrlEncode($sig);

        return "$h.$p.$s";
    }

    public static function decode(string $jwt, string $secret): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        [$h, $p, $s] = $parts;

        $check = self::base64UrlEncode(
            hash_hmac('sha256', "$h.$p", $secret, true)
        );

        if (!hash_equals($check, $s)) return null;

        $payload = json_decode(self::base64UrlDecode($p), true);
        if (!$payload || ($payload['exp'] ?? 0) < time()) return null;

        return $payload;
    }
}
