<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;

if (!KNCMS::checkLogin()) {
  header('Location: ' . KNCMS::baseUrl() . '/auth');
  exit;
}

$base = KNCMS::baseUrl();
$user = KNCMS::getUserInfo();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>KN Card – Danh sách Card</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Danh sách card: tạo, sửa, xóa, mở thiết kế, checkout." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap"
    rel="stylesheet" />

  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />

  <!-- dùng đúng CSS của hệ bạn -->
  <link rel="stylesheet" href="<?= $base ?>/assets/styles/desgin.css" />
</head>

<body>
<main>
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/private/nav.php'; ?>

  <section class="app-wrapper">

    <!-- LEFT: LIST -->
    <div class="panel">
      <div class="panel-header">
        <div>
          <div class="panel-title">Danh sách Card</div>
            <div class="small muted">Quản lý các card của bạn</div>
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button class="btn btn-success" id="btn-new">
            <i class="fa-solid fa-plus"></i> Tạo card mới
          </button>
          <button class="btn" id="btn-reload">
            <i class="fa-solid fa-rotate"></i> Tải lại
          </button>
        </div>
      </div>

      <div class="form-grid">

        <div class="field">
          <div class="field-label">Tìm theo tên</div>
          <input id="q" type="text" class="field-input" placeholder="VD: Card khách hàng A..." />
        </div>

        <div class="field">
          <div class="field-label">Lọc trạng thái</div>
          <div class="pill-options" id="status-filter">
            <button class="pill-option active" data-status="all">Tất cả</button>
            <button class="pill-option" data-status="draft">Draft</button>
            <button class="pill-option" data-status="active">Active</button>
            <button class="pill-option" data-status="archived">Archived</button>
          </div>
        </div>

        <div class="line"></div>

        <div class="field">
          <div class="field-label">Danh sách</div>
          <div id="cards-list" class="layer-list"></div>
          <!-- tận dụng .layer-list để đồng bộ style (list item) -->
        </div>

      </div>
    </div>

    <!-- RIGHT: INFO / QUICK ACTION -->
    <div class="panel">
      <div class="panel-header">
        <div>
          <div class="panel-title">Tài khoản</div>
          <div class="small muted">Quản lý card của bạn</div>
        </div>

        <div class="user-pill">
          <div class="user-avatar" id="user-avatar">U</div>
          <span id="user-email-label" class="small"></span>
          <button class="logout-btn" id="logout-btn" title="Đăng xuất">Thoát</button>
        </div>
      </div>

      <div class="form-grid">
        <div class="field">
          <div class="field-label">Tổng quan</div>
          <div class="small muted" id="kpi"></div>
        </div>

        <div class="field">
          <div class="field-label">Gợi ý luồng</div>
          <div class="field-note" style="margin-top:6px;">
            1) Mở card → Design<br>
            2) Lưu card (API save)<br>
            3) Checkout → Shipping → Payment
          </div>
        </div>

        <div class="line"></div>

        <div class="field">
          <div class="field-label">Đi nhanh</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            <a class="btn btn-primary" href="<?= $base ?>/pages/checkout_step2.php">
              <i class="fa-solid fa-cart-shopping"></i> Checkout (nhập card_id)
            </a>
            <a class="btn" href="<?= $base ?>app">
              <i class="fa-solid fa-pen-ruler"></i> Trang Design
            </a>
          </div>
        </div>
      </div>
    </div>

  </section>
</main>

<link rel="stylesheet" href="<?= $base ?>/assets/styles/knloader.css">
<script src="<?= $base ?>/assets/js/knloader.js"></script>

