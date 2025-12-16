(() => {
  const DEFAULTS = {
    position: "top-right",
    duration: 3200,
    max: 4,
    pauseOnHover: true,
    closeOnClick: false,
    showProgress: true,
  };

  function ensureHost() {
    let host = document.querySelector(".knnotify-host");
    if (!host) {
      host = document.createElement("div");
      host.className = "knnotify-host";
      document.body.appendChild(host);
    }
    return host;
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function iconText(variant) {
    switch (variant) {
      case "success": return "✓";
      case "error": return "!";
      case "warning": return "!";
      default: return "i";
    }
  }

  function safeText(s) {
    // avoid XSS via innerHTML
    return (s ?? "").toString();
  }

  function removeToast(el) {
    if (!el || el.__closing) return;
    el.__closing = true;
    el.classList.add("is-out");
    setTimeout(() => el.remove(), 220);
  }

  function startProgress(bar, ms) {
    if (!bar) return;
    bar.classList.add("is-anim");
    bar.style.transitionDuration = `${ms}ms`;
    // next tick to animate
    requestAnimationFrame(() => (bar.style.transform = "scaleX(0)"));
  }

  function stopProgress(bar) {
    if (!bar) return;
    const computed = getComputedStyle(bar);
    const matrix = computed.transform;
    // keep current scaleX
    bar.style.transitionDuration = "0ms";
    bar.style.transform = matrix === "none" ? "scaleX(1)" : matrix;
    bar.classList.remove("is-anim");
  }

  function resumeProgress(bar, remainingMs) {
    if (!bar) return;
    // get current scaleX
    const computed = getComputedStyle(bar);
    const matrix = computed.transform;
    let scaleX = 1;
    if (matrix && matrix !== "none") {
      const parts = matrix.match(/matrix\(([^)]+)\)/);
      if (parts && parts[1]) {
        const vals = parts[1].split(",").map(v => parseFloat(v.trim()));
        if (vals.length >= 1 && !Number.isNaN(vals[0])) scaleX = vals[0];
      }
    }
    bar.style.transitionDuration = "0ms";
    bar.style.transform = `scaleX(${scaleX})`;
    bar.classList.remove("is-anim");
    requestAnimationFrame(() => {
      bar.classList.add("is-anim");
      bar.style.transitionDuration = `${remainingMs}ms`;
      bar.style.transform = "scaleX(0)";
    });
  }

  function createToast(opts) {
    const host = ensureHost();
    const cfg = Object.assign({}, DEFAULTS, opts || {});
    const variant = cfg.variant || "info";
    const duration = clamp(Number(cfg.duration || DEFAULTS.duration), 800, 30000);

    // enforce max
    while (host.children.length >= cfg.max) {
      host.removeChild(host.lastElementChild);
    }

    const toast = document.createElement("div");
    toast.className = "knnotify";
    toast.dataset.variant = variant;

    const title = safeText(cfg.title || (variant === "success" ? "Thành công" :
                                        variant === "error" ? "Lỗi" :
                                        variant === "warning" ? "Cảnh báo" : "Thông báo"));
    const message = safeText(cfg.message || "");

    toast.innerHTML = `
    <link rel="stylesheet" href="/assets/styles/kncms.css">
      <div class="knnotify-top">
        <div class="knnotify-icon" aria-hidden="true">${iconText(variant)}</div>
        <div class="knnotify-body">
          <div class="knnotify-title"></div>
          <div class="knnotify-msg"></div>
        </div>
        <div class="knnotify-actions">
          <button class="knnotify-btn" type="button" aria-label="Đóng">✕</button>
        </div>
      </div>
      <div class="knnotify-bar"></div>
    `;

    // set text safely
    toast.querySelector(".knnotify-title").textContent = title;
    toast.querySelector(".knnotify-msg").textContent = message;

    const btnClose = toast.querySelector(".knnotify-btn");
    const bar = toast.querySelector(".knnotify-bar");

    let timer = null;
    let startedAt = performance.now();
    let remaining = duration;

    function start() {
      if (cfg.showProgress) startProgress(bar, remaining);
      timer = window.setTimeout(() => removeToast(toast), remaining);
    }

    function pause() {
      if (!timer) return;
      window.clearTimeout(timer);
      timer = null;
      const now = performance.now();
      const elapsed = now - startedAt;
      remaining = Math.max(0, remaining - elapsed);
      if (cfg.showProgress) stopProgress(bar);
    }

    function resume() {
      if (timer) return;
      startedAt = performance.now();
      if (cfg.showProgress) resumeProgress(bar, remaining);
      timer = window.setTimeout(() => removeToast(toast), remaining);
    }

    btnClose.addEventListener("click", (e) => {
      e.preventDefault();
      removeToast(toast);
    });

    if (cfg.closeOnClick) {
      toast.addEventListener("click", () => removeToast(toast));
    }

    if (cfg.pauseOnHover) {
      toast.addEventListener("mouseenter", pause);
      toast.addEventListener("mouseleave", resume);
      toast.addEventListener("focusin", pause);
      toast.addEventListener("focusout", resume);
    }

    host.prepend(toast);

    // animate in
    requestAnimationFrame(() => toast.classList.add("is-in"));

    // start timer
    startedAt = performance.now();
    start();

    // public controls
    return {
      close: () => removeToast(toast),
      el: toast,
    };
  }

  // Expose global API
  window.KNNotify = {
    toast: (opts) => createToast(opts),
    success: (message, title = "Thành công", opts = {}) =>
      createToast({ ...opts, variant: "success", title, message }),
    info: (message, title = "Thông báo", opts = {}) =>
      createToast({ ...opts, variant: "info", title, message }),
    warning: (message, title = "Cảnh báo", opts = {}) =>
      createToast({ ...opts, variant: "warning", title, message }),
    error: (message, title = "Lỗi", opts = {}) =>
      createToast({ ...opts, variant: "error", title, message }),
  };
})();
function notifySuccess(msg) {
  if (window.KNNotify) {
    KNNotify.success(msg);
  } else {
    alert(msg);
  }
}

function notifyError(msg) {
  if (window.KNNotify) {
    KNNotify.error(msg);
  } else {
    alert(msg);
  }
}
