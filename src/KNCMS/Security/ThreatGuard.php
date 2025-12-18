<?php
declare(strict_types=1);

namespace KNCMS\Security;

final class ThreatGuard
{
    public static function ip(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function path(): string
    {
        return (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    }

    public static function isSuspiciousPayload(string $raw): bool
    {
        $s = strtolower($raw);
        $patterns = [
            '<script', 'javascript:', 'onerror=', 'onload=', // XSS
            '../', '..\\', '%2e%2e%2f', '%2e%2e%5c',       // traversal
            'union select', 'sleep(', 'benchmark(', 'or 1=1', 'information_schema', // SQLi
            'curl/', 'sqlmap', 'nikto', 'masscan', 'nmap',  // tools
        ];
        foreach ($patterns as $p) {
            if (str_contains($s, $p)) return true;
        }
        return false;
    }

    public static function isBotUA(): bool
    {
        $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') return true;
        $bad = ['bot','spider','crawler','scrapy','python-requests','curl','wget','httpclient'];
        foreach ($bad as $w) if (str_contains($ua, $w)) return true;
        return false;
    }

    public static function readRawBody(int $maxBytes = 200_000): string
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw)) return '';
        return strlen($raw) > $maxBytes ? substr($raw, 0, $maxBytes) : $raw;
    }
}