<script>
  KNLoader.showFor(700, { brand: "KN Card", msg: "Đang tải danh sách…" });

  const TOKEN_KEY = "kn_token";

  const currentUserEmail = <?= json_encode($user['email'] ?? '') ?>;
  const currentUserName  = <?= json_encode($user['name'] ?? ($user['nickname'] ?? '')) ?>;

  const userEmailLabel = document.getElementById("user-email-label");
  const userAvatar = document.getElementById("user-avatar");
  const logoutBtn = document.getElementById("logout-btn");

  userEmailLabel.textContent = currentUserEmail || currentUserName || "";
  userAvatar.textContent = (currentUserEmail || currentUserName || "U").charAt(0).toUpperCase();

  logoutBtn.addEventListener("click", () => {
    localStorage.removeItem(TOKEN_KEY);
    document.cookie = "kn_token=; Max-Age=0; path=/";
    window.location.href = "<?= $base ?>/auth";
  });

  const elList = document.getElementById("cards-list");
  const elQ = document.getElementById("q");
  const elKpi = document.getElementById("kpi");
  const statusFilter = document.getElementById("status-filter");

  const state = {
    cards: [],
    status: "all",
    q: ""
  };

  function esc(s){
    return String(s ?? "").replace(/[&<>"']/g, m => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"
    }[m]));
  }

  async function apiGet(system, params){
    const u = new URL("/api/card/" + system, window.location.origin);
    if(params){
      Object.keys(params).forEach(k => u.searchParams.set(k, String(params[k])));
    }
    const r = await fetch(u.toString(), { credentials:"include" });
    const j = await r.json().catch(()=>null);
    if(!j || !j.ok) throw new Error((j && j.error) ? j.error : "API_ERROR");
    return j;
  }

  async function apiPost(system, body){
    const r = await fetch("/api/card/" + system, {
      method:"POST",
      credentials:"include",
      headers:{ "Content-Type":"application/json" },
      body: JSON.stringify(body || {})
    });
    const j = await r.json().catch(()=>null);
    if(!j || !j.ok) throw new Error((j && j.error) ? j.error : "API_ERROR");
    return j;
  }

  function setKpi(){
    const total = state.cards.length;
    const active = state.cards.filter(x => x.status === "active").length;
    const draft = state.cards.filter(x => x.status === "draft").length;
    const archived = state.cards.filter(x => x.status === "archived").length;

    elKpi.textContent = `Tổng: ${total} • Active: ${active} • Draft: ${draft} • Archived: ${archived}`;
  }

  function render(){
    const q = (state.q || "").toLowerCase().trim();
    const st = state.status;

    const items = state.cards.filter(c => {
      const okQ = !q || String(c.title || "").toLowerCase().includes(q);
      const okS = (st === "all") || (String(c.status) === st);
      return okQ && okS;
    });

    setKpi();

    if(!items.length){
      elList.innerHTML = `
        <div class="layer-item">
          <div class="layer-main-row">
            <div class="layer-thumb"><span>?</span></div>
            <div class="layer-name">Chưa có card nào / bộ lọc không khớp</div>
            <div class="layer-actions">
              <button type="button" id="btn-create-inline" title="Tạo card"><i class="fa-solid fa-plus"></i></button>
            </div>
          </div>
        </div>
      `;
      const btnInline = document.getElementById("btn-create-inline");
      if(btnInline) btnInline.onclick = () => goNew();
      return;
    }

    elList.innerHTML = items.map(c => {
      const id = Number(c.id || 0);
      const title = esc(c.title || ("Card #" + id));
      const status = esc(c.status || "draft");
      const updated = esc(c.updated_at || "");

      const badge = status === "active" ? "Active" : (status === "archived" ? "Archived" : "Draft");

      return `
        <div class="layer-item" data-id="${id}">
          <div class="layer-main-row">
            <div class="layer-thumb" style="background-image:url('${esc(c.thumb_url || "")}')">
              ${c.thumb_url ? "" : "<span>KN</span>"}
            </div>
            <div class="layer-name">
              ${title}
              <div class="field-note" style="margin-top:4px;">
                #${id} • ${badge} • ${updated || "-"}
              </div>
            </div>
            <div class="layer-actions">
              <button type="button" data-act="design" title="Mở Design"><i class="fa-solid fa-pen-ruler"></i></button>
              <button type="button" data-act="checkout" title="Checkout"><i class="fa-solid fa-cart-shopping"></i></button>
              <button type="button" data-act="delete" title="Xóa"><i class="fa-regular fa-trash-can"></i></button>
            </div>
          </div>
        </div>
      `;
    }).join("");
  }

  async function loadList(){
    elList.innerHTML = `
      <div class="layer-item">
        <div class="layer-main-row">
          <div class="layer-thumb"><span>…</span></div>
          <div class="layer-name">Đang tải dữ liệu…</div>
        </div>
      </div>
    `;

    try{
      const j = await apiGet("list");
      state.cards = Array.isArray(j.cards) ? j.cards : [];
      render();
    }catch(e){
      elList.innerHTML = `
        <div class="layer-item">
          <div class="layer-main-row">
            <div class="layer-thumb"><span>!</span></div>
            <div class="layer-name">Lỗi tải list: ${esc(e.message || e)}</div>
          </div>
        </div>
      `;
    }
  }

  function goNew(){
    window.location.href = "<?= $base ?>app";
  }

  function goDesign(id){
    window.location.href = "<?= $base ?>app?card_id=" + encodeURIComponent(String(id));
  }

  function goCheckout(id){
    window.location.href = "<?= $base ?>/pages/checkout_step2.php?card_id=" + encodeURIComponent(String(id));
  }

  // events
  elQ.addEventListener("input", () => {
    state.q = elQ.value || "";
    render();
  });

  statusFilter.addEventListener("click", (e) => {
    const btn = e.target.closest(".pill-option");
    if(!btn) return;
    statusFilter.querySelectorAll(".pill-option").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    state.status = btn.dataset.status || "all";
    render();
  });

  document.getElementById("btn-reload").addEventListener("click", loadList);
  document.getElementById("btn-new").addEventListener("click", goNew);

  elList.addEventListener("click", async (e) => {
    const actBtn = e.target.closest("button[data-act]");
    if(!actBtn) return;

    const item = e.target.closest(".layer-item");
    if(!item) return;

    const id = Number(item.dataset.id || 0);
    const act = actBtn.dataset.act;

    if(act === "design") return goDesign(id);
    if(act === "checkout") return goCheckout(id);

    if(act === "delete"){
      const ok = confirm("Xóa card #" + id + " ? Không thể hoàn tác.");
      if(!ok) return;

      actBtn.disabled = true;
      try{
        await apiPost("delete", { id });
        state.cards = state.cards.filter(x => Number(x.id) !== id);
        render();
      }catch(err){
        alert("Xóa thất bại: " + String(err.message || err));
      }finally{
        actBtn.disabled = false;
      }
    }
  });

  // init
  loadList();
</script>

</body>
</html>
