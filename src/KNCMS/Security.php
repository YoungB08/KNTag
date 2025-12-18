<?php 
use KNCMS\Http\Routes;
use KNCMS\Security\Logger;
use KNCMS\Security\RateLimit;
use KNCMS\Security\ThreatGuard;

Logger::init();

$ip   = ThreatGuard::ip();
$path = ThreatGuard::path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (str_starts_with($path, '/api')) {
    $rl = RateLimit::hit("api:{$ip}", 120, 60); // 120 req / 60s / IP

    if (!$rl['ok']) {
        Logger::security('API_RATE_LIMIT', [
            'ip' => $ip,
            'path' => $path,
            'method' => $method,
            'retry_after' => $rl['retry_after'],
        ]);

        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'RATE_LIMIT',
            'retry_after' => $rl['retry_after'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (in_array($method, ['POST','PUT','PATCH'], true)) {
    $raw = ThreatGuard::readRawBody();
    if ($raw !== '' && ThreatGuard::isSuspiciousPayload($raw)) {
        Logger::security('SUSPICIOUS_PAYLOAD', [
            'ip' => $ip,
            'path' => $path,
            'method' => $method,
            'sample' => substr($raw, 0, 600),
        ]);
    }
}

// 3) UA bot
if (str_starts_with($path, '/api') && ThreatGuard::isBotUA()) {
    Logger::warning('BOT_UA_HIT', ['ip' => $ip, 'path' => $path]);
}

