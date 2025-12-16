// ==== AUTH / NAV DEMO ====
const TOKEN_KEY = "kn_token";
const currentUser = null;
const res = await fetch(`${API_BASE}/api/me`, {
    headers: { "Authorization": "Bearer " + token }
});

const userEmailLabel = document.getElementById("user-email-label");
const userAvatar = document.getElementById("user-avatar");
const logoutBtn = document.getElementById("logout-btn");

userEmailLabel.textContent = currentUser || "";
userAvatar.textContent = (currentUser || "U").charAt(0).toUpperCase();

logoutBtn.addEventListener("click", () => {
  localStorage.removeItem(TOKEN_KEY);
  window.location.href = "/Auth";
});

// NAV avatar + dropdown
const avatarNav = document.getElementById("kn-avatar");
const avatarWrap = document.getElementById("kn-avatar-wrap");
const dropdownEmail = document.getElementById("kn-dropdown-email");
const dropdownLogout = document.getElementById("kn-dropdown-logout");

avatarNav.textContent = (currentUser || "U").charAt(0).toUpperCase();
dropdownEmail.textContent = "Đang đăng nhập: " + (currentUser || "Guest");

avatarNav.addEventListener("click", () => {
  avatarWrap.classList.toggle("open");
});

document.addEventListener("click", (e) => {
  if (!avatarWrap.contains(e.target)) {
    avatarWrap.classList.remove("open");
  }
});

dropdownLogout.addEventListener("click", () => {
  localStorage.removeItem(TOKEN_KEY);
  window.location.href = "/Auth";
});

// Active tab
const path = window.location.pathname;
document.querySelectorAll(".kn-nav-btn").forEach((btn) => {
  const target = btn.dataset.go;
  if (target === "design" && path.includes("design")) {
    btn.classList.add("active");
  }
  if (target === "auth" && path.includes("auth")) {
    btn.classList.add("active");
  }

  btn.addEventListener("click", () => {
    if (target === "auth") window.location.href = "/Auth";
    if (target === "design") window.location.href = "/Home";
  });
});

// ==== DOM ELEMENTS ====
const card = document.getElementById("card-front");
const cardBack = document.getElementById("card-back");
const backOverlay = cardBack.querySelector(".back-overlay");
const backDarkLayer = document.getElementById("back-dark-layer");
const frontDarkLayer = document.getElementById("front-dark-layer");

const backShortEl = document.getElementById("back-short");

const cardTitle = document.getElementById("card-title");
const cardSubtitle = document.getElementById("card-subtitle");
const cardIconImg = document.getElementById("card-icon-img");
const cardLogo = document.getElementById("card-logo");
const bioQrWrapper = document.getElementById("bio-qr-wrapper");
const bioTextBlock = document.getElementById("bio-text-block");

const inputTitle = document.getElementById("input-title");
const inputSubtitle = document.getElementById("input-subtitle");
const inputTitleSize = document.getElementById("input-title-size");
const labelTitleSize = document.getElementById("label-title-size");
const inputSubtitleSize = document.getElementById("input-subtitle-size");
const labelSubtitleSize = document.getElementById("label-subtitle-size");

const fontPresets = document.getElementById("font-presets");

const inputAvatar = document.getElementById("input-avatar");
const bgPresets = document.getElementById("bg-presets");
const inputBgImage = document.getElementById("input-bg-image");
const btnRemoveBg = document.getElementById("btn-remove-bg");

const inputDarkness = document.getElementById("input-darkness");
const labelDarkness = document.getElementById("label-darkness");

const inputLogo = document.getElementById("input-logo");
const btnToggleLogo = document.getElementById("btn-toggle-logo");

const inputCustomLayer = document.getElementById("input-custom-layer");
const layerList = document.getElementById("layer-list");

// text layer controls
const inputLayerText = document.getElementById("input-layer-text");
const inputLayerTextSize = document.getElementById("input-layer-text-size");
const labelLayerTextSize = document.getElementById("label-layer-text-size");
const layerTextFont = document.getElementById("layer-text-font");
const btnAddTextLayer = document.getElementById("btn-add-text-layer");

const inputQrUrl = document.getElementById("input-qr-url");
const inputQrSize = document.getElementById("input-qr-size");
const labelQrSize = document.getElementById("label-qr-size");

const chkSyncBack = document.getElementById("chk-sync-back");
const chkEditBack = document.getElementById("chk-edit-back");
const inputBackShort = document.getElementById("input-back-short");
const inputBackSize = document.getElementById("input-back-size");
const labelBackSize = document.getElementById("label-back-size");

const btnDownloadPng = document.getElementById("btn-download-png");
const btnDownloadPdf = document.getElementById("btn-download-pdf");
const btnResetLayout = document.getElementById("btn-reset-layout");

// ==== STATE ====
let logoVisible = false;
let currentBgMode = "default";
let layerCounter = 0;
let currentMaxZ = 30;

// font map
const FONT_MAP = {
  inter: '"Inter", system-ui, -apple-system, BlinkMacSystemFont, sans-serif',
  poppins: '"Poppins", system-ui, -apple-system, BlinkMacSystemFont, sans-serif',
  playfair: '"Playfair Display", "Times New Roman", serif',
};

// ==== HELPERS ====
function applyBgPreset(name) {
  currentBgMode = name;
  card.style.backgroundImage = "";
  if (name === "default") {
    card.style.background = "linear-gradient(135deg, #3b82f6, #10b981)";
  } else if (name === "purple") {
    card.style.background = "linear-gradient(135deg, #6366f1, #0ea5e9)";
  } else if (name === "orange") {
    card.style.background = "linear-gradient(135deg, #fb923c, #f97316)";
  } else if (name === "dark") {
    card.style.background = "linear-gradient(135deg, #0f172a, #111827)";
  }
  syncBackBackground();
}

function syncBackBackground() {
  if (!chkSyncBack.checked) return;
  backOverlay.style.background = card.style.background;
  if (card.style.backgroundImage) {
    backOverlay.style.backgroundImage = card.style.backgroundImage;
    backOverlay.style.backgroundSize = card.style.backgroundSize;
    backOverlay.style.backgroundPosition = card.style.backgroundPosition;
  } else {
    backOverlay.style.backgroundImage = "";
  }
}

applyBgPreset("default");

// chỉnh độ tối cho cả front + back
function applyDarkness() {
  const val = Number(inputDarkness.value) || 0;
  const alpha = val / 100; // max 0.6 khi max=60
  labelDarkness.textContent = val + "%";
  const bg = `rgba(0,0,0,${alpha})`;
  frontDarkLayer.style.background = bg;
  backDarkLayer.style.background = bg;
}
applyDarkness();

// collision check: layer không che logo
function isOverlapWithLogo(newX, newY, el) {
  if (!logoVisible) return false;
  const cardRect = card.getBoundingClientRect();
  const logoRect = cardLogo.getBoundingClientRect();

  const elWidth = el.offsetWidth;
  const elHeight = el.offsetHeight;

  const elLeft = newX;
  const elTop = newY;
  const elRight = elLeft + elWidth;
  const elBottom = elTop + elHeight;

  const logoLeft = logoRect.left - cardRect.left;
  const logoTop = logoRect.top - cardRect.top;
  const logoRight = logoLeft + logoRect.width;
  const logoBottom = logoTop + logoRect.height;

  const margin = 6;

  const overlap =
    elLeft < logoRight + margin &&
    elRight > logoLeft - margin &&
    elTop < logoBottom + margin &&
    elBottom > logoTop - margin;

  return overlap;
}

// generic drag
function makeDraggable(el) {
  let isDown = false;
  let offsetX = 0;
  let offsetY = 0;

  function getPoint(e) {
    // Lấy toạ độ cho cả mouse và touch
    if (e.touches && e.touches[0]) return e.touches[0];
    if (e.changedTouches && e.changedTouches[0]) return e.changedTouches[0];
    return e;
  }

  function startDrag(e) {
    e.preventDefault();                    // tránh scroll khi kéo
    const p = getPoint(e);
    const rect = el.getBoundingClientRect();

    isDown = true;
    offsetX = p.clientX - rect.left;
    offsetY = p.clientY - rect.top;
    el.style.cursor = "grabbing";
  }

  function endDrag() {
    isDown = false;
    el.style.cursor = "grab";
  }

  function onMove(e) {
    if (!isDown) return;
    e.preventDefault();

    const p = getPoint(e);
    const cardRect = card.getBoundingClientRect();

    let x = p.clientX - cardRect.left - offsetX;
    let y = p.clientY - cardRect.top - offsetY;

    const maxX = cardRect.width - el.offsetWidth;
    const maxY = cardRect.height - el.offsetHeight;

    x = Math.max(0, Math.min(x, maxX));
    y = Math.max(0, Math.min(y, maxY));

    el.style.position = "absolute";
    el.style.left = x + "px";
    el.style.top = y + "px";
  }

  // Mouse
  el.addEventListener("mousedown", startDrag);
  document.addEventListener("mousemove", onMove);
  document.addEventListener("mouseup", endDrag);

  // Touch
  el.addEventListener("touchstart", startDrag, { passive: false });
  document.addEventListener("touchmove", onMove, { passive: false });
  document.addEventListener("touchend", endDrag);
}


