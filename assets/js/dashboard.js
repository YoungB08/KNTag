(() => {
  const root = document.getElementById("dash-root");
  const isGuest = root?.dataset?.isGuest === "1";
  const nickname = root?.dataset?.nickname || "";
  const base = root?.dataset?.base || "";

  const $ = (q) => document.querySelector(q);

  // dropdown avatar
  const wrap = $("#nav-avatar-wrap");
  const avatar = $("#nav-avatar");
  if (avatar && wrap) {
    avatar.addEventListener("click", (e) => { e.stopPropagation(); wrap.classList.toggle("open"); });
    document.addEventListener("click", () => wrap.classList.remove("open"));
  }

  // nav btn go
  document.querySelectorAll("[data-go]").forEach((el) => {
    el.addEventListener("click", () => window.location.href = el.getAttribute("data-go"));
  });

  // login / logout hooks (bro map đúng route login/logout của bro)
  document.querySelectorAll("[data-login]").forEach((el) => {
    el.addEventListener("click", () => { window.location.href = "/login"; });
  });
  const logoutBtn = document.querySelector("[data-logout]");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
      // ✅ đổi endpoint logout nếu bro có
      await fetch(base + "/api/auth/logout", { method: "POST", credentials: "include" }).catch(()=>{});
      window.location.reload();
    });
  }

  // auth modal
  const modal = $("#auth-modal");
  const modalClose = $("#auth-modal-close");
  const modalLoginBtn = modal?.querySelector('[data-login]');
  
  function openAuthModal() {
    if (!modal) return;
    modal.classList.add("open");
    modal.classList.add("visible");
    modal.setAttribute("aria-hidden", "false");
  }
  function closeAuthModal() {
    if (!modal) return;
    modal.classList.remove("open");
    modal.classList.remove("visible");
    modal.setAttribute("aria-hidden", "true");
  }
  
  if (modalClose) {
    modalClose.addEventListener("click", closeAuthModal);
  }
  
  if (modal) {
    modal.addEventListener("click", (e) => { 
      if (e.target === modal) closeAuthModal(); 
    });
    
    // Close on Escape
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal.classList.contains("open")) {
        closeAuthModal();
      }
    });
  }
  
  // Modal login button
  if (modalLoginBtn) {
    modalLoginBtn.addEventListener("click", () => {
      window.location.href = "/auth.html";
    });
  }

  // lock actions in guest
  document.querySelectorAll("[data-auth-action]").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (isGuest) return openAuthModal();
      const act = btn.getAttribute("data-auth-action");
      if (act === "edit-bio") window.location.href = "/bio/layout";
      if (act === "share-bio") alert("TODO: share dialog");
      if (act === "order-nfc") window.location.href = "/nfc";
      if (act === "settings") window.location.href = "/settings";
    });
  });

  // timestamp
  const updatedAt = $("#updated-at");
  const apiPill = $("#api-pill");
  const btnRefresh = $("#btn-refresh");

  function stamp() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    if (updatedAt) updatedAt.textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }
  stamp();
  if (btnRefresh) btnRefresh.addEventListener("click", () => { stamp(); if (!isGuest) loadAll(); });

  // public bio url
  const publicBioUrl = $("#public-bio-url");
  if (publicBioUrl) {
    publicBioUrl.textContent = nickname ? `${location.origin}/bio?u=${nickname}` : "—";
  }

  async function jget(url) {
    const res = await fetch(url, { credentials: "include" });
    let json = null;
    try { json = await res.json(); } catch {}
    return { res, json };
  }

  // ===== LOADERS (only when logged in) =====
  async function loadStats14d() {
    const overlay = $("#chart-overlay");
    const canvas = $("#chart-canvas");
    const box = $("#chart-box");
    if (!overlay || !canvas || !box) return;

    overlay.style.display = "flex";
    canvas.style.display = "none";

    const { res, json } = await jget(base + `/api/bio/stats/views?u=${encodeURIComponent(nickname)}`);
    if (!res.ok || !json?.ok) {
      overlay.querySelector(".dash-chart-small").textContent = json?.message || "Không tải được stats";
      return;
    }

    const daily = json.data?.daily || [];
    // Vẽ chart canvas đơn giản (views + unique) không cần lib
    const ctx = canvas.getContext("2d");
    const W = box.clientWidth;
    const H = 240;
    canvas.width = W * devicePixelRatio;
    canvas.height = H * devicePixelRatio;
    canvas.style.width = W + "px";
    canvas.style.height = H + "px";
    ctx.scale(devicePixelRatio, devicePixelRatio);

    const views = daily.map(d => d.views || 0);
    const uniq = daily.map(d => d.unique || 0);
    const maxV = Math.max(1, ...views, ...uniq);

    function line(series, alpha) {
      ctx.beginPath();
      series.forEach((v, i) => {
        const x = (i / Math.max(1, series.length - 1)) * (W - 24) + 12;
        const y = H - 18 - (v / maxV) * (H - 36);
        if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
      });
      ctx.lineWidth = 2;
      ctx.strokeStyle = `rgba(15,23,42,${alpha})`;
      ctx.stroke();
    }

    ctx.clearRect(0, 0, W, H);
    // grid
    ctx.strokeStyle = "rgba(15,23,42,.08)";
    ctx.lineWidth = 1;
    for (let i=0;i<5;i++){
      const y = 18 + i*((H-36)/4);
      ctx.beginPath(); ctx.moveTo(12,y); ctx.lineTo(W-12,y); ctx.stroke();
    }

    line(views, 0.85);
    line(uniq, 0.45);

    overlay.style.display = "none";
    canvas.style.display = "block";

    // KPI 7 ngày (views) = sum last 7
    const last7 = daily.slice(-7);
    const sum7 = last7.reduce((a, x) => a + (x.views||0), 0);
    const total14 = daily.reduce((a, x) => a + (x.views||0), 0);

    const kpiViews = $("#kpi-views");
    const kpiViewsSub = $("#kpi-views-sub");
    if (kpiViews) kpiViews.textContent = sum7.toLocaleString("en-US");
    if (kpiViewsSub) kpiViewsSub.textContent = `Tổng 14 ngày: ${total14.toLocaleString("en-US")}`;

    // CTR demo: nếu bro có API clicks thì thay vào đây
    const miniCtr = $("#mini-ctr");
    if (miniCtr) miniCtr.textContent = "—";
  }

  async function loadLinks() {
    const tbody = $("#links-tbody");
    const note = $("#links-foot-note");
    if (!tbody) return;

    const { res, json } = await jget(base + `/api/dashboard/links`);
    if (!res.ok || !json?.ok) {
      tbody.innerHTML = `<tr><td colspan="4" class="dash-muted" style="padding:14px;">${json?.message || "Không tải được links"}</td></tr>`;
      return;
    }

    const links = json.data?.links || [];
    if (!links.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="dash-muted" style="padding:14px;">Chưa có link nào.</td></tr>`;
      if (note) note.textContent = "";
      return;
    }

    tbody.innerHTML = links.slice(0, 8).map((l) => {
      const label = (l.label || "Link").replace(/</g,"&lt;");
      const url = (l.url || "").replace(/</g,"&lt;");
      const ic = (l.iconClass || "fa-solid fa-link").replace(/</g,"&lt;");
      return `
        <tr>
          <td><strong>${label}</strong></td>
          <td>${url}</td>
          <td><i class="${ic}"></i></td>
          <td class="dash-td-right"><button class="dash-linkbtn" type="button" data-edit-link="${l.id}">Sửa</button></td>
        </tr>
      `;
    }).join("");

    if (note) note.textContent = `Đang hiển thị ${Math.min(8, links.length)}/${links.length} links.`;

    tbody.querySelectorAll("[data-edit-link]").forEach(btn => {
      btn.addEventListener("click", () => alert("TODO: mở popup sửa link (edit bio_blocks icon)"));
    });
  }

  async function loadActivity() {
    const box = $("#activity-list");
    if (!box) return;

    const { res, json } = await jget(base + `/api/dashboard/activity`);
    if (!res.ok || !json?.ok) {
      box.innerHTML = `<div class="dash-muted" style="padding:10px;">${json?.message || "Không tải được activity"}</div>`;
      return;
    }

    const items = json.data?.items || [];
    if (!items.length) {
      box.innerHTML = `<div class="dash-muted" style="padding:10px;">Chưa có hoạt động.</div>`;
      return;
    }

    box.innerHTML = items.slice(0, 6).map(it => `
      <div class="dash-tl-item">
        <div class="dash-tl-dot ${it.level || ""}"></div>
        <div class="dash-tl-body">
          <div class="dash-tl-title">${(it.title||"").replace(/</g,"&lt;")}</div>
          <div class="dash-tl-sub">${(it.sub||"").replace(/</g,"&lt;")}</div>
          <div class="dash-tl-time">${(it.time||"").replace(/</g,"&lt;")}</div>
        </div>
      </div>
    `).join("");
  }

  async function loadKpis() {
    const { res, json } = await jget(base + `/api/dashboard/kpis`);
    if (apiPill) apiPill.textContent = res.ok ? "OK" : "ERR";
    if (!res.ok || !json?.ok) return;

    const d = json.data || {};
    const kpiActive = $("#kpi-active");
    const kpiClicks = $("#kpi-clicks");
    const kpiNfc = $("#kpi-nfc");

    if (kpiActive) kpiActive.textContent = (d.activeBio ?? 0).toLocaleString("en-US");
    if (kpiClicks) kpiClicks.textContent = (d.clicks7d ?? 0).toLocaleString("en-US");
    if (kpiNfc) kpiNfc.textContent = (d.nfcCount ?? 0).toLocaleString("en-US");
  }

  async function loadAll() {
    await loadKpis();
    await loadStats14d();
    await loadLinks();
    await loadActivity();
  }

  // export
  const btnExport = $("#btn-export");
  if (btnExport) {
    btnExport.addEventListener("click", async () => {
      if (isGuest) return openAuthModal();
      window.location.href = base + "/api/dashboard/export";
    });
  }

  // buttons that require auth
  $("#btn-add-link")?.addEventListener("click", () => isGuest ? openAuthModal() : window.location.href = "/bio/layout");
  $("#btn-stats-detail")?.addEventListener("click", () => isGuest ? openAuthModal() : alert("TODO: trang thống kê chi tiết"));
  $("#btn-links-all")?.addEventListener("click", () => isGuest ? openAuthModal() : alert("TODO: trang links chi tiết"));
  $("#btn-activity-log")?.addEventListener("click", () => isGuest ? openAuthModal() : alert("TODO: trang log"));

  if (isGuest) {
    if (apiPill) apiPill.textContent = "PUBLIC";
    stamp();
    return;
  }

  // Logged in => load all
  loadAll();
})();
window.openAuthModal = openAuthModal;
window.closeAuthModal = closeAuthModal;