<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use KNCMS\KNCMS;

if (!KNCMS::checkLogin()) { header('Location: ' . KNCMS::baseUrl() . '/auth'); exit; }

$cardId = (int)($_GET['card_id'] ?? 0);
$orderId = (int)($_GET['order_id'] ?? 0);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Checkout Step 2</title>
  <style>
    body{font-family:Inter,system-ui,Arial;margin:24px;max-width:760px}
    .card{border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:12px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .grid1{display:grid;grid-template-columns:1fr;gap:10px}
    input,select{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px}
    button{padding:10px 14px;border-radius:10px;border:1px solid #111827;background:#111827;color:#fff;cursor:pointer}
    .muted{color:#6b7280;font-size:13px}
    a{color:#111827}
  </style>
</head>
<body>
  <h2>Checkout</h2>
  <div class="muted">Flow: checkout_create → checkout_shipping → checkout_payment → pay</div>

  <div class="card">
    <div class="grid">
      <div>
        <label>Card ID</label>
        <input id="card_id" value="<?= $cardId ?>" placeholder="vd: 12">
      </div>
      <div>
        <label>Order ID (nếu đã tạo)</label>
        <input id="order_id" value="<?= $orderId ?>" placeholder="vd: 1001">
      </div>
    </div>
    <div style="margin-top:10px" class="grid">
      <div>
        <label>Số lượng</label>
        <input id="qty" value="1">
      </div>
      <div>
        <label>Giá (VND)</label>
        <input id="amount" value="149000">
      </div>
    </div>
    <div style="margin-top:10px">
      <button id="btn_create">Tạo Order (draft)</button>
    </div>
  </div>

  <div class="card">
    <h3>Thông tin giao hàng</h3>
    <div class="grid">
      <div><input id="full_name" placeholder="Họ tên"></div>
      <div><input id="phone" placeholder="SĐT"></div>
    </div>
    <div class="grid1" style="margin-top:10px">
      <input id="address_line" placeholder="Địa chỉ (số nhà, đường...)">
    </div>
    <div class="grid" style="margin-top:10px">
      <div><input id="province" placeholder="Tỉnh/TP"></div>
      <div><input id="district" placeholder="Quận/Huyện"></div>
    </div>
    <div class="grid" style="margin-top:10px">
      <div><input id="ward" placeholder="Phường/Xã"></div>
      <div><input id="note" placeholder="Ghi chú (tuỳ chọn)"></div>
    </div>
    <div style="margin-top:10px">
      <button id="btn_shipping">Lưu Shipping</button>
    </div>
  </div>

  <div class="card">
    <h3>Phương thức thanh toán</h3>
    <select id="pay_method">
      <option value="banking">Banking</option>
      <option value="cod">COD</option>
    </select>
    <div style="margin-top:10px">
      <button id="btn_payment">Chọn Payment</button>
    </div>
    <div class="muted" style="margin-top:8px" id="next_hint"></div>
  </div>

  <div class="card">
    <h3>Debug</h3>
    <pre id="log" style="white-space:pre-wrap;background:#0b1020;color:#e5e7eb;padding:12px;border-radius:12px;"></pre>
  </div>

<script>
const log = (x)=>{ document.getElementById('log').textContent = typeof x==='string'?x:JSON.stringify(x,null,2); };

async function post(system, body){
  const r = await fetch('/api/card/' + system, {
    method:'POST',
    credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(body || {})
  });
  const j = await r.json().catch(()=>null);
  if(!j) throw new Error('Bad JSON');
  if(!j.ok) throw new Error(j.error || 'ERR');
  return j;
}

document.getElementById('btn_create').addEventListener('click', async ()=>{
  try{
    const card_id = Number(document.getElementById('card_id').value||0);
    const quantity = Number(document.getElementById('qty').value||1);
    const amount = Number(document.getElementById('amount').value||0);
    const j = await post('checkout_create', {card_id, quantity, amount});
    document.getElementById('order_id').value = j.order_id;
    log(j);
  }catch(e){ log({ok:false, error:String(e.message||e)}); }
});

document.getElementById('btn_shipping').addEventListener('click', async ()=>{
  try{
    const order_id = Number(document.getElementById('order_id').value||0);
    const shipping = {
      full_name: document.getElementById('full_name').value,
      phone: document.getElementById('phone').value,
      address_line: document.getElementById('address_line').value,
      province: document.getElementById('province').value,
      district: document.getElementById('district').value,
      ward: document.getElementById('ward').value,
      note: document.getElementById('note').value
    };
    const j = await post('checkout_shipping', {order_id, shipping});
    log(j);
  }catch(e){ log({ok:false, error:String(e.message||e)}); }
});

document.getElementById('btn_payment').addEventListener('click', async ()=>{
  try{
    const order_id = Number(document.getElementById('order_id').value||0);
    const method = document.getElementById('pay_method').value;
    const j = await post('checkout_payment', {order_id, method});
    log(j);
    const hint = document.getElementById('next_hint');
    if(method==='banking') hint.innerHTML = 'Đi tới <a href="/pages/pay_banking.php?order_id='+order_id+'">Trang Banking</a>';
    if(method==='cod') hint.innerHTML = 'Đi tới <a href="/pages/pay_cod.php?order_id='+order_id+'">Trang COD</a>';
  }catch(e){ log({ok:false, error:String(e.message||e)}); }
});
</script>
</body>
</html>
