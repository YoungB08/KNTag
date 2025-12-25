<?php
declare(strict_types=1);

use KNCMS\KNCMS;
require __DIR__ . '/_boot.php';
require __DIR__ . '/_res.php';
require __DIR__ . '/_auth.php';
require __DIR__ . '/_upload.php';
require __DIR__ . '/systems.php';
function card_list(): void
{
    if (!KNCMS::checkLogin()) {
        json_out(false, null, "UNAUTHORIZED", 401);
    }

    $u = KNCMS::getUserInfo();
    if (!$u || empty($u['id'])) {
        json_out(false, null, "USER_NOT_FOUND", 401);
    }

    $uid = (int)$u['id'];

    $q = trim((string)($_GET['q'] ?? ''));
    $status = trim((string)($_GET['status'] ?? 'all'));

    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 12);
    if ($limit < 1) $limit = 12;
    if ($limit > 50) $limit = 50;

    $offset = ($page - 1) * $limit;

    $where = " WHERE user_id = :uid ";
    $params = ['uid' => $uid];

    if ($status !== '' && $status !== 'all') {
        $allow = ['draft','active','archived'];
        if (!in_array($status, $allow, true)) {
            json_out(false, null, "INVALID_STATUS", 400);
        }
        $where .= " AND status = :status ";
        $params['status'] = $status;
    }

    if ($q !== '') {
        $where .= " AND title LIKE :q ";
        $params['q'] = '%' . $q . '%';
    }

    // total
    $row = KNCMS::get_row("SELECT COUNT(*) AS c FROM cards {$where}", $params);
    $total = (int)($row['c'] ?? 0);

    // list
    $sql = "SELECT id, title, status, thumb_url, created_at, updated_at
            FROM cards
            {$where}
            ORDER BY updated_at DESC, id DESC
            LIMIT {$limit} OFFSET {$offset}";

    $cards = KNCMS::get_list($sql, $params);

    json_out(true, [
        'cards' => $cards,
        'paging' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total
        ]
    ]);
}