// ==== BACK CONTROLS ====
chkSyncBack.addEventListener("change", syncBackBackground);

chkEditBack.addEventListener("change", () => {
  const editable = chkEditBack.checked;
  inputBackShort.disabled = !editable;
});

inputBackShort.addEventListener("input", () => {
  backShortEl.textContent = inputBackShort.value || "";
});

// size mô tả mặt sau
inputBackSize.addEventListener("input", () => {
  const val = Number(inputBackSize.value);
  labelBackSize.textContent = val + "%";
  const base = 11; // base px
  backShortEl.style.fontSize = (base * val) / 100 + "px";
});

// ==== TEXT / TITLE ====
inputTitle.addEventListener("input", () => {
  cardTitle.textContent = inputTitle.value || "Guest User";
});

inputSubtitle.addEventListener("input", () => {
  cardSubtitle.textContent =
    inputSubtitle.value || "Thẻ bio cá nhân tạo bằng KN BioCard";
});

inputTitleSize.addEventListener("input", () => {
  const val = Number(inputTitleSize.value);
  labelTitleSize.textContent = val + "%";
  const base = 22;
  cardTitle.style.fontSize = (base * val) / 100 + "px";
});

inputSubtitleSize.addEventListener("input", () => {
  const val = Number(inputSubtitleSize.value);
  labelSubtitleSize.textContent = val + "%";
  const base = 14;
  cardSubtitle.style.fontSize = (base * val) / 100 + "px";
});

// ==== FONT PRESETS ====
fontPresets.addEventListener("click", (e) => {
  const btn = e.target.closest(".pill-option");
  if (!btn) return;
  fontPresets.querySelectorAll(".pill-option").forEach((b) =>
    b.classList.remove("active")
  );
  btn.classList.add("active");
  const key = btn.dataset.font;
  const family = FONT_MAP[key] || FONT_MAP.inter;
  cardTitle.style.fontFamily = family;
  cardSubtitle.style.fontFamily = family;
  backShortEl.style.fontFamily = family;
});

// ==== AVATAR ====
inputAvatar.addEventListener("change", (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function (ev) {
    cardIconImg.src = ev.target.result;
  };
  reader.readAsDataURL(file);
});

// ==== BACKGROUND ====
bgPresets.addEventListener("click", (e) => {
  const btn = e.target.closest(".pill-option");
  if (!btn) return;
  bgPresets.querySelectorAll(".pill-option").forEach((b) =>
    b.classList.remove("active")
  );
  btn.classList.add("active");
  applyBgPreset(btn.dataset.bg);
});

inputBgImage.addEventListener("change", (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function (ev) {
    card.style.backgroundImage = `url('${ev.target.result}')`;
    card.style.backgroundSize = "cover";
    card.style.backgroundPosition = "center";
    syncBackBackground();
  };
  reader.readAsDataURL(file);
});

btnRemoveBg.addEventListener("click", () => {
  inputBgImage.value = "";
  applyBgPreset(currentBgMode);
});

// DARKNESS
inputDarkness.addEventListener("input", applyDarkness);

// ==== LOGO (FRONT) ====
inputLogo.addEventListener("change", (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function (ev) {
    cardLogo.style.backgroundImage = `url('${ev.target.result}')`;
    cardLogo.classList.add("show");
    logoVisible = true;
  };
  reader.readAsDataURL(file);
});

btnToggleLogo.addEventListener("click", () => {
  logoVisible = !logoVisible;
  if (logoVisible) {
    cardLogo.classList.add("show");
  } else {
    cardLogo.classList.remove("show");
  }
});

// ==== QR CODE ====
function renderQR() {
  const url = inputQrUrl.value.trim() || "https://example.com";
  const size = Number(inputQrSize.value) || 120;
  labelQrSize.textContent = size + "px";

  const container = document.getElementById("qr-container");
  container.innerHTML = "";
  new QRCode(container, {
    text: url,
    width: size,
    height: size,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H,
  });
}
inputQrUrl.addEventListener("input", renderQR);
inputQrSize.addEventListener("input", renderQR);
renderQR();

// ==== LAYER SYSTEM ====
function setActiveLayerItem(item) {
  layerList.querySelectorAll(".layer-item").forEach((el) =>
    el.classList.remove("active")
  );
  if (item) item.classList.add("active");
}

