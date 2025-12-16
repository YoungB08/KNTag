<?php
declare(strict_types=1);

namespace KNCMS\Http;

final class Routes
{
    private static array $routes = [
        'GET'  => [],
        'POST' => [],
        'PUT'  => [],
        'DELETE' => [],
    ];

    public static function get(string $path, callable $handler): void
    {
        self::$routes['GET'][self::normalize($path)] = $handler;
    }
    public static function post(string $path, callable $handler): void
    {
        self::$routes['POST'][self::normalize($path)] = $handler;
    }
    public static function put(string $path, callable $handler): void
    {
        self::$routes['PUT'][self::normalize($path)] = $handler;
    }
    public static function delete(string $path, callable $handler): void
    {
        self::$routes['DELETE'][self::normalize($path)] = $handler;
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if (str_contains($path, 'index.php')) {
            $path = preg_replace('#^.*index\.php#', '', $path);
            $path = $path === '' ? '/' : $path;
        }

        $path = self::normalize($path);

        // 1) Match tĩnh trước (nhanh nhất)
        if (isset(self::$routes[$method][$path])) {
            call_user_func(self::$routes[$method][$path]);
            return;
        }

        // 2) Match động: /@{u}, /user/{id}, ...
        $matched = self::matchDynamic($method, $path);
        if ($matched !== null) {
            [$handler, $params] = $matched;
            call_user_func_array($handler, $params);
            return;
        }

        self::notFound($method, $path);
    }

    private static function matchDynamic(string $method, string $path): ?array
{
    $routes = self::$routes[$method] ?? [];
    if (!$routes) return null;

    foreach ($routes as $route => $handler) {
        if (!str_contains($route, '{')) continue;

        $paramNames = [];

        // Build regex: escape literal parts, replace {param} with a capture group
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^\/]+)';
        }, $route);

        // Escape ONLY the literal chars (after replacement we need to escape remaining regex meta)
        // So we escape route first, then re-inject capture groups. Easiest: do it in 2 passes:
        // 1) Temporarily mark params, 2) preg_quote, 3) restore capture groups.
        $tmp = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '___PARAM___', $route);
        $tmp = preg_quote($tmp, '#');
        $tmp = str_replace('___PARAM___', '([^\/]+)', $tmp);

        $regex = '#^' . $tmp . '$#';

        if (preg_match($regex, $path, $m)) {
            array_shift($m);

            // pass params by order
            $params = [];
            foreach ($m as $val) {
                $params[] = urldecode($val);
            }
            return [$handler, $params];
        }
    }

    return null;
}


    private static function normalize(string $path): string
    {
        if ($path === '') return '/';
        $path = '/' . ltrim($path, '/');
        return rtrim($path, '/') ?: '/';
    }

    private static function notFound(string $method, string $path): void
    {
        http_response_code(404);

        if (str_starts_with($path, '/api')) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'error' => 'NOT_FOUND',
                'method' => $method,
                'path' => $path
            ]);
            return;
        }

        echo "<h1>404</h1><p>Route <b>{$method} {$path}</b> not found.</p>";
    }
}
