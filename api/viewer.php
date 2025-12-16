<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;
use KNCMS\Http\Routes;

if (!function_exists('json_out')) {
  function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/**
 * Dashboard range cố định, KHÔNG lấy từ $_GET
 * đổi 14 -> 30 nếu muốn.
 */
const BIO_STATS_DAYS = 14;

/**
 * Resolve user + bioId từ nickname u
 */
function resolve_bio_by_u(string $u): array
{
  $u = trim($u);
  if ($u === '') json_out(['ok' => false, 'message' => 'Missing u'], 400);

  $user = KNCMS::get_row("SELECT id FROM users WHERE nickname=:n LIMIT 1", ['n' => $u]);
  if (!$user) json_out(['ok' => false, 'message' => 'User not found'], 404);

  $uid = (int)$user['id'];
  $bio = KNCMS::get_row("SELECT id, user_id, bg_style, font_style, bg_custom FROM bios WHERE user_id=:uid LIMIT 1", ['uid' => $uid]);
  if (!$bio) json_out(['ok' => false, 'message' => 'Bio not configured'], 404);

  return [
    'uid' => $uid,
    'bio' => $bio,
    'bioId' => (int)$bio['id'],
  ];
}

/**
 * Track view: pageview + unique/day theo fingerprint(ip+ua)
 * - pageview: luôn +1
 * - unique/day: chỉ +1 nếu fingerprint chưa tồn tại trong ngày
 */
function track_bio_view(int $bioId): void
{
  $today = date('Y-m-d');

  // 1) Pageview
  KNCMS::exec(
    "INSERT INTO bio_daily_views (bio_id, view_date, views, unique_views)
     VALUES (:bid, :d, 1, 0)
     ON DUPLICATE KEY UPDATE views = views + 1, updated_at = CURRENT_TIMESTAMP",
    ['bid' => $bioId, 'd' => $today]
  );

  // 2) Unique/day (best-effort)
  $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
     ?? $_SERVER['HTTP_X_FORWARDED_FOR']
     ?? $_SERVER['REMOTE_ADDR']
     ?? '0.0.0.0';

  // X_FORWARDED_FOR có thể là "ip1, ip2", lấy ip đầu
  if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
  }

  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $fp = hash('sha256', $ip . '|' . $ua);

  // check tồn tại trước để không phụ thuộc return rowCount của KNCMS::exec
  $exists = KNCMS::get_row(
    "SELECT 1 FROM bio_view_fingerprints WHERE bio_id=:bid AND view_date=:d AND fp=:fp LIMIT 1",
    ['bid' => $bioId, 'd' => $today, 'fp' => $fp]
  );

  if (!$exists) {
    // insert (có UNIQUE KEY nên an toàn tương đối, race nhỏ vẫn ok)
    KNCMS::exec(
      "INSERT INTO bio_view_fingerprints (bio_id, view_date, fp)
       VALUES (:bid, :d, :fp)",
      ['bid' => $bioId, 'd' => $today, 'fp' => $fp]
    );

    // tăng unique_views
    KNCMS::exec(
      "INSERT INTO bio_daily_views (bio_id, view_date, views, unique_views)
       VALUES (:bid, :d, 0, 1)
       ON DUPLICATE KEY UPDATE unique_views = unique_views + 1, updated_at = CURRENT_TIMESTAMP",
      ['bid' => $bioId, 'd' => $today]
    );
  }
}

/**
 * =================================================================
 *  1) VIEW ENDPOINT (có track view)
 *  GET /api/bio/view?u=nickname
 * =================================================================
 */
