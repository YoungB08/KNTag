<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;
use KNCMS\Http\Routes;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!function_exists('json_out')) {
  function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }
}

function require_uid(): int {
  $uid = $_SESSION['uid'] ?? null;
  if (is_int($uid)) return $uid;
  if (is_string($uid) && ctype_digit($uid)) return (int)$uid;
  json_out(['ok' => false, 'message' => 'Unauthorized'], 401);
  return 0;
}

function my_bio_id(int $uid): int {
  $bio = KNCMS::get_row("SELECT id FROM bios WHERE user_id=:uid LIMIT 1", ['uid'=>$uid]);
  if (!$bio) json_out(['ok'=>false,'message'=>'Bio not configured'], 404);
  return (int)$bio['id'];
}

function my_nickname(int $uid): string {
  $u = KNCMS::get_row("SELECT nickname FROM users WHERE id=:id LIMIT 1", ['id'=>$uid]);
  return (string)($u['nickname'] ?? '');
}

/**
 * KPIs: activeBio, clicks7d, nfcCount
 * - activeBio: 1 nếu có bio (hoặc trạng thái public nếu bro có cột status)
 * - clicks7d: hiện chưa có table click => để 0 (sau bro đưa schema click, t map)
 * - nfcCount: nếu bro có table nfc_cards; không có thì 0
 */
Routes::get('/api/dashboard/kpis', function () {
  $uid = require_uid();
  $bioId = my_bio_id($uid);

  $activeBio = 1;

  $sum14 = KNCMS::get_row(
    "SELECT COALESCE(SUM(views),0) AS s
     FROM bio_daily_views
     WHERE bio_id=:bid AND view_date >= (CURDATE() - INTERVAL 13 DAY)",
    ['bid'=>$bioId]
  );
  $views14d = (int)($sum14['s'] ?? 0);

  // clicks7d: chưa có schema -> 0
  $clicks7d = 0;

  // NFC count: nếu bro có table nfc_cards(user_id), mở comment
  $nfcCount = 0;
  // $n = KNCMS::get_row("SELECT COUNT(*) AS c FROM nfc_cards WHERE user_id=:uid", ['uid'=>$uid]);
  // $nfcCount = (int)($n['c'] ?? 0);

  json_out(['ok'=>true,'data'=>[
    'activeBio'=>$activeBio,
    'views14d'=>$views14d,
    'clicks7d'=>$clicks7d,
    'nfcCount'=>$nfcCount
  ]], 200);
});

/**
 * Links lấy từ bio_blocks type=icon
 */
Routes::get('/api/dashboard/links', function () {
  $uid = require_uid();
  $bioId = my_bio_id($uid);

  $rows = KNCMS::get_list(
    "SELECT id, data_json
     FROM bio_blocks
     WHERE bio_id=:bid AND type='icon'
     ORDER BY position ASC",
    ['bid'=>$bioId]
  );

  $links = [];
  if (is_array($rows)) {
    foreach ($rows as $r) {
      $raw = (string)($r['data_json'] ?? '');
      $d = $raw ? json_decode($raw, true) : [];
      if (!is_array($d)) $d = [];
      $links[] = [
        'id' => (int)($r['id'] ?? 0),
        'label' => (string)($d['label'] ?? 'Link'),
        'url' => (string)($d['url'] ?? ''),
        'iconClass' => (string)($d['iconClass'] ?? 'fa-solid fa-link'),
      ];
    }
  }

  json_out(['ok'=>true,'data'=>['links'=>$links]], 200);
});

/**
 * Activity: demo (vì bro chưa đưa schema logs)
 * Nếu bro có table activity_logs thì t map thẳng.
 */
Routes::get('/api/dashboard/activity', function () {
  $uid = require_uid();

  $items = [
    ['title'=>'Đăng nhập thành công','sub'=>'Phiên làm việc đã được tạo.','time'=>'Hôm nay','level'=>''],
    ['title'=>'Mở Dashboard','sub'=>'Tải dữ liệu thống kê và links.','time'=>'Vừa xong','level'=>''],
  ];

  json_out(['ok'=>true,'data'=>['items'=>$items]], 200);
});

/**
 * Export: xuất JSON dashboard (kpis + stats + links)
 */
Routes::get('/api/dashboard/export', function () {
  $uid = require_uid();
  $nick = my_nickname($uid);
  $bioId = my_bio_id($uid);

  $stats = KNCMS::get_list(
    "SELECT view_date, views, unique_views
     FROM bio_daily_views
     WHERE bio_id=:bid AND view_date >= (CURDATE() - INTERVAL 13 DAY)
     ORDER BY view_date ASC",
    ['bid'=>$bioId]
  );

  $linksRows = KNCMS::get_list(
    "SELECT id, data_json FROM bio_blocks WHERE bio_id=:bid AND type='icon' ORDER BY position ASC",
    ['bid'=>$bioId]
  );

  $links = [];
  if (is_array($linksRows)) {
    foreach ($linksRows as $r) {
      $d = json_decode((string)($r['data_json'] ?? ''), true);
      if (!is_array($d)) $d = [];
      $links[] = [
        'id'=>(int)($r['id']??0),
        'label'=>(string)($d['label']??'Link'),
        'url'=>(string)($d['url']??''),
        'iconClass'=>(string)($d['iconClass']??'fa-solid fa-link'),
      ];
    }
  }

  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="knbiocard_dashboard_export.json"');

  echo json_encode([
    'ok'=>true,
    'user'=>['uid'=>$uid,'nickname'=>$nick],
    'bio'=>['bio_id'=>$bioId],
    'stats14d'=>$stats ?: [],
    'links'=>$links
  ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
});
