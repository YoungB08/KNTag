<?php
declare(strict_types=1);

require __DIR__ . '/_boot.php';
require __DIR__ . '/_res.php';
require __DIR__ . '/_auth.php';
require __DIR__ . '/_upload.php';
require __DIR__ . '/systems.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '';
$system = '';

if (preg_match('#/api/card/([a-zA-Z0-9_-]+)$#', $path, $m)) {
  $system = strtolower($m[1]);
} else {
  $system = strtolower((string)($_GET['system'] ?? ''));
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

dispatch_card_system($system, $method);
card_json_out(['ok'=>false,'error'=>'NOT_FOUND'], 404);
