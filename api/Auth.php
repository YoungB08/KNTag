<?php
declare(strict_types=1);

use KNCMS\Http\Routes;
use KNCMS\KNCMS;
use KNCMS\Auth\JWT;
use KNCMS\Security\RateLimit;

if (!function_exists('json_in')) {
    function json_in(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('json_out')) {
    function json_out(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('auth_validate')) {
    function auth_validate(string $email, string $password): array
    {
        $fields = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fields['email'] = ['email'];
        }

        $len = strlen($password);
        if ($len < 6)  $fields['password'][] = 'min:6';
        if ($len > 72) $fields['password'][] = 'max:72';

        return $fields;
    }
}

/* =========================================================
 * SESSION + COOKIE AUTH (SET ON SUCCESS)
 * ======================================================= */

if (!function_exists('auth_is_https')) {
    function auth_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return true;
        return false;
    }
}

if (!function_exists('auth_session_start')) {
    function auth_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $secure = auth_is_https();

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        if ($secure) ini_set('session.cookie_secure', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax', // cross-site FE -> None (bắt buộc HTTPS)
        ]);

        session_start();
    }
}

if (!function_exists('auth_set_session_user')) {
    function auth_set_session_user(int $uid, string $email): void
    {
        auth_session_start();
        session_regenerate_id(true);

        $_user = KNCMS::get_row(
            "SELECT * FROM users WHERE id=:id LIMIT 1",
            ['id' => $uid]
        );
        $_SESSION['auth'] = [
            'uid' => $uid,
            'email' => $email,
            'nickname' => $_user['nickname'] ?? 'Guest User',
            'username' => $_user['username'] ?? '',
            'phone' => $_user['phone'] ?? '',
            'ts' => time(),
        ];
    }
}

if (!function_exists('auth_set_cookie_token')) {
    function auth_set_cookie_token(string $token, int $ttl = 86400): void
    {
        $secure = auth_is_https();

        setcookie('kn_token', $token, [
            'expires'  => time() + $ttl,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax', // cross-site FE -> None (bắt buộc HTTPS)
        ]);
    }
}

if (!function_exists('auth_clear_session_cookie')) {
    function auth_clear_session_cookie(): void
    {
        auth_session_start();

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'] ?? '/',
                'domain'   => $p['domain'] ?? '',
                'secure'   => (bool)($p['secure'] ?? false),
                'httponly' => (bool)($p['httponly'] ?? true),
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();

        setcookie('kn_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => auth_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/* =========================================================
 * ROUTES
 * ======================================================= */

if (!function_exists('register_auth_routes')) {
    function register_auth_routes(): void
    {
        Routes::post('/api/login', function () {
            $ip = KNCMS::getIp();
            if (!RateLimit::allow('login:' . $ip, 10, 60)) {
                json_out(['ok' => false, 'error' => 'RATE_LIMITED', 'message' => 'Too many requests.'], 429);
            }

            $in = json_in();
            $email = strtolower(trim((string)($in['email'] ?? '')));
            $password = (string)($in['password'] ?? '');

            $fields = auth_validate($email, $password);
            if ($fields) {
                json_out(['ok' => false, 'error' => 'VALIDATION_ERROR', 'fields' => $fields], 422);
            }

            $user = KNCMS::get_row(
                "SELECT id,email,password FROM users WHERE email=:e LIMIT 1",
                ['e' => $email]
            );

            if (!$user || !password_verify($password, (string)$user['password'])) {
                json_out([
                    'ok' => false,
                    'error' => 'INVALID_CREDENTIALS',
                    'message' => 'Email or password is incorrect.'
                ], 401);
            }

            $secret = $_ENV['JWT_SECRET'] ?? 'changeme';
            $ttl = 86400;

            $token = JWT::encode(
                ['sub' => (int)$user['id'], 'email' => (string)$user['email']],
                $secret,
                $ttl
            );

            // ✅ SET SESSION + COOKIE WHEN AUTH SUCCESS
            auth_set_session_user((int)$user['id'], (string)$user['email']);
            auth_set_cookie_token($token, $ttl);

            json_out([
                'ok' => true,
                'token' => $token,
                'user' => ['id' => (int)$user['id'], 'email' => (string)$user['email'], 'nickname' => (string)($user['nickname'] ?? ''), 'username' => (string)($user['username'] ?? '')]
            ], 200);
        });

        Routes::post('/api/register', function () {
            $ip = KNCMS::getIp();
            if (!RateLimit::allow('register:' . $ip, 5, 60)) {
                json_out(['ok' => false, 'error' => 'RATE_LIMITED', 'message' => 'Too many requests.'], 429);
            }

            $in = json_in();
            $email = strtolower(trim((string)($in['email'] ?? '')));
            $password = (string)($in['password'] ?? '');

            $fields = auth_validate($email, $password);
            if ($fields) {
                json_out(['ok' => false, 'error' => 'VALIDATION_ERROR', 'fields' => $fields], 422);
            }

            $exists = KNCMS::get_row("SELECT id FROM users WHERE email=:e LIMIT 1", ['e' => $email]);
            if ($exists) {
                json_out([
                    'ok' => false,
                    'error' => 'EMAIL_EXISTS',
                    'message' => 'Email already exists.'
                ], 409);
            }

            $hash = password_hash($password, PASSWORD_ARGON2ID);

            $uid = KNCMS::insert_id(
                "INSERT INTO users(email,password) VALUES(:e,:p)",
                ['e' => $email, 'p' => $hash]
            );

            $secret = $_ENV['JWT_SECRET'] ?? 'changeme';
            $ttl = 86400;

            $token = JWT::encode(
                ['sub' => (int)$uid, 'email' => $email],
                $secret,
                $ttl
            );

            // ✅ SET SESSION + COOKIE WHEN AUTH SUCCESS
            auth_set_session_user((int)$uid, $email);
            auth_set_cookie_token($token, $ttl);

            json_out([
                'ok' => true,
                'token' => $token,
                'user' => ['id' => (int)$uid, 'email' => $email]
            ], 200);
        });

        // (Optional) logout route
        Routes::post('/api/logout', function () {
            auth_clear_session_cookie();
            json_out(['ok' => true], 200);
        });

        // (Optional) check session route
        Routes::get('/api/me', function () {
            auth_session_start();
            $me = $_SESSION['auth'] ?? null;
            if (!$me) json_out(['ok' => false, 'error' => 'UNAUTHENTICATED'], 401);
            json_out(['ok' => true, 'user' => $me], 200);
        });
    }
}
