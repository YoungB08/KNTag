<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use KNCMS\KNCMS;

if (!KNCMS::checkLogin()) { header('Location: ' . KNCMS::baseUrl() . '/auth'); exit; }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Danh sách Card</title>
  <style>
    body{font-family:Inter,system-ui,Arial;margin:24px}
    .row{display:flex;gap:10px;align-items:center;justify-content:space-between;padding:12px 14px;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:10px}
    .btn{padding:8px 12px;border:1px solid #111827;border-radius:10px;background:#111827;color:#fff;text-decoration:none}
    .btn2{padding:8px 12px;border:1px solid #111827;border-radius:10px;background:#fff;color:#111827;text-decoration:none}
    .muted{color:#6b7280;font-size:13px}
  </style>
</head>
<body>
  <h2>Cards</h2>
  <div class="muted">Endpoint: /api/card/list</div>
  <div style="display:flex;gap:10px;margin:14px 0;">
    <a class="btn2" href="/pages/checkout_step2.php">Test checkout (nhập order_id)</a>
  </div>

  <div id="list"></div>

  <script>
  async function loadList(){
    const r = await fetch('/api/card/list', {credentials:'include'});
    const j = await r.json();
    const el = document.getElementById('list');
    if(!j.ok){ el.innerHTML = '<div class="muted">Lỗi: '+(j.error||'')+'</div>'; return; }
    const rows = (j.cards||[]).map(c=>`
      <div class="row">
        <div>
          <div><b>#${c.id}</b> ${c.title}</div>
          <div class="muted">${c.status} • ${c.updated_at}</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn" href="/pages/checkout_step2.php?card_id=${c.id}">Mua / Checkout</a>
        </div>
      </div>
    `).join('');
    el.innerHTML = rows || '<div class="muted">Chưa có card nào.</div>';
  }
  loadList();
  </script>
</body>
</html>
