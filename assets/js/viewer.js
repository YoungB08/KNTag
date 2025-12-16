const TOKEN_KEY = "kn_token";
    function esc(s) {
      return String(s ?? "").replace(/[&<>"']/g, (m) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
      } [m]));
    }

    function getUsername() {
      const sp = new URLSearchParams(location.search);
      const q = sp.get("u");
      if (q) return q.trim();

      const p = location.pathname || "/";
      const m = p.match(/^\/@([^\/]+)$/);
      if (m && m[1]) return decodeURIComponent(m[1]).trim();

      return "";
    }

    function normalize(json) {
      const x = json?.data ?? json ?? {};

      // bg_style/font_style từ DB
      const bg_style = (x.bg_style || "default").toString().toLowerCase();
      const font_style = (x.font_style || "inter").toString().toLowerCase();

      // bg_custom: có thể là URL ảnh hoặc JSON string (color/gradient)
      const rawBgCustom = (x.bg_custom ?? x.coverUrl ?? "").toString().trim();

      let bg_cfg = null; // {type,color,a,b,angle} nếu parse được
      let coverUrl = ""; // chỉ dùng khi bg_custom là ảnh/url

      if (rawBgCustom) {
        // thử parse JSON
        try {
          const obj = JSON.parse(rawBgCustom);
          if (obj && (obj.type === "color" || obj.type === "gradient")) {
            bg_cfg = obj;
          } else {
            coverUrl = rawBgCustom;
          }
        } catch {
          // không phải JSON -> coi như URL ảnh
          coverUrl = rawBgCustom;
        }
      }

      return {
        name: x.name || "Guest User",
        nickname: x.nickname || "",
        role: x.role || "",
        jobs: Array.isArray(x.jobs) ? x.jobs : [],
        intro: x.intro || "",
        avatarUrl: x.avatarUrl || x.avatar || x.avt || "",
        links: Array.isArray(x.links) ? x.links : [],
        ctaMain: x.ctaMain || null,
        ctaContact: x.ctaContact || null,

        bg_style,
        font_style,

        // NEW
        bg_cfg, // color/gradient config (nếu có)
        coverUrl // chỉ khi là ảnh
      };
    }


    // DOM
    const avatarEl = document.getElementById("bio-avatar");
    const nameEl = document.getElementById("bio-name");
    const nickEl = document.getElementById("bio-nick");
    const roleEl = document.getElementById("bio-role");
    const jobsEl = document.getElementById("bio-jobs");
    const introEl = document.getElementById("bio-intro");
    const linksWrap = document.getElementById("links-wrap");
    const ctaMainEl = document.getElementById("cta-main");
    const ctaContactEl = document.getElementById("cta-contact");

    function applyTheme(p) {
      document.body.dataset.bg = p.bg_style || "default";
      document.body.dataset.font = p.font_style || "inter";

      const card = document.querySelector(".bio-shell");
      if (!card) return;

      // reset
      card.classList.remove("has-cover");
      card.style.removeProperty("--cover-url");
      card.style.removeProperty("--bg-solid");
      card.style.removeProperty("--bg-gradient");

      // Ưu tiên bg_cfg (color/gradient) hơn cover ảnh
      if (p.bg_cfg && (p.bg_cfg.type === "color" || p.bg_cfg.type === "gradient")) {
        if (p.bg_cfg.type === "color") {
          const c = String(p.bg_cfg.color || "").trim() || "#1b1f2a";
          card.style.setProperty("--bg-solid", c);
          card.dataset.bgMode = "solid";
        } else {
          const a = String(p.bg_cfg.a || "").trim() || "#00c6ff";
          const b = String(p.bg_cfg.b || "").trim() || "#0072ff";
          const ang = Number.isFinite(p.bg_cfg.angle) ? p.bg_cfg.angle : parseInt(String(p.bg_cfg.angle || "135"), 10) || 135;
          card.style.setProperty("--bg-gradient", `linear-gradient(${ang}deg, ${a}, ${b})`);
          card.dataset.bgMode = "gradient";
        }
        return;
      }

      // Nếu không có bg_cfg -> dùng cover ảnh như cũ
      if (p.coverUrl) {
        const safe = String(p.coverUrl).replace(/"/g, "%22").replace(/'/g, "%27");
        card.classList.add("has-cover");
        card.style.setProperty("--cover-url", `url("${safe}")`);
        card.dataset.bgMode = "cover";
        return;
      }

      // fallback theo bg_style (default/dark/light)
      card.dataset.bgMode = (p.bg_style || "default");
    }

    function renderAvatar(url, alt) {
      avatarEl.innerHTML = "";
      if (url) {
        const img = document.createElement("img");
        img.src = url;
        img.alt = alt || "Avatar";
        img.loading = "lazy";
        avatarEl.appendChild(img);
      } else {
        const icon = document.createElement("i");
        icon.className = "fa-regular fa-user";
        avatarEl.appendChild(icon);
      }
    }

    function renderBase(p) {
      nameEl.textContent = p.name || "Guest User";
      nickEl.textContent = p.nickname || "";
      roleEl.textContent = p.role || "";
      roleEl.style.display = p.role ? "" : "none";

      jobsEl.innerHTML = "";
      (p.jobs || []).forEach((job) => {
        const span = document.createElement("span");
        span.className = "job-tag";
        span.textContent = job;
        jobsEl.appendChild(span);
      });
      jobsEl.style.display = (p.jobs && p.jobs.length) ? "" : "none";

      introEl.textContent = p.intro || "Thẻ Bio được tạo bằng KN BioCard – NFC ready.";

      if (p.ctaMain && p.ctaMain.url) {
        ctaMainEl.href = p.ctaMain.url;
        ctaMainEl.querySelector("span").textContent = p.ctaMain.label || "Xem thêm";
        ctaMainEl.style.display = "";
      } else ctaMainEl.style.display = "none";

      if (p.ctaContact && p.ctaContact.url) {
        ctaContactEl.href = p.ctaContact.url;
        ctaContactEl.querySelector("span").textContent = p.ctaContact.label || "Liên hệ";
        ctaContactEl.style.display = "";
      } else ctaContactEl.style.display = "none";
    }

    function renderLinks(list) {
      linksWrap.innerHTML = "";
      (list || []).forEach((item) => {
        const hasUrl = !!item.url;
        const el = document.createElement(hasUrl ? "a" : "div");
        if (hasUrl) {
          el.href = item.url;
          el.target = "_blank";
          el.rel = "noopener";
        }
        el.className = "link-item" + (hasUrl ? "" : " disabled");

        el.innerHTML = `
        <div class="link-item-icon"><i class="${esc(item.iconClass || "fa-solid fa-link")}"></i></div>
        <div class="link-item-text">
          <div class="link-item-label">${esc(item.label || "Link")}</div>
          <div class="link-item-sub">${esc(item.sub || (hasUrl ? item.url : "Chưa cấu hình link"))}</div>
        </div>
        <div class="link-item-arrow">${hasUrl ? '<i class="fa-solid fa-arrow-up-right-from-square"></i>' : ""}</div>
      `;
        linksWrap.appendChild(el);
      });
    }

    async function load() {
      const u = getUsername();
      if (!u) {
        const p = {
          name: "Guest User",
          nickname: "",
          role: "",
          jobs: [],
          intro: "Thiếu username",
          avatarUrl: "",
          links: [],
          bg_style: "soft",
          font_style: "inter",
          coverUrl: ""
        };
        applyTheme(p);
        renderAvatar("", "Guest");
        renderBase(p);
        renderLinks([]);
        return;
      }

      const api = `/api/bio/view?u=${encodeURIComponent(u)}`;

      try {
        const res = await fetch(api, {
          headers: {
            "Accept": "application/json"
          },
          cache: "no-store"
        });
        const json = await res.json().catch(() => null);

        if (!res.ok || !json || json.ok === false) {
          const p = {
            name: "Bio không tồn tại",
            nickname: "@" + u,
            role: "",
            jobs: [],
            intro: (json?.message || json?.error || ("HTTP " + res.status)),
            avatarUrl: "",
            links: [],
            bg_style: "soft",
            font_style: "inter",
            coverUrl: ""
          };
          applyTheme(p);
          renderAvatar("", "Not found");
          renderBase(p);
          renderLinks([]);
          return;
        }

        const p = normalize(json);
        applyTheme(p);
        renderAvatar(p.avatarUrl, p.name);
        renderBase(p);
        renderLinks(p.links);

      } catch (e) {
        const p = {
          name: "Lỗi tải dữ liệu",
          nickname: "@" + u,
          role: "",
          jobs: [],
          intro: (e?.message || "Fetch failed"),
          avatarUrl: "",
          links: [],
          bg_style: "soft",
          font_style: "inter",
          coverUrl: ""
        };
        applyTheme(p);
        renderAvatar("", "Error");
        renderBase(p);
        renderLinks([]);
      }
    }

    load();
  