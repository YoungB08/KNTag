<?php
declare(strict_types=1);

use KNCMS\KNCMS;

function dispatch_card_system(string $system, string $method): void {
  $user = card_require_login();
  $uid = (int)($user['id'] ?? 0);
  if ($uid <= 0) card_json_out(['ok'=>false,'error'=>'UNAUTHORIZED'], 401);

  switch ($system) {
    case 'me':
      if ($method !== 'GET') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $u = KNCMS::get_row('SELECT id,email,level,username FROM users WHERE id=:id LIMIT 1', ['id'=>$uid]);
      card_json_out(['ok'=>true,'user'=>$u ?: ['id'=>$uid]]);
      break;

    case 'list':
      if ($method !== 'GET') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $rows = KNCMS::get_list(
        'SELECT id,title,status,thumb_url,created_at,updated_at FROM cards WHERE user_id=:uid ORDER BY id DESC',
        ['uid'=>$uid]
      );
      card_json_out(['ok'=>true,'cards'=>$rows]);
      break;

    case 'get':
      if ($method !== 'GET') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) card_json_out(['ok'=>false,'error'=>'BAD_ID'], 400);
      card_require_card_owner($id, $uid);
      $row = KNCMS::get_row('SELECT * FROM cards WHERE id=:id AND user_id=:uid LIMIT 1', ['id'=>$id,'uid'=>$uid]);
      if (!$row) card_json_out(['ok'=>false,'error'=>'NOT_FOUND'], 404);
      $row['layout'] = json_decode((string)$row['layout_json'], true);
      unset($row['layout_json']);
      card_json_out(['ok'=>true,'card'=>$row]);
      break;

    case 'save':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $in = card_read_json();
      $id = (int)($in['id'] ?? 0);
      $title = trim((string)($in['title'] ?? 'Untitled Card'));
      $status = (string)($in['status'] ?? 'draft');
      $layout = $in['layout'] ?? null;

      if (!is_array($layout)) card_json_out(['ok'=>false,'error'=>'INVALID_LAYOUT'], 422);
      if (!in_array($status, ['draft','active','archived'], true)) $status = 'draft';

      if ($id <= 0) {
        $newId = KNCMS::insert_id(
          'INSERT INTO cards(user_id,title,status,layout_json,created_at,updated_at)
           VALUES(:uid,:title,:status,:layout,NOW(),NOW())',
          ['uid'=>$uid,'title'=>$title,'status'=>$status,'layout'=>json_encode($layout, JSON_UNESCAPED_UNICODE)]
        );
        card_json_out(['ok'=>true,'id'=>(int)$newId,'created'=>true], 201);
      }

      card_require_card_owner($id, $uid);
      KNCMS::exec(
        'UPDATE cards SET title=:title,status=:status,layout_json=:layout,updated_at=NOW()
         WHERE id=:id AND user_id=:uid LIMIT 1',
        ['id'=>$id,'uid'=>$uid,'title'=>$title,'status'=>$status,'layout'=>json_encode($layout, JSON_UNESCAPED_UNICODE)]
      );
      card_json_out(['ok'=>true,'id'=>$id,'updated'=>true]);
      break;

    case 'delete':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $in = card_read_json();
      $id = (int)($in['id'] ?? 0);
      if ($id <= 0) card_json_out(['ok'=>false,'error'=>'BAD_ID'], 400);
      card_require_card_owner($id, $uid);
      KNCMS::exec('DELETE FROM cards WHERE id=:id AND user_id=:uid LIMIT 1', ['id'=>$id,'uid'=>$uid]);
      card_json_out(['ok'=>true]);
      break;

    case 'upload_image':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      if (empty($_FILES['file'])) card_json_out(['ok'=>false,'error'=>'NO_FILE'], 400);
      $info = card_upload_image_file($_FILES['file'], $uid);
      $upId = KNCMS::insert_id(
        'INSERT INTO uploads(user_id,kind,url,created_at) VALUES(:uid,"image",:url,NOW())',
        ['uid'=>$uid,'url'=>$info['url']]
      );
      card_json_out(['ok'=>true,'upload'=>['id'=>(int)$upId,'url'=>$info['url']]]);
      break;

    case 'checkout_create':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $in = card_read_json();
      $cardId = (int)($in['card_id'] ?? 0);
      $qty = max(1, (int)($in['quantity'] ?? 1));
      $amount = max(0, (int)($in['amount'] ?? 149000));
      if ($cardId <= 0) card_json_out(['ok'=>false,'error'=>'BAD_CARD_ID'], 400);
      card_require_card_owner($cardId, $uid);

      $orderId = KNCMS::insert_id(
        'INSERT INTO card_orders(user_id,card_id,quantity,amount,status,created_at,updated_at)
         VALUES(:uid,:cid,:qty,:amt,"draft",NOW(),NOW())',
        ['uid'=>$uid,'cid'=>$cardId,'qty'=>$qty,'amt'=>$amount]
      );
      card_json_out(['ok'=>true,'order_id'=>(int)$orderId,'status'=>'draft'], 201);
      break;

    case 'checkout_shipping':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $in = card_read_json();
      $orderId = (int)($in['order_id'] ?? 0);
      if ($orderId <= 0) card_json_out(['ok'=>false,'error'=>'BAD_ORDER_ID'], 400);
      card_require_order_owner($orderId, $uid);

      $shipping = $in['shipping'] ?? [];
      if (!is_array($shipping)) $shipping = [];

      $full = trim((string)($shipping['full_name'] ?? ''));
      $phone = trim((string)($shipping['phone'] ?? ''));
      $addr1 = trim((string)($shipping['address_line'] ?? ''));
      $prov = trim((string)($shipping['province'] ?? ''));
      $dist = trim((string)($shipping['district'] ?? ''));
      $ward = trim((string)($shipping['ward'] ?? ''));
      $note = trim((string)($shipping['note'] ?? ''));

      if ($full==='' || $phone==='' || $addr1==='' || $prov==='' || $dist==='' || $ward==='') {
        card_json_out(['ok'=>false,'error'=>'MISSING_SHIPPING_FIELDS'], 422);
      }

      $shipJson = json_encode([
        'full_name'=>$full,'phone'=>$phone,'address_line'=>$addr1,
        'province'=>$prov,'district'=>$dist,'ward'=>$ward,'note'=>$note
      ], JSON_UNESCAPED_UNICODE);

      KNCMS::exec(
        'UPDATE card_orders SET shipping_json=:ship, updated_at=NOW() WHERE id=:id AND user_id=:uid LIMIT 1',
        ['ship'=>$shipJson,'id'=>$orderId,'uid'=>$uid]
      );
      card_json_out(['ok'=>true]);
      break;

    case 'checkout_payment':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $in = card_read_json();
      $orderId = (int)($in['order_id'] ?? 0);
      $methodPay = (string)($in['method'] ?? '');
      if ($orderId <= 0) card_json_out(['ok'=>false,'error'=>'BAD_ORDER_ID'], 400);
      if (!in_array($methodPay, ['banking','cod'], true)) card_json_out(['ok'=>false,'error'=>'INVALID_METHOD'], 422);

      $order = card_require_order_owner($orderId, $uid);
      if (empty($order['shipping_json'])) card_json_out(['ok'=>false,'error'=>'SHIPPING_REQUIRED'], 422);

      KNCMS::exec(
        'UPDATE card_orders SET payment_method=:m, status="pending_payment", updated_at=NOW()
         WHERE id=:id AND user_id=:uid LIMIT 1',
        ['m'=>$methodPay,'id'=>$orderId,'uid'=>$uid]
      );
      card_json_out(['ok'=>true,'payment_method'=>$methodPay,'status'=>'pending_payment']);
      break;

    case 'order_get':
      if ($method !== 'GET') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $orderId = (int)($_GET['id'] ?? 0);
      if ($orderId <= 0) card_json_out(['ok'=>false,'error'=>'BAD_ORDER_ID'], 400);
      $order = card_require_order_owner($orderId, $uid);
      $order['shipping'] = $order['shipping_json'] ? json_decode((string)$order['shipping_json'], true) : null;
      unset($order['shipping_json']);
      card_json_out(['ok'=>true,'order'=>$order]);
      break;

    case 'pay_banking_confirm':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $in = card_read_json();
      $orderId = (int)($in['order_id'] ?? 0);
      $reference = trim((string)($in['reference'] ?? ''));
      if ($orderId <= 0 || $reference==='') card_json_out(['ok'=>false,'error'=>'MISSING_FIELDS'], 422);

      $order = card_require_order_owner($orderId, $uid);
      if ((string)$order['payment_method'] !== 'banking') card_json_out(['ok'=>false,'error'=>'PAYMENT_METHOD_MISMATCH'], 422);

      KNCMS::insert_id(
        'INSERT INTO card_payments(order_id,method,status,meta_json,created_at)
         VALUES(:oid,"banking","user_confirmed",:meta,NOW())',
        ['oid'=>$orderId,'meta'=>json_encode(['reference'=>$reference], JSON_UNESCAPED_UNICODE)]
      );

      KNCMS::exec(
        'UPDATE card_orders SET status="payment_review", updated_at=NOW() WHERE id=:id AND user_id=:uid LIMIT 1',
        ['id'=>$orderId,'uid'=>$uid]
      );
      card_json_out(['ok'=>true,'status'=>'payment_review']);
      break;

    case 'pay_cod_confirm':
      if ($method !== 'POST') card_json_out(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED'], 405);
      $in = card_read_json();
      $orderId = (int)($in['order_id'] ?? 0);
      if ($orderId <= 0) card_json_out(['ok'=>false,'error'=>'BAD_ORDER_ID'], 400);

      $order = card_require_order_owner($orderId, $uid);
      if ((string)$order['payment_method'] !== 'cod') card_json_out(['ok'=>false,'error'=>'PAYMENT_METHOD_MISMATCH'], 422);

      KNCMS::insert_id(
        'INSERT INTO card_payments(order_id,method,status,meta_json,created_at)
         VALUES(:oid,"cod","cod_selected",:meta,NOW())',
        ['oid'=>$orderId,'meta'=>json_encode(new stdClass(), JSON_UNESCAPED_UNICODE)]
      );

      KNCMS::exec(
        'UPDATE card_orders SET status="pending", updated_at=NOW() WHERE id=:id AND user_id=:uid LIMIT 1',
        ['id'=>$orderId,'uid'=>$uid]
      );
      card_json_out(['ok'=>true,'status'=>'pending']);
      break;

    default:
      // fallthrough
      break;
  }
}
