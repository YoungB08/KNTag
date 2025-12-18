<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use KNCMS\KNCMS;

if (!KNCMS::checkLogin()) { header('Location: ' . KNCMS::baseUrl() . '/auth'); exit; }
$orderId = (int)($_GET['order_id'] ?? 0);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Thanh toán COD</title>
  <style>
    body{font-family:Inter,system-ui,Arial;margin:24px;max-width:760px}
    .card{border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:12px}
    button{padding:10px 14px;border-radius:10px;border:1px solid #111827;background:#111827;color:#fff;cursor:pointer}
    pre{white-space:pre-wrap;background:#0b1020;color:#e5e7eb;padding:12px;border-radius:12px;}
    .muted{color:#6b7280;font-size:13px}
  </style>
</head>
<body>
  <h2>COD</h2>
  <div class="muted">Order: <?= $orderId ?> • API: /api/card/pay_cod_confirm</div>

  <div class="card">
    <h3>Xác nhận COD</h3>
    <div>Đơn sẽ được xử lý và giao hàng, bạn thanh toán khi nhận.</div>
    <div style="margin-top:10px">
      <button id="btn">Xác nhận COD</button>
    </div>
  </div>

  <pre id="log"></pre>

<script>
const log = (x)=>document.getElementById('log').textContent = typeof x==='string'?x:JSON.stringify(x,null,2);

document.getElementById('btn').addEventListener('click', async ()=>{
  try{
    const order_id = <?= $orderId ?>;
    const r = await fetch('/api/card/pay_cod_confirm', {
      method:'POST',
      credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({order_id})
    });
    const j = await r.json();
    log(j);
  }catch(e){ log({ok:false,error:String(e.message||e)}); }
});
</script>
</body>
</html>
