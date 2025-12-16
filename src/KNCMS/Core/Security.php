<?php
declare(strict_types=1);

namespace KNCMS\Core;

final class Security
{
    public static function e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function antiText(string $s): string
    {
        $s = html_entity_decode(trim($s), ENT_QUOTES, 'UTF-8');
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', '', $s);
        $s = preg_replace('/\s+/u', ' ', (string)$s);
        return trim((string)$s);
    }

    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!$token || empty($_SESSION['_csrf'])) return false;
        return hash_equals((string)$_SESSION['_csrf'], (string)$token);
    }
}
