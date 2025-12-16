(function (global) {
  const DEFAULTS = {
    brand: "KN BioCard",
    sub: "Đang xử lý, vui lòng đợi…",
    tag: "Secure • NFC Ready",
    showProgress: true,
    lockScroll: true,
    // Nếu bro có logo SVG riêng thì truyền vào options.logoSvg
    logoSvg: null
  };

  const STATE = {
    el: null,
    openCount: 0
  };

  function htmlLogo(svg) {
    if (svg) return svg;
    // Logo mặc định (KN)
    return `
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M6 18V6h2.2l6.2 8.2V6H18v12h-2.2L9.6 9.8V18H6Z" fill="white" opacity=".92"/>
        <path d="M6 18V6h2.2l6.2 8.2V6H18" stroke="rgba(247,201,72,.9)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `;
  }

  function ensure() {
    if (STATE.el) return STATE.el;

    const wrap = document.createElement("div");
    wrap.className = "knl-overlay";
    wrap.innerHTML = `
      <div class="knl-card" role="dialog" aria-modal="true" aria-label="Loading">
        <div class="knl-top"></div>
        <div class="knl-body">
          <div class="knl-brand">
            <div class="knl-logo" data-knl-logo></div>
            <div>
              <div class="knl-title" data-knl-brand></div>
              <div class="knl-sub" data-knl-sub></div>
            </div>
          </div>

          <div class="knl-row">
            <div class="knl-spinner" aria-hidden="true"></div>
            <div class="knl-sub" data-knl-msg></div>
          </div>

          <div class="knl-progress" data-knl-progress style="display:none;">
            <div class="knl-bar"></div>
          </div>
        </div>

        <div class="knl-foot">
          <div>© <span data-knl-year></span> KN</div>
          <div class="knl-tag" data-knl-tag></div>
        </div>
      </div>
    `;

    document.body.appendChild(wrap);
    STATE.el = wrap;

    wrap.addEventListener("click", (e) => {
      // click overlay không tắt (độc quyền/brand lock)
      e.preventDefault();
    });

    return wrap;
  }

  function setText(el, opts) {
    el.querySelector("[data-knl-brand]").textContent = opts.brand || DEFAULTS.brand;
    el.querySelector("[data-knl-sub]").textContent = opts.sub || DEFAULTS.sub;
    el.querySelector("[data-knl-msg]").textContent = opts.msg || opts.sub || DEFAULTS.sub;
    el.querySelector("[data-knl-tag]").textContent = opts.tag || DEFAULTS.tag;
    el.querySelector("[data-knl-year]").textContent = String(new Date().getFullYear());

    const logo = el.querySelector("[data-knl-logo]");
    logo.innerHTML = htmlLogo(opts.logoSvg || DEFAULTS.logoSvg);

    const prog = el.querySelector("[data-knl-progress]");
    prog.style.display = (opts.showProgress ?? DEFAULTS.showProgress) ? "" : "none";
  }

  function lockScroll(on) {
    const html = document.documentElement;
    if (on) {
      html.dataset.knlLock = "1";
      html.style.overflow = "hidden";
    } else {
      if (html.dataset.knlLock === "1") {
        delete html.dataset.knlLock;
        html.style.overflow = "";
      }
    }
  }

  function show(options = {}) {
    const opts = { ...DEFAULTS, ...options };
    const el = ensure();

    STATE.openCount += 1;
    setText(el, opts);

    if (opts.lockScroll) lockScroll(true);

    // show
    requestAnimationFrame(() => el.classList.add("is-show"));
  }
// show trong 1 khoảng thời gian rồi auto hide
function showFor(ms = 1200, options = {}) {
  show(options);
  setTimeout(() => {
    hide(true);
  }, Math.max(0, ms));
}

  function hide(force = false) {
    if (!STATE.el) return;

    if (!force) {
      STATE.openCount = Math.max(STATE.openCount - 1, 0);
      if (STATE.openCount > 0) return;
    } else {
      STATE.openCount = 0;
    }

    STATE.el.classList.remove("is-show");
    lockScroll(false);
  }

  async function wrap(promiseOrFn, options = {}) {
    try {
      show(options);
      const p = (typeof promiseOrFn === "function") ? promiseOrFn() : promiseOrFn;
      return await p;
    } finally {
      hide(true);
    }
  }

  global.KNLoader = { show, hide, wrap , showFor};
})(window);