Routes::get('/api/bio/view', function () {

  $u = isset($_GET['u']) ? trim((string)$_GET['u']) : '';

  // Resolve user + bio
  $resolved = resolve_bio_by_u($u);
  $bio = $resolved['bio'];
  $bioId = $resolved['bioId'];

  // Load blocks
  $rows = KNCMS::get_list(
    "SELECT type, position, data_json
     FROM bio_blocks
     WHERE bio_id=:bid
     ORDER BY position ASC",
    ['bid' => $bioId]
  );

  // Build viewer payload
  $view = [
    'name'       => 'Guest User',
    'nickname'   => '',
    'role'       => '',
    'jobs'       => [],
    'intro'      => '',
    'avatarUrl'  => '',
    'coverUrl'   => (string)($bio['bg_custom'] ?? ''),
    'bg_custom'  => (string)($bio['bg_custom'] ?? ''),
    'bg_style'   => (string)($bio['bg_style'] ?? 'soft'),
    'font_style' => (string)($bio['font_style'] ?? 'inter'),
    'links'      => [],
    'ctaMain'    => null,
    'ctaContact' => null,
  ];

  if (is_array($rows)) {
    foreach ($rows as $r) {
      $type = (string)($r['type'] ?? '');
      $raw  = (string)($r['data_json'] ?? '');

      $d = [];
      if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $d = $decoded;
      }

      if ($type === 'avatar') {
        $view['avatarUrl'] = (string)($d['dataUrl'] ?? '');
      } elseif ($type === 'name') {
        $t = trim((string)($d['text'] ?? ''));
        if ($t !== '') $view['name'] = $t;
      } elseif ($type === 'nickname') {
        $view['nickname'] = (string)($d['text'] ?? '');
      } elseif ($type === 'role') {
        $view['role'] = (string)($d['text'] ?? '');
      } elseif ($type === 'jobs') {
        $rawJobs = (string)($d['text'] ?? '');
        $jobs = array_values(array_filter(array_map('trim', explode(',', $rawJobs))));
        $view['jobs'] = $jobs;
      } elseif ($type === 'intro') {
        $view['intro'] = (string)($d['text'] ?? '');
      } elseif ($type === 'text') {
        $t = trim((string)($d['text'] ?? ''));
        if ($t !== '') $view['intro'] = trim($view['intro'] . "\n" . $t);
      } elseif ($type === 'icon') {
        $view['links'][] = [
          'label'     => (string)($d['label'] ?? 'Link'),
          'url'       => (string)($d['url'] ?? ''),
          'sub'       => (string)($d['sub'] ?? ''),
          'iconClass' => (string)($d['iconClass'] ?? 'fa-solid fa-link'),
        ];
      }
    }
  }

  // Track view (đặt SAU resolve bioId, trước output)
  track_bio_view($bioId);

  json_out(['ok' => true, 'data' => $view], 200);
});

/**
 * =================================================================
 *  2) STATS ENDPOINT (days tự động = BIO_STATS_DAYS)
 *  GET /api/bio/stats/views?u=nickname
 * =================================================================
 */
Routes::get('/api/bio/stats/views', function () {

  $u = isset($_GET['u']) ? trim((string)$_GET['u']) : '';
  $resolved = resolve_bio_by_u($u);
  $bioId = (int)$resolved['bioId'];

  $days = BIO_STATS_DAYS;

  $end = new DateTime('today');
  $start = (clone $end)->modify('-' . ($days - 1) . ' day');

  $rows = KNCMS::get_list(
    "SELECT view_date, views, unique_views
     FROM bio_daily_views
     WHERE bio_id=:bid
       AND view_date BETWEEN :s AND :e
     ORDER BY view_date ASC",
    [
      'bid' => $bioId,
      's'   => $start->format('Y-m-d'),
      'e'   => $end->format('Y-m-d'),
    ]
  );

  $map = [];
  if (is_array($rows)) {
    foreach ($rows as $r) {
      $d = (string)($r['view_date'] ?? '');
      if ($d === '') continue;
      $map[$d] = [
        'date'   => $d,
        'views'  => (int)($r['views'] ?? 0),
        'unique' => (int)($r['unique_views'] ?? 0),
      ];
    }
  }

  $daily = [];
  $it = clone $start;
  while ($it <= $end) {
    $d = $it->format('Y-m-d');
    $daily[] = $map[$d] ?? ['date' => $d, 'views' => 0, 'unique' => 0];
    $it->modify('+1 day');
  }

  $totalViews = 0;
  $totalUnique = 0;
  foreach ($daily as $x) {
    $totalViews += (int)$x['views'];
    $totalUnique += (int)$x['unique'];
  }

  json_out([
    'ok' => true,
    'data' => [
      'bio_id' => $bioId,
      'days'   => $days,
      'range'  => ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')],
      'total'  => ['views' => $totalViews, 'unique' => $totalUnique],
      'daily'  => $daily,
    ]
  ], 200);
});