function createImageLayer(src, name) {
  const id = ++layerCounter;

  const img = document.createElement("img");
  img.src = src;
  img.className = "card-layer";
  img.dataset.layerId = String(id);
  img.dataset.layerType = "image";
  img.style.zIndex = String(++currentMaxZ);
  img.dataset.size = "120";

  card.appendChild(img);
  makeDraggable(img, { avoidLogo: true });

  const item = document.createElement("div");
  item.className = "layer-item";
  item.dataset.layerId = String(id);
  item.dataset.layerType = "image";

  const mainRow = document.createElement("div");
  mainRow.className = "layer-main-row";
  mainRow.innerHTML = `
    <div class="layer-thumb" style="background-image:url('${src}')"></div>
    <div class="layer-name">Img ${id} – ${name || "image"}</div>
    <div class="layer-actions">
      <button type="button" data-eye="1" title="Ẩn/hiện layer"><i class="fa-regular fa-eye"></i></button>
      <button type="button" data-delete="1" title="Xóa layer"><i class="fa-regular fa-trash-can"></i></button>
    </div>
  `;
  item.appendChild(mainRow);

  const sizeRow = document.createElement("div");
  sizeRow.className = "layer-size-row";
  sizeRow.innerHTML = `
    <input type="range" min="40" max="260" value="120" data-size-slider="${id}">
    <span data-size-label="${id}">120px</span>
  `;
  item.appendChild(sizeRow);

  layerList.appendChild(item);
  setActiveLayerItem(item);
}

function createTextLayer(content, fontKey, sizePx) {
  if (!content.trim()) return;
  const id = ++layerCounter;
  const family = FONT_MAP[fontKey] || FONT_MAP.inter;

  const span = document.createElement("div");
  span.className = "card-layer-text";
  span.dataset.layerId = String(id);
  span.dataset.layerType = "text";
  span.textContent = content;
  span.style.fontFamily = family;
  span.style.fontSize = sizePx + "px";
  span.dataset.size = String(sizePx);

  span.style.zIndex = String(++currentMaxZ);
  card.appendChild(span);
  makeDraggable(span, { avoidLogo: true });

  const item = document.createElement("div");
  item.className = "layer-item";
  item.dataset.layerId = String(id);
  item.dataset.layerType = "text";

  const fontLabel =
    fontKey === "poppins" ? "Pop" : fontKey === "playfair" ? "Serif" : "Sans";

  const mainRow = document.createElement("div");
  mainRow.className = "layer-main-row";
  mainRow.innerHTML = `
    <div class="layer-thumb"><span>T</span></div>
    <div class="layer-name">Text ${id} – ${fontLabel}</div>
    <div class="layer-actions">
      <button type="button" data-eye="1" title="Ẩn/hiện layer"><i class="fa-regular fa-eye"></i></button>
      <button type="button" data-delete="1" title="Xóa layer"><i class="fa-regular fa-trash-can"></i></button>
    </div>
  `;
  item.appendChild(mainRow);

  const sizeRow = document.createElement("div");
  sizeRow.className = "layer-size-row";
  sizeRow.innerHTML = `
    <input type="range" min="10" max="40" value="${sizePx}" data-size-slider="${id}">
    <span data-size-label="${id}">${sizePx}px</span>
  `;
  item.appendChild(sizeRow);

  layerList.appendChild(item);
  setActiveLayerItem(item);
}

// upload image layer
inputCustomLayer.addEventListener("change", (e) => {
  const files = Array.from(e.target.files || []);
  files.forEach((file) => {
    const reader = new FileReader();
    reader.onload = (ev) => {
      createImageLayer(ev.target.result, file.name);
    };
    reader.readAsDataURL(file);
  });
  inputCustomLayer.value = "";
});

// text layer controls
layerTextFont.addEventListener("click", (e) => {
  const btn = e.target.closest(".pill-option");
  if (!btn) return;
  layerTextFont.querySelectorAll(".pill-option").forEach((b) =>
    b.classList.remove("active")
  );
  btn.classList.add("active");
});

inputLayerTextSize.addEventListener("input", () => {
  const val = Number(inputLayerTextSize.value);
  labelLayerTextSize.textContent = val + "px";
});

btnAddTextLayer.addEventListener("click", () => {
  const content = inputLayerText.value || "";
  const size = Number(inputLayerTextSize.value) || 18;
  const activeBtn =
    layerTextFont.querySelector(".pill-option.active") ||
    layerTextFont.querySelector('[data-font="inter"]');
  const fontKey = activeBtn.dataset.font || "inter";
  createTextLayer(content, fontKey, size);
});

