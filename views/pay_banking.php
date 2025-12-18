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
  <title>Thanh toán Banking</title>
  <style>
    body{font-family:Inter,system-ui,Arial;margin:24px;max-width:760px}
    .card{border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:12px}
    input{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px}
    button{padding:10px 14px;border-radius:10px;border:1px solid #111827;background:#111827;color:#fff;cursor:pointer}
    .muted{color:#6b7280;font-size:13px}
    pre{white-space:pre-wrap;background:#0b1020;color:#e5e7eb;padding:12px;border-radius:12px;}
  </style>
</head>
<body>
  <h2>Banking</h2>
  <div class="muted">Order: <?= $orderId ?> • API: /api/card/pay_banking_confirm</div>

  <div class="card">
    <h3>Thông tin chuyển khoản (demo)</h3>
    <div>Ngân hàng: <b>VCB</b></div>
    <div>STK: <b>0123456789</b></div>
    <div>Chủ TK: <b>KNTech</b></div>
    <div>Nội dung CK: <b>KN <?= $orderId ?></b></div>
  </div>

  <div class="card">
    <label>Mã tham chiếu / nội dung chuyển khoản thực tế</label>
    <input id="ref" placeholder="VD: VCB-20251218-XXXX">
    <div style="margin-top:10px">
      <button id="btn">Tôi đã chuyển khoản</button>
    </div>
  </div>

  <pre id="log"></pre>

<script>
const log = (x)=>document.getElementById('log').textContent = typeof x==='string'?x:JSON.stringify(x,null,2);

document.getElementById('btn').addEventListener('click', async ()=>{
  try{
    const order_id = <?= $orderId ?>;
    const reference = document.getElementById('ref').value.trim();
    const r = await fetch('/api/card/pay_banking_confirm', {
      method:'POST',
      credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({order_id, reference})
    });
    const j = await r.json();
    log(j);
  }catch(e){ log({ok:false,error:String(e.message||e)}); }
});
</script>
</body>
</html>
