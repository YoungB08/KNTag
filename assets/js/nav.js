;(function (w) {
  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;")
  }

  function firstLetter(nameOrEmail) {
    const s = String(nameOrEmail || "U").trim()
    if (!s) return "U"
    return s[0].toUpperCase()
  }

  function renderTemplate(opts) {
    const brandTitle = esc(opts.brandTitle || "KN BioCard")
    const brandSub = esc(opts.brandSub || "Thẻ Bio cá nhân – NFC")
    const logoText = esc(opts.logoText || "KN")
    const searchPlaceholder = esc(opts.searchPlaceholder || "Tìm kiếm...")

    const user = opts.user || { isGuest: true, email: "Guest" }
    const emailText = user.isGuest ? "Guest" : (user.email || "User")
    const avatarText = user.avatarLetter || firstLetter(emailText)

    const items = Array.isArray(opts.items) && opts.items.length
      ? opts.items
      : [
          { key: "auth",   label: "Trang chủ", icon: "fa-solid fa-house" },
          { key: "design", label: "Tạo thẻ",   icon: "fa-solid fa-id-card-clip" },
          { key: "history",label: "Lịch sử",   icon: "fa-regular fa-clock" },
          { key: "support",label: "Hỗ trợ",    icon: "fa-regular fa-life-ring" },
        ]

    const activeKey = String(opts.activeKey || "")

    const centerBtns = items.map(it => {
      const key = esc(it.key)
      const label = esc(it.label)
      const icon = esc(it.icon || "fa-solid fa-circle")
      const isActive = key === esc(activeKey) ? " active" : ""
      const disabled = it.disabled ? " disabled" : ""
      return `
        <button class="kn-nav-btn${isActive}${disabled}" data-go="${key}" ${it.disabled ? "disabled" : ""}>
          <i class="${icon}"></i> ${label}
        </button>
      `
    }).join("")

    return `
      <div class="kn-nav-wrap">
        <nav class="kn-nav">
          <div class="kn-nav-left">
            <div class="kn-nav-logo">${logoText}</div>
            <div class="kn-nav-brand">
              <span class="kn-nav-brand-title">${brandTitle}</span>
              <span class="kn-nav-brand-sub">${brandSub}</span>
            </div>
          </div>

          <div class="kn-nav-center">
            ${centerBtns}
          </div>

          <div class="kn-nav-right">
            <div class="kn-nav-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input id="kn-nav-search-input" placeholder="${searchPlaceholder}" />
            </div>

            <div class="kn-avatar-wrap" id="kn-avatar-wrap">
              <div class="kn-avatar" id="kn-avatar">${esc(avatarText)}</div>
              <div class="kn-dropdown" id="kn-dropdown">
                <div class="kn-dropdown-header" id="kn-dropdown-email">
                  Đang đăng nhập: ${esc(emailText)}
                </div>

                <div class="kn-dropdown-item" id="kn-dropdown-profile" ${user.isGuest ? 'data-requires-auth="1"' : ""}>
                  <i class="fa-regular fa-user"></i>
                  Hồ sơ của tôi
                </div>

                <div class="kn-dropdown-item" id="kn-dropdown-logout">
                  <i class="fa-solid fa-arrow-right-from-bracket"></i>
                  ${user.isGuest ? "Đăng nhập" : "Đăng xuất"}
                </div>
              </div>
            </div>
          </div>
        </nav>
      </div>
    `
  }

  function attachEvents(root, opts) {
    const onGo = typeof opts.onGo === "function" ? opts.onGo : null
    const onLogout = typeof opts.onLogout === "function" ? opts.onLogout : null
    const onProfile = typeof opts.onProfile === "function" ? opts.onProfile : null
    const onSearch = typeof opts.onSearch === "function" ? opts.onSearch : null

    const wrap = root.querySelector("#kn-avatar-wrap")
    const avatar = root.querySelector("#kn-avatar")
    const dropdown = root.querySelector("#kn-dropdown")

    function closeDropdown() {
      if (wrap) wrap.classList.remove("open")
    }
    function toggleDropdown() {
      if (wrap) wrap.classList.toggle("open")
    }

    if (avatar) {
      avatar.addEventListener("click", (e) => {
        e.stopPropagation()
        toggleDropdown()
      })
    }

    document.addEventListener("click", closeDropdown)

    root.querySelectorAll("[data-go]").forEach((btn) => {
      btn.addEventListener("click", () => {
        if (btn.disabled) return
        const key = btn.getAttribute("data-go") || ""
        if (onGo) onGo(key)
      })
    })

    const profileBtn = root.querySelector("#kn-dropdown-profile")
    if (profileBtn) {
      profileBtn.addEventListener("click", () => {
        closeDropdown()
        if (onProfile) onProfile()
      })
    }

    const logoutBtn = root.querySelector("#kn-dropdown-logout")
    if (logoutBtn) {
      logoutBtn.addEventListener("click", () => {
        closeDropdown()
        if (onLogout) onLogout()
      })
    }

    const searchInput = root.querySelector("#kn-nav-search-input")
    if (searchInput && onSearch) {
      let t = null
      searchInput.addEventListener("input", () => {
        if (t) clearTimeout(t)
        t = setTimeout(() => onSearch(searchInput.value || ""), 200)
      })
      searchInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") onSearch(searchInput.value || "")
      })
    }

    // cleanup handle
    return function destroy() {
      document.removeEventListener("click", closeDropdown)
    }
  }

  function create(mountOrSelector, options) {
    const opts = options || {}
    const mount =
      typeof mountOrSelector === "string"
        ? document.querySelector(mountOrSelector)
        : mountOrSelector

    if (!mount) throw new Error("KNNav: mount element not found")

    mount.innerHTML = renderTemplate(opts)

    const destroy = attachEvents(mount, opts)

    return {
      mount,
      destroy,
      setActive(key) {
        mount.querySelectorAll(".kn-nav-btn[data-go]").forEach((b) => {
          b.classList.toggle("active", (b.getAttribute("data-go") || "") === String(key))
        })
      },
      setUser(user) {
        const u = user || { isGuest: true, email: "Guest" }
        const email = u.isGuest ? "Guest" : (u.email || "User")
        const letter = u.avatarLetter || firstLetter(email)

        const avatarEl = mount.querySelector("#kn-avatar")
        const emailEl = mount.querySelector("#kn-dropdown-email")
        const logoutEl = mount.querySelector("#kn-dropdown-logout")

        if (avatarEl) avatarEl.textContent = letter
        if (emailEl) emailEl.textContent = "Đang đăng nhập: " + email
        if (logoutEl) logoutEl.childNodes[2].nodeValue = " " + (u.isGuest ? "Đăng nhập" : "Đăng xuất")
      },
    }
  }

  w.KNNav = { create }
})(window)
