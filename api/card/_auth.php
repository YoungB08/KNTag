<?php
declare(strict_types=1);

use KNCMS\KNCMS;

function card_require_login(): array {
  if (!KNCMS::checkLogin()) {
    card_json_out(['ok'=>false,'error'=>'UNAUTHORIZED'], 401);
  }
  $u = KNCMS::getUserInfo();
  if (!$u) {
    card_json_out(['ok'=>false,'error'=>'UNAUTHORIZED'], 401);
  }
  return $u;
}

function card_require_card_owner(int $cardId, int $uid): void {
  $row = KNCMS::get_row('SELECT id,user_id FROM cards WHERE id=:id LIMIT 1', ['id'=>$cardId]);
  if (!$row) card_json_out(['ok'=>false,'error'=>'CARD_NOT_FOUND'], 404);
  if ((int)$row['user_id'] !== $uid) card_json_out(['ok'=>false,'error'=>'FORBIDDEN'], 403);
}

function card_require_order_owner(int $orderId, int $uid): array {
  $row = KNCMS::get_row('SELECT * FROM card_orders WHERE id=:id LIMIT 1', ['id'=>$orderId]);
  if (!$row) card_json_out(['ok'=>false,'error'=>'ORDER_NOT_FOUND'], 404);
  if ((int)$row['user_id'] !== $uid) card_json_out(['ok'=>false,'error'=>'FORBIDDEN'], 403);
  return $row;
}