// layer list actions
layerList.addEventListener("click", (e) => {
  const item = e.target.closest(".layer-item");
  if (!item) return;
  const id = item.dataset.layerId;
  const type = item.dataset.layerType;
  const selector =
    type === "text"
      ? `.card-layer-text[data-layer-id="${id}"]`
      : `.card-layer[data-layer-id="${id}"]`;
  const layerEl = card.querySelector(selector);
  if (!layerEl) {
    item.remove();
    return;
  }

  // delete
  if (e.target.closest("button[data-delete]")) {
    layerEl.remove();
    item.remove();
    return;
  }

  // eye
  if (e.target.closest("button[data-eye]")) {
    const hidden = layerEl.style.display === "none";
    layerEl.style.display = hidden ? "block" : "none";
    const icon = e.target.closest("button").querySelector("i");
    if (hidden) {
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    } else {
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    }
    return;
  }

  // chọn layer -> đưa lên trên cùng
  currentMaxZ += 1;
  layerEl.style.zIndex = String(currentMaxZ);
  setActiveLayerItem(item);
});

// slider size cho từng layer
layerList.addEventListener("input", (e) => {
  const slider = e.target;
  const id = slider.getAttribute("data-size-slider");
  if (!id) return;
  const val = Number(slider.value);
  const label = layerList.querySelector(`span[data-size-label="${id}"]`);

  const imgEl = card.querySelector(`.card-layer[data-layer-id="${id}"]`);
  const textEl = card.querySelector(`.card-layer-text[data-layer-id="${id}"]`);

  if (imgEl) {
    imgEl.style.width = val + "px";
    imgEl.dataset.size = String(val);
  }
  if (textEl) {
    textEl.style.fontSize = val + "px";
    textEl.dataset.size = String(val);
  }
  if (label) label.textContent = val + "px";
});

// ==== DRAGGABLE ELEMENTS ====
makeDraggable(bioQrWrapper, { avoidLogo: true });
makeDraggable(cardLogo, { avoidLogo: false });
makeDraggable(bioTextBlock, { avoidLogo: true });

// ==== RESET ====
btnResetLayout.addEventListener("click", () => {
  bioQrWrapper.style.position = "";
  bioQrWrapper.style.left = "";
  bioQrWrapper.style.top = "";

  cardLogo.style.position = "";
  cardLogo.style.left = "";
  cardLogo.style.top = "";

  bioTextBlock.style.position = "";
  bioTextBlock.style.left = "";
  bioTextBlock.style.top = "";

  inputTitleSize.value = 100;
  labelTitleSize.textContent = "100%";
  cardTitle.style.fontSize = "22px";

  inputSubtitleSize.value = 100;
  labelSubtitleSize.textContent = "100%";
  cardSubtitle.style.fontSize = "14px";

  inputBackSize.value = 100;
  labelBackSize.textContent = "100%";
  backShortEl.style.fontSize = "11px";

  inputQrSize.value = 120;
  renderQR();

  // reset font
  fontPresets.querySelectorAll(".pill-option").forEach((b) =>
    b.classList.remove("active")
  );
  fontPresets.querySelector('[data-font="inter"]').classList.add("active");
  const family = FONT_MAP.inter;
  cardTitle.style.fontFamily = family;
  cardSubtitle.style.fontFamily = family;
  backShortEl.style.fontFamily = family;

  // reset darkness
  inputDarkness.value = 0;
  applyDarkness();

  // clear layers
  card.querySelectorAll(".card-layer, .card-layer-text").forEach((el) =>
    el.remove()
  );
  layerList.innerHTML = "";
  layerCounter = 0;
  currentMaxZ = 30;

  inputLayerText.value = "";
  inputLayerTextSize.value = 18;
  labelLayerTextSize.textContent = "18px";
});

// ==== EXPORT PNG/PDF ====
btnDownloadPng.addEventListener("click", () => {
  html2canvas(card, { scale: 3 }).then((canvas) => {
    const link = document.createElement("a");
    link.download = "kn-biocard-front.png";
    link.href = canvas.toDataURL("image/png");
    link.click();
  });
});

btnDownloadPdf.addEventListener("click", () => {
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF("l", "mm", "credit-card");

  html2canvas(card, { scale: 3 }).then((canvasFront) => {
    const imgFront = canvasFront.toDataURL("image/png");
    const w = pdf.internal.pageSize.getWidth();
    const h = pdf.internal.pageSize.getHeight();
    pdf.addImage(imgFront, "PNG", 0, 0, w, h);

    pdf.addPage();
    html2canvas(cardBack, { scale: 3 }).then((canvasBack) => {
      const imgBack = canvasBack.toDataURL("image/png");
      pdf.addImage(imgBack, "PNG", 0, 0, w, h);
      pdf.save("kn-biocard-2-mat.pdf");
    });
  });
});

// init
syncBackBackground();
