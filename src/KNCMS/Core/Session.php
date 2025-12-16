<?php
declare(strict_types=1);

namespace KNCMS\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        // ini_set('session.cookie_secure', '1'); // enable on HTTPS
        session_start();
    }
}
