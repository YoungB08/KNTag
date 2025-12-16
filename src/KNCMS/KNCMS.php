<?php

declare(strict_types=1);

namespace KNCMS;

use KNCMS\Core\Env;
use KNCMS\Core\Security;
use KNCMS\Core\Session;
use KNCMS\Database\DB;
use KNCMS\Network\Network;
use KNCMS\User\UserHelper;
use KNCMS\Utils\Formatter;
use KNCMS\Utils\Text;
use KNCMS\Core\Url;
use KNCMS\Auth\JWT;

define('PROJECT_ROOT', $_SERVER['DOCUMENT_ROOT']);

KNCMS::boot(PROJECT_ROOT);
final class KNCMS
{
    public static function boot(string $basePath): void
    {
        Session::start();
        Env::load($basePath);

        Url::init();
        DB::connect([
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::int('DB_PORT', 3306),
            'db'   => Env::get('DB_NAME', ''),
            'user' => Env::get('DB_USER', ''),
            'pass' => Env::get('DB_PASSWORD', ''),
        ]);
    }

    // ===== Network =====
    public static function getIp(): string
    {
        return Network::getIp();
    }
    public static function curl_get(string $url, int $timeoutSeconds = 10): string
    {
        return Network::curlGet($url, $timeoutSeconds);
    }

    // ===== Database (SQLi-safe) =====
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        return DB::query($sql, $params);
    }
    public static function get_row(string $sql, array $params = []): array|false
    {
        return DB::row($sql, $params) ?? false;
    }
    public static function get_list(string $sql, array $params = []): array
    {
        return DB::all($sql, $params);
    }
    public static function exec(string $sql, array $params = []): int
    {
        return DB::exec($sql, $params);
    }
    public static function insert_id(string $sql, array $params = []): int
    {
        return DB::insertGetId($sql, $params);
    }

    // ===== Security (XSS/CSRF) =====
    public static function e(?string $s): string
    {
        return Security::e($s);
    }
    public static function anti_text(string $s): string
    {
        return Security::antiText($s);
    }
    public static function csrfToken(): string
    {
        return Security::csrfToken();
    }
    public static function verifyCsrf(?string $token): bool
    {
        return Security::verifyCsrf($token);
    }

    // ===== User =====
    public static function capbac(int $level): string
    {
        return UserHelper::capbac($level);
    }

    // ===== Utils =====
    public static function format_cash(float|int $price): string
    {
        return Formatter::format_cash($price);
    }
    public static function to_slug(string $str): string
    {
        return Text::toSlug($str);
    }

    public static function baseUrl(): string
    {
        return Url::base();
    }

    public static function url(string $path = ''): string
    {
        return Url::to($path);
    }

    // ===== Authentication =====
    /**
     * Check if current request is authenticated.
     * Strategy:
     *  - ensure session is started and check common session keys (`user`, `user_id`)
     *  - if not set, try to validate a JWT from cookie `jwt` or Authorization header
     *
     * @return bool
     */
    public static function checkLogin(): bool
    {
        // ưu tiên session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!empty($_SESSION['auth']['uid'])) {
            return true;
        }

        // fallback: check JWT trong cookie
        if (!empty($_COOKIE['kn_token'])) {
            $token = (string)$_COOKIE['kn_token'];
            $secret = $_ENV['JWT_SECRET'] ?? 'changeme';

            try {
                $payload = \KNCMS\Auth\JWT::decode($token, $secret);
                if (!empty($payload['sub'])) {
                    // auto sync lại session
                    $_SESSION['auth'] = [
                        'uid' => (int)$payload['sub'],
                        'email' => (string)($payload['email'] ?? ''),
                        'ts' => time(),
                    ];
                    return true;
                }
            } catch (\Throwable $e) {
                // token invalid / expired
                return false;
            }
        }

        return false;
    }
    public static function getUserInfo(): ?array
    {
        if (!self::checkLogin()) {
            return null;
        }

        $userId = (int)($_SESSION['auth']['uid'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $user = self::get_row('SELECT * FROM users WHERE id=:id LIMIT 1', ['id' => $userId]);
        return $user === false ? null : $user;
    }
}
