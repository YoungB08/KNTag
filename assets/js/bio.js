const TOKEN_KEY = "kn_token";
const API_BASE = "/index.php";

const blocksContainer = document.getElementById("blocks");

const nameInput = document.getElementById("name-input");
const nickInput = document.getElementById("nick-input");
const roleInput = document.getElementById("role-input");
const jobsInput = document.getElementById("jobs-input");
const introInput = document.getElementById("intro-input");

const btnAddText = document.getElementById("btn-add-text");
const btnAddIcon = document.getElementById("btn-add-icon");

// BACKGROUND TYPE (default/dark/light/color/gradient)
const bgSelect = document.getElementById("bg-style-select");

// BACKGROUND UI PANELS
const bgPanel = document.getElementById("bg-panel");
const colorPanel = document.getElementById("bg-color-panel");
const gradPanel = document.getElementById("bg-gradient-panel");

const colorInput = document.getElementById("bg-color-input");
const colorHex = document.getElementById("bg-color-hex");
const colorPalette = document.getElementById("bg-color-palette");

const gradA = document.getElementById("bg-grad-a");
const gradAHex = document.getElementById("bg-grad-a-hex");
const gradB = document.getElementById("bg-grad-b");
const gradBHex = document.getElementById("bg-grad-b-hex");
const gradAngle = document.getElementById("bg-grad-angle");
const gradAngleVal = document.getElementById("bg-grad-angle-val");
const gradPalette = document.getElementById("bg-gradient-palette");

const fontSelect = document.getElementById("font-style-select");

const btnSave = document.getElementById("btn-save-layout");
const saveStatus = document.getElementById("save-status");

// PREVIEW: card/bg/content (đúng cấu trúc HTML của bro)
const previewCard = document.getElementById("bio-preview");
const previewBg = document.getElementById("bio-preview-bg");
const previewContent = document.getElementById("bio-preview-content");

// NAV dropdown
const navAvatarWrap = document.getElementById("nav-avatar-wrap");
const navAvatar = document.getElementById("nav-avatar");
navAvatar?.addEventListener("click", () => navAvatarWrap?.classList.toggle("open"));
document.addEventListener("click", (e) => {
if (navAvatarWrap && !navAvatarWrap.contains(e.target)) navAvatarWrap.classList.remove("open");
});

let avatarDataUrl = null;
let currentIconTarget = null;

let customTextCount = 0;
let customIconCount = 0;

// =========================
// BACKGROUND CONFIG (SAVE/LOAD via API)
// =========================
let bgCfg = {
type: "default",     // default | dark | light | color | gradient
color: "#1b1f2a",    // for color
a: "#00c6ff",        // gradient start
b: "#0072ff",        // gradient end
angle: 135           // gradient angle
};

const COLOR_SWATCHES = [
"#0b1020","#111827","#0f172a","#1f2937","#0b1220",
"#1b1f2a","#0b1320","#141a2a","#121826","#0c1222",
"#ffffff","#f8fafc","#f1f5f9","#e2e8f0","#cbd5e1",
"#fde68a","#fca5a5","#a7f3d0","#93c5fd","#c4b5fd"
];

const GRAD_SWATCHES = [
["#00c6ff", "#0072ff", 135],
["#a18cd1", "#fbc2eb", 135],
["#f093fb", "#f5576c", 135],
["#43e97b", "#38f9d7", 135],
["#fa709a", "#fee140", 135],
["#30cfd0", "#330867", 135],
["#667eea", "#764ba2", 135],
["#232526", "#414345", 135],
["#0f2027", "#203a43", 135],
["#fdfbfb", "#ebedee", 135]
];

function setSaveStatus(msg, isError = false) {
if (!saveStatus) return;
saveStatus.textContent = msg || "";
saveStatus.style.color = isError ? "#dc2626" : "";
}

function isHex(v) {
return typeof v === "string" && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v.trim());
}

function normHex(v, fallback) {
v = (v || "").trim();
if (!v) return fallback;
if (v[0] !== "#") v = "#" + v;
return isHex(v) ? v : fallback;
}

function hexToRgb(hex) {
const h = normHex(hex, "#000000").slice(1);
const full = (h.length === 3) ? h.split("").map(x => x + x).join("") : h;
const n = parseInt(full, 16);
return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}

function luminance(hex) {
const { r, g, b } = hexToRgb(hex);
const srgb = [r, g, b].map(v => {
    v /= 255;
    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
});
return 0.2126 * srgb[0] + 0.7152 * srgb[1] + 0.0722 * srgb[2];
}

function pickFgForBg(bgHex) {
const L = luminance(bgHex);
return L < 0.35 ? "#e5e7eb" : "#111827";
}

function syncBgPanels(type) {
const adv = (type === "color" || type === "gradient");
if (bgPanel) bgPanel.style.display = adv ? "" : "none";
if (colorPanel) colorPanel.style.display = (type === "color") ? "" : "none";
if (gradPanel) gradPanel.style.display = (type === "gradient") ? "" : "none";
}

function renderColorPalette() {
if (!colorPalette) return;
colorPalette.innerHTML = "";
COLOR_SWATCHES.forEach(hex => {
    const d = document.createElement("div");
    d.className = "bg-swatch";
    d.style.width = "100%";
    d.style.aspectRatio = "1/1";
    d.style.borderRadius = "10px";
    d.style.cursor = "pointer";
    d.style.boxShadow = "0 0 0 1px rgba(255,255,255,.12) inset";
    d.style.background = hex;
    d.title = hex;
    d.addEventListener("click", () => {
    const val = normHex(hex, "#1b1f2a");
    bgCfg.type = "color";
    bgCfg.color = val;
    if (bgSelect) bgSelect.value = "color";
    if (colorInput) colorInput.value = val;
    if (colorHex) colorHex.value = val;
    applyCardStyle();
    renderPreview();
    });
    colorPalette.appendChild(d);
});
}

function renderGradientPalette() {
if (!gradPalette) return;
gradPalette.innerHTML = "";
GRAD_SWATCHES.forEach(([a, b, ang]) => {
    const d = document.createElement("div");
    d.className = "bg-swatch";
    d.style.width = "100%";
    d.style.height = "44px";
    d.style.borderRadius = "12px";
    d.style.cursor = "pointer";
    d.style.boxShadow = "0 0 0 1px rgba(255,255,255,.12) inset";
    d.style.background = `linear-gradient(${ang}deg, ${a}, ${b})`;
    d.title = `${a} → ${b} (${ang}°)`;
    d.addEventListener("click", () => {
    bgCfg.type = "gradient";
    bgCfg.a = normHex(a, "#00c6ff");
    bgCfg.b = normHex(b, "#0072ff");
    bgCfg.angle = Number.isFinite(ang) ? ang : 135;
    if (bgSelect) bgSelect.value = "gradient";
    if (gradA) gradA.value = bgCfg.a;
    if (gradAHex) gradAHex.value = bgCfg.a;
    if (gradB) gradB.value = bgCfg.b;
    if (gradBHex) gradBHex.value = bgCfg.b;
    if (gradAngle) gradAngle.value = String(bgCfg.angle);
    if (gradAngleVal) gradAngleVal.textContent = `${bgCfg.angle}°`;
    applyCardStyle();
    renderPreview();
    });
    gradPalette.appendChild(d);
});
}

function applyCardStyle() {
const style = bgSelect?.value || bgCfg.type || "default";
bgCfg.type = style;

const font = fontSelect?.value || "inter";

// font
let fontFamily = '"Inter", system-ui, -apple-system, BlinkMacSystemFont, sans-serif';
if (font === "poppins") fontFamily = '"Poppins", system-ui, -apple-system, BlinkMacSystemFont, sans-serif';
if (font === "dm-sans") fontFamily = '"DM Sans", system-ui, -apple-system, BlinkMacSystemFont, sans-serif';
previewCard.style.fontFamily = fontFamily;

// bg panels visibility
syncBgPanels(style);

// reset
previewCard.classList.remove("is-dark");

// base bg + fg
let baseBg = "";
let fg = "#111827";

if (style === "default") {
    baseBg = ""; // để CSS của bro tự ăn
    fg = "#111827";
} else if (style === "light") {
    baseBg = "#f8fafc";
    fg = "#111827";
} else if (style === "dark") {
    baseBg = "linear-gradient(135deg, #0f172a, #111827)";
    fg = "#e5e7eb";
    previewCard.classList.add("is-dark");
} else if (style === "color") {
    const c = normHex(colorHex?.value, normHex(colorInput?.value, bgCfg.color || "#1b1f2a"));
    bgCfg.color = c;
    if (colorInput) colorInput.value = c;
    if (colorHex) colorHex.value = c;
    baseBg = c;
    fg = pickFgForBg(c);
    if (fg !== "#111827") previewCard.classList.add("is-dark");
} else if (style === "gradient") {
    const a = normHex(gradAHex?.value, normHex(gradA?.value, bgCfg.a || "#00c6ff"));
    const b = normHex(gradBHex?.value, normHex(gradB?.value, bgCfg.b || "#0072ff"));
    const ang = parseInt(String(gradAngle?.value || bgCfg.angle || 135), 10);
    bgCfg.a = a; bgCfg.b = b; bgCfg.angle = Number.isFinite(ang) ? ang : 135;

    if (gradA) gradA.value = a;
    if (gradAHex) gradAHex.value = a;
    if (gradB) gradB.value = b;
    if (gradBHex) gradBHex.value = b;
    if (gradAngle) gradAngle.value = String(bgCfg.angle);
    if (gradAngleVal) gradAngleVal.textContent = `${bgCfg.angle}°`;

    baseBg = `linear-gradient(${bgCfg.angle}deg, ${bgCfg.a}, ${bgCfg.b})`;

    // fg heuristic: lấy màu A làm đại diện
    fg = pickFgForBg(bgCfg.a);
    if (fg !== "#111827") previewCard.classList.add("is-dark");
}

previewCard.style.color = fg;

// apply base bg to previewBg (để không đè content)
previewBg.style.background = baseBg;

// đảm bảo previewBg nằm sau content
previewBg.style.position = "absolute";
previewBg.style.inset = "0";
previewBg.style.zIndex = "0";

previewContent.style.position = "relative";
previewContent.style.zIndex = "1";
}

// Events bg select + panels
bgSelect?.addEventListener("change", () => {
applyCardStyle();
renderPreview();
});

colorInput?.addEventListener("input", () => {
if (colorHex) colorHex.value = colorInput.value;
if (bgSelect) bgSelect.value = "color";
bgCfg.type = "color";
bgCfg.color = normHex(colorInput.value, "#1b1f2a");
applyCardStyle();
renderPreview();
});

colorHex?.addEventListener("input", () => {
const v = normHex(colorHex.value, bgCfg.color || "#1b1f2a");
if (colorInput) colorInput.value = v;
if (bgSelect) bgSelect.value = "color";
bgCfg.type = "color";
bgCfg.color = v;
applyCardStyle();
renderPreview();
});

function bindGradSync() {
gradA?.addEventListener("input", () => {
    if (gradAHex) gradAHex.value = gradA.value;
    if (bgSelect) bgSelect.value = "gradient";
    bgCfg.type = "gradient";
    bgCfg.a = normHex(gradA.value, "#00c6ff");
    applyCardStyle();
    renderPreview();
});

gradB?.addEventListener("input", () => {
    if (gradBHex) gradBHex.value = gradB.value;
    if (bgSelect) bgSelect.value = "gradient";
    bgCfg.type = "gradient";
    bgCfg.b = normHex(gradB.value, "#0072ff");
    applyCardStyle();
    renderPreview();
});

gradAHex?.addEventListener("input", () => {
    const v = normHex(gradAHex.value, bgCfg.a || "#00c6ff");
    if (gradA) gradA.value = v;
    if (bgSelect) bgSelect.value = "gradient";
    bgCfg.type = "gradient";
    bgCfg.a = v;
    applyCardStyle();
    renderPreview();
});

gradBHex?.addEventListener("input", () => {
    const v = normHex(gradBHex.value, bgCfg.b || "#0072ff");
    if (gradB) gradB.value = v;
    if (bgSelect) bgSelect.value = "gradient";
    bgCfg.type = "gradient";
    bgCfg.b = v;
    applyCardStyle();
    renderPreview();
});

gradAngle?.addEventListener("input", () => {
    if (gradAngleVal) gradAngleVal.textContent = `${gradAngle.value}°`;
    if (bgSelect) bgSelect.value = "gradient";
    bgCfg.type = "gradient";
    bgCfg.angle = parseInt(String(gradAngle.value || "135"), 10) || 135;
    applyCardStyle();
    renderPreview();
});
}

fontSelect?.addEventListener("change", () => {
applyCardStyle();
renderPreview();
});

// NO AUTOSAVE: chỉ render preview khi input thay đổi
[nameInput, nickInput, roleInput, jobsInput, introInput].forEach((el) => {
el?.addEventListener("input", () => renderPreview());
});

// Drag & drop (NO AUTOSAVE)
let draggingBlock = null;

function startBlockDrag(block) {
draggingBlock = block;
block.classList.add("dragging");
}

function endBlockDrag() {
if (!draggingBlock) return;
draggingBlock.classList.remove("dragging");
draggingBlock = null;
renderPreview();
}

function moveBlockDrag(clientY) {
if (!draggingBlock) return;
const afterElement = getDragAfterElement(blocksContainer, clientY);
if (afterElement == null) blocksContainer.appendChild(draggingBlock);
else blocksContainer.insertBefore(draggingBlock, afterElement);
}

blocksContainer?.addEventListener("mousedown", (e) => {
const handle = e.target.closest(".drag-handle");
if (!handle) return;
const block = handle.closest(".block");
if (!block) return;
e.preventDefault();
startBlockDrag(block);
});

document.addEventListener("mousemove", (e) => {
if (!draggingBlock) return;
e.preventDefault();
moveBlockDrag(e.clientY);
});

document.addEventListener("mouseup", endBlockDrag);

blocksContainer?.addEventListener("touchstart", (e) => {
const handle = e.target.closest(".drag-handle");
if (!handle) return;
const block = handle.closest(".block");
if (!block) return;
const touch = e.touches[0];
e.preventDefault();
startBlockDrag(block);
moveBlockDrag(touch.clientY);
}, { passive: false });

document.addEventListener("touchmove", (e) => {
if (!draggingBlock) return;
const touch = e.touches[0];
e.preventDefault();
moveBlockDrag(touch.clientY);
}, { passive: false });

document.addEventListener("touchend", endBlockDrag);

function getDragAfterElement(container, y) {
const draggableElements = [...container.querySelectorAll(".block:not(.dragging)")];
return draggableElements.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offset = y - box.top - box.height / 2;
    if (offset < 0 && offset > closest.offset) return { offset, element: child };
    return closest;
}, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
}

// BLOCK FACTORIES
function createAvatarBlock() {
const block = document.createElement("div");
block.className = "block";
block.dataset.type = "avatar";
block.innerHTML = `
    <div class="block-header">
    <div class="block-title">Avatar</div>
    <div class="block-desc">Ảnh đại diện tròn ở trên cùng.</div>
    </div>
    <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
    <div class="block-controls">
    <label class="file-pill">
        <i class="fa-regular fa-image"></i> Upload avatar
        <input type="file" class="avatar-file-inline" accept="image/*" style="display:none" />
    </label>
    </div>
`;

const af = block.querySelector(".avatar-file-inline");
af.addEventListener("change", (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
    avatarDataUrl = String(ev.target.result || "");
    renderPreview();
    };
    reader.readAsDataURL(file);
});

return block;
}

function createInputBlock(type, title, value) {
const block = document.createElement("div");
block.className = "block";
block.dataset.type = type;
block.innerHTML = `
    <div class="block-header">
    <div class="block-title">${escapeHtml(title)}</div>
    <div class="block-desc">Chỉnh nội dung hiển thị.</div>
    </div>
    <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
    <div class="block-controls">
    <input type="text" value="${escapeAttr(value || "")}" />
    </div>
`;

const input = block.querySelector("input");
input.addEventListener("input", () => {
    if (type === "name") nameInput.value = input.value;
    if (type === "nickname") nickInput.value = input.value;
    if (type === "role") roleInput.value = input.value;
    if (type === "jobs") jobsInput.value = input.value;
    renderPreview();
});

return block;
}

function createTextareaBlock(type, value) {
const block = document.createElement("div");
block.className = "block";
block.dataset.type = type;
block.innerHTML = `
    <div class="block-header">
    <div class="block-title">Giới thiệu ngắn</div>
    <div class="block-desc">1–3 câu về bản thân / dịch vụ.</div>
    </div>
    <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
    <div class="block-controls">
    <textarea>${escapeHtml(value || "")}</textarea>
    </div>
`;

const ta = block.querySelector("textarea");
ta.addEventListener("input", () => {
    introInput.value = ta.value;
    renderPreview();
});

return block;
}

function createCustomTextBlock(textValue) {
customTextCount++;
const block = document.createElement("div");
block.className = "block";
block.dataset.type = "text";
block.innerHTML = `
    <div class="block-header">
    <div class="block-title">Text tùy chỉnh ${customTextCount}</div>
    <div class="block-desc">Dòng text thêm vào Bio.</div>
    </div>
    <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
    <div class="block-controls">
    <input type="text" value="${escapeAttr(textValue || "")}" />
    </div>
`;
block.querySelector("input").addEventListener("input", renderPreview);
return block;
}

function createIconBlock(initialLabel, initialClass, initialUrl) {
const block = document.createElement("div");
block.className = "block";
block.dataset.type = "icon";
block.dataset.iconClass = initialClass || "fa-solid fa-star";

block.innerHTML = `
    <div class="block-header">
    <div class="block-title">Icon + nhãn</div>
    <div class="block-desc">Hiển thị icon + chữ, có thể gắn link.</div>
    </div>
    <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
    <div class="block-controls">
    <button type="button" class="icon-preview-pill">
        <i class="${block.dataset.iconClass}"></i>
    </button>
    <input type="text" class="icon-label-input" value="${escapeAttr(initialLabel || "")}" placeholder="Nhãn (ví dụ: Facebook)" />
    <input type="text" class="icon-url-input" value="${escapeAttr(initialUrl || "")}" placeholder="Link (https://..., để trống nếu không)" />
    <button type="button" class="btn-mini btn-choose-icon">
        <i class="fa-solid fa-icons"></i> Chọn icon
    </button>
    </div>
`;

block.querySelector(".icon-label-input").addEventListener("input", renderPreview);
block.querySelector(".icon-url-input").addEventListener("input", renderPreview);

return block;
}

function ensureCoreBlocksWithSync() {
const have = (t) => !!blocksContainer.querySelector(`.block[data-type="${t}"]`);
if (!have("avatar")) blocksContainer.prepend(createAvatarBlock());
if (!have("name")) blocksContainer.appendChild(createInputBlock("name", "Tên hiển thị", nameInput.value || "Guest User"));
if (!have("nickname")) blocksContainer.appendChild(createInputBlock("nickname", "Nickname / Username", nickInput.value || "@kn.biocard"));
if (!have("role")) blocksContainer.appendChild(createInputBlock("role", "Role / Vai trò", roleInput.value || "Bio & NFC Card Designer"));
if (!have("jobs")) blocksContainer.appendChild(createInputBlock("jobs", "Jobs / Lĩnh vực", jobsInput.value || "NFC Card, Personal Branding, Web Design"));
if (!have("intro")) blocksContainer.appendChild(createTextareaBlock("intro", introInput.value || "Thiết kế Bio page & thẻ NFC cho creator, freelancer và thương hiệu nhỏ – tối ưu UI/UX & trải nghiệm chia sẻ link."));
}

// Add blocks
btnAddText?.addEventListener("click", () => {
blocksContainer.appendChild(createCustomTextBlock("Dòng text mới"));
renderPreview();
});

btnAddIcon?.addEventListener("click", () => {
blocksContainer.appendChild(createIconBlock("Website", "fa-solid fa-globe", ""));
renderPreview();
});

// ICON PICKER
const ICONS = [
{ cls: "fa-brands fa-facebook-f", label: "Facebook" },
{ cls: "fa-brands fa-instagram", label: "Instagram" },
{ cls: "fa-brands fa-tiktok", label: "TikTok" },
{ cls: "fa-brands fa-youtube", label: "YouTube" },
{ cls: "fa-brands fa-x-twitter", label: "X / Twitter" },
{ cls: "fa-brands fa-linkedin-in", label: "LinkedIn" },
{ cls: "fa-brands fa-github", label: "GitHub" },
{ cls: "fa-brands fa-discord", label: "Discord" },
{ cls: "fa-brands fa-telegram", label: "Telegram" },
{ cls: "fa-brands fa-whatsapp", label: "WhatsApp" },
{ cls: "fa-brands fa-facebook-messenger", label: "Messenger" },
{ cls: "fa-solid fa-globe", label: "Website" },
{ cls: "fa-solid fa-link", label: "Link" },
{ cls: "fa-solid fa-envelope", label: "Email" },
{ cls: "fa-solid fa-phone", label: "Phone" },
{ cls: "fa-solid fa-location-dot", label: "Location" },
{ cls: "fa-solid fa-briefcase", label: "Công việc" },
{ cls: "fa-solid fa-building", label: "Công ty" },
{ cls: "fa-solid fa-camera", label: "Photo" },
{ cls: "fa-solid fa-music", label: "Music" },
{ cls: "fa-solid fa-palette", label: "Design" },
{ cls: "fa-solid fa-pen-nib", label: "Viết" },
{ cls: "fa-solid fa-graduation-cap", label: "Education" },
{ cls: "fa-solid fa-code", label: "Developer" },
{ cls: "fa-solid fa-gamepad", label: "Game" },
{ cls: "fa-solid fa-heart", label: "Love" },
{ cls: "fa-solid fa-star", label: "Star" },
{ cls: "fa-solid fa-fire", label: "Hot" },
{ cls: "fa-solid fa-circle-info", label: "Info" },
{ cls: "fa-solid fa-bag-shopping", label: "Shop" },
{ cls: "fa-solid fa-book", label: "Blog" },
{ cls: "fa-solid fa-calendar-days", label: "Booking" },
{ cls: "fa-solid fa-user", label: "Profile" },
{ cls: "fa-solid fa-users", label: "Community" }
];

const iconModal = document.getElementById("icon-modal");
const iconModalClose = document.getElementById("icon-modal-close");
const iconGrid = document.getElementById("icon-grid");
const iconSearchInput = document.getElementById("icon-search");

blocksContainer?.addEventListener("click", (e) => {
const chooseBtn = e.target.closest(".btn-choose-icon");
if (!chooseBtn) return;
currentIconTarget = chooseBtn.closest(".block");
openIconModal();
});

function openIconModal() {
if (!currentIconTarget) return;
iconModal.classList.add("show");
iconSearchInput.value = "";
renderIconGrid(ICONS);
iconSearchInput.focus();
}

function closeIconModal() {
iconModal.classList.remove("show");
}

iconModalClose?.addEventListener("click", closeIconModal);
iconModal?.addEventListener("click", (e) => {
if (e.target === iconModal) closeIconModal();
});

iconSearchInput?.addEventListener("input", () => {
const q = iconSearchInput.value.trim().toLowerCase();
const filtered = ICONS.filter((item) => ((item.label + " " + item.cls).toLowerCase()).includes(q));
renderIconGrid(filtered);
});

function renderIconGrid(list) {
iconGrid.innerHTML = "";
const inner = document.createElement("div");
inner.className = "icon-grid-inner";

list.forEach((item) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "icon-choice";
    btn.dataset.iconClass = item.cls;
    btn.innerHTML = `<i class="${item.cls}"></i><span>${escapeHtml(item.label)}</span>`;
    btn.addEventListener("click", () => {
    if (!currentIconTarget) return;
    currentIconTarget.dataset.iconClass = item.cls;
    const previewIcon = currentIconTarget.querySelector(".icon-preview-pill i");
    if (previewIcon) previewIcon.className = item.cls;
    renderPreview();
    closeIconModal();
    });
    inner.appendChild(btn);
});

iconGrid.appendChild(inner);
}

// RENDER PREVIEW
function renderPreview() {
previewContent.innerHTML = "";
const blocks = [...blocksContainer.querySelectorAll(".block")];

blocks.forEach((block) => {
    const type = block.dataset.type;

    if (type === "avatar") {
    const avatar = document.createElement("div");
    avatar.className = "preview-avatar";
    if (avatarDataUrl) {
        const img = document.createElement("img");
        img.src = avatarDataUrl;
        avatar.appendChild(img);
    } else {
        const icon = document.createElement("i");
        icon.className = "fa-regular fa-user";
        icon.style.color = "#ffffff";
        icon.style.fontSize = "36px";
        avatar.appendChild(icon);
    }
    previewContent.appendChild(avatar);
    }

    if (type === "name") {
    const el = block.querySelector('input[type="text"]') || nameInput;
    const nameEl = document.createElement("div");
    nameEl.className = "preview-name";
    nameEl.textContent = (el.value || "").trim() || "Tên của bạn";
    previewContent.appendChild(nameEl);
    }

    if (type === "nickname") {
    const el = block.querySelector('input[type="text"]') || nickInput;
    const nickEl = document.createElement("div");
    nickEl.className = "preview-nick";
    nickEl.textContent = (el.value || "").trim() || "@nickname";
    previewContent.appendChild(nickEl);
    }

    if (type === "role") {
    const el = block.querySelector('input[type="text"]') || roleInput;
    const roleText = (el.value || "").trim();
    if (roleText) {
        const roleEl = document.createElement("div");
        roleEl.className = "preview-role";
        roleEl.textContent = roleText;
        previewContent.appendChild(roleEl);
    }
    }

    if (type === "jobs") {
    const el = block.querySelector('input[type="text"]') || jobsInput;
    const raw = el.value || "";
    const jobs = raw.split(",").map((j) => j.trim()).filter(Boolean);
    if (jobs.length) {
        const wrap = document.createElement("div");
        wrap.className = "preview-jobs";
        jobs.forEach((job) => {
        const tag = document.createElement("span");
        tag.className = "job-tag";
        tag.textContent = job;
        wrap.appendChild(tag);
        });
        previewContent.appendChild(wrap);
    }
    }

    if (type === "intro") {
    const ta = block.querySelector("textarea") || introInput;
    const introText = (ta.value || "").trim();
    if (introText) {
        const introEl = document.createElement("div");
        introEl.className = "preview-intro";
        introEl.textContent = introText;
        previewContent.appendChild(introEl);
    }
    }

    if (type === "text") {
    const input = block.querySelector("input");
    const text = input ? (input.value || "").trim() : "";
    if (text) {
        const textEl = document.createElement("div");
        textEl.className = "preview-intro";
        textEl.textContent = text;
        previewContent.appendChild(textEl);
    }
    }

    if (type === "icon") {
    const labelInput = block.querySelector(".icon-label-input");
    const urlInput = block.querySelector(".icon-url-input");

    const title = labelInput ? labelInput.value.trim() : "";
    const url = urlInput ? urlInput.value.trim() : "";
    const iconClass = block.dataset.iconClass || "fa-solid fa-star";

    let list = previewContent.querySelector(".preview-links");
    if (!list) {
        list = document.createElement("div");
        list.className = "preview-links";
        previewContent.appendChild(list);
    }

    const card = document.createElement(url ? "a" : "div");
    card.className = "link-card";
    if (url) {
        card.href = normalizeUrl(url);
        card.target = "_blank";
        card.rel = "noopener";
    }

    const left = document.createElement("div");
    left.className = "link-card-icon";
    const ico = document.createElement("i");
    ico.className = iconClass;
    left.appendChild(ico);

    const body = document.createElement("div");
    body.className = "link-card-body";

    const t = document.createElement("div");
    t.className = "link-card-title";
    t.textContent = title || "Link";

    const sub = document.createElement("div");
    sub.className = "link-card-sub";
    sub.textContent = url ? url : "Chưa gắn link";

    body.appendChild(t);
    body.appendChild(sub);

    const right = document.createElement("div");
    right.className = "link-card-right";
    const arrow = document.createElement("i");
    arrow.className = "fa-solid fa-arrow-up-right-from-square";
    right.appendChild(arrow);

    card.appendChild(left);
    card.appendChild(body);
    card.appendChild(right);

    list.appendChild(card);
    }
});

applyCardStyle();
}

// PAYLOAD BUILD
function collectBlocksPayload() {
const blocks = [...blocksContainer.querySelectorAll(".block")];

return blocks.map((block) => {
    const type = block.dataset.type;

    if (type === "avatar") return { type, data: { dataUrl: avatarDataUrl || "" } };

    if (type === "name") {
    const v = block.querySelector("input")?.value ?? nameInput.value ?? "";
    return { type, data: { text: v } };
    }

    if (type === "nickname") {
    const v = block.querySelector("input")?.value ?? nickInput.value ?? "";
    return { type, data: { text: v } };
    }

    if (type === "role") {
    const v = block.querySelector("input")?.value ?? roleInput.value ?? "";
    return { type, data: { text: v } };
    }

    if (type === "jobs") {
    const v = block.querySelector("input")?.value ?? jobsInput.value ?? "";
    return { type, data: { text: v } };
    }

    if (type === "intro") {
    const v = block.querySelector("textarea")?.value ?? introInput.value ?? "";
    return { type, data: { text: v } };
    }

    if (type === "text") {
    const input = block.querySelector("input");
    return { type, data: { text: input ? input.value : "" } };
    }

    if (type === "icon") {
    const label = block.querySelector(".icon-label-input")?.value || "";
    const url = block.querySelector(".icon-url-input")?.value || "";
    const iconClass = block.dataset.iconClass || "fa-solid fa-star";
    return { type, data: { label, url, iconClass } };
    }

    return { type, data: {} };
});
}

function serializeBgCustomForApi() {
const t = bgCfg?.type || "default";
if (t === "color") return JSON.stringify({ type: "color", color: bgCfg.color });
if (t === "gradient") return JSON.stringify({ type: "gradient", a: bgCfg.a, b: bgCfg.b, angle: bgCfg.angle });
return "";
}

function parseBgFromApi(bg_style, bg_custom) {
const style = String(bg_style || "").trim() || "default";
const raw = String(bg_custom || "").trim();

// default/dark/light không cần bg_custom
if (!raw) {
    bgCfg.type = (style === "dark" || style === "light" || style === "color" || style === "gradient" || style === "default") ? style : "default";
    return;
}

// color/gradient: đọc JSON
try {
    const obj = JSON.parse(raw);
    if (obj && obj.type === "color") {
    bgCfg.type = "color";
    bgCfg.color = normHex(obj.color, "#1b1f2a");
    return;
    }
    if (obj && obj.type === "gradient") {
    bgCfg.type = "gradient";
    bgCfg.a = normHex(obj.a, "#00c6ff");
    bgCfg.b = normHex(obj.b, "#0072ff");
    bgCfg.angle = Number.isFinite(obj.angle) ? obj.angle : parseInt(String(obj.angle || "135"), 10) || 135;
    return;
    }
} catch {}

// fallback
bgCfg.type = (style === "dark" || style === "light" || style === "default") ? style : "default";
}

async function saveLayout() {
const token = localStorage.getItem(TOKEN_KEY);
if (!token) {
    window.location.href = "/Auth";
    return false;
}

const payload = {
    bio: {
    bg_style: bgCfg.type,
    font_style: fontSelect.value,
    bg_custom: serializeBgCustomForApi()
    },
    blocks: collectBlocksPayload()
};
// console.log("Saving payload:", payload);
const res = await fetch(`${API_BASE}/api/bio/layout`, {
    method: "PUT",
    headers: {
    "Content-Type": "application/json",
    "Authorization": "Bearer " + token
    },
    body: JSON.stringify(payload)
});

if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = "/Auth";
    return false;
}

const data = await res.json().catch(() => null);
return !!(res.ok && data && data.ok === true);
}

async function loadLayout() {
const token = localStorage.getItem(TOKEN_KEY);
if (!token) return;

const res = await fetch(`${API_BASE}/api/bio/layout`, {
    headers: { "Authorization": "Bearer " + token }
});

if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = "/Auth";
    return;
}

const data = await res.json().catch(() => null);
if (!res.ok || !data || data.ok !== true) return;

if (data.bio?.font_style) fontSelect.value = data.bio.font_style;

// BG load from API
parseBgFromApi(data.bio?.bg_style, data.bio?.bg_custom);

// sync bg UI inputs
if (bgSelect) bgSelect.value = bgCfg.type;

if (bgCfg.type === "color") {
    if (colorInput) colorInput.value = bgCfg.color;
    if (colorHex) colorHex.value = bgCfg.color;
}

if (bgCfg.type === "gradient") {
    if (gradA) gradA.value = bgCfg.a;
    if (gradAHex) gradAHex.value = bgCfg.a;
    if (gradB) gradB.value = bgCfg.b;
    if (gradBHex) gradBHex.value = bgCfg.b;
    if (gradAngle) gradAngle.value = String(bgCfg.angle);
    if (gradAngleVal) gradAngleVal.textContent = `${bgCfg.angle}°`;
}

applyCardStyle();

const blocks = Array.isArray(data.blocks) ? data.blocks : [];

// rebuild blocks UI theo DB
blocksContainer.innerHTML = "";
avatarDataUrl = null;
customTextCount = 0;
customIconCount = 0;

blocks.forEach((b) => {
    const type = b.type;
    const d = b.data || {};

    if (type === "avatar") {
    avatarDataUrl = d.dataUrl || null;
    blocksContainer.appendChild(createAvatarBlock());
    return;
    }

    if (type === "name") {
    nameInput.value = d.text || "";
    blocksContainer.appendChild(createInputBlock("name", "Tên hiển thị", d.text || ""));
    return;
    }

    if (type === "nickname") {
    nickInput.value = d.text || "";
    blocksContainer.appendChild(createInputBlock("nickname", "Nickname / Username", d.text || ""));
    return;
    }

    if (type === "role") {
    roleInput.value = d.text || "";
    blocksContainer.appendChild(createInputBlock("role", "Role / Vai trò", d.text || ""));
    return;
    }

    if (type === "jobs") {
    jobsInput.value = d.text || "";
    blocksContainer.appendChild(createInputBlock("jobs", "Jobs / Lĩnh vực", d.text || ""));
    return;
    }

    if (type === "intro") {
    introInput.value = d.text || "";
    blocksContainer.appendChild(createTextareaBlock("intro", d.text || ""));
    return;
    }

    if (type === "text") {
    blocksContainer.appendChild(createCustomTextBlock(d.text || ""));
    return;
    }

    if (type === "icon") {
    blocksContainer.appendChild(createIconBlock(d.label || "", d.iconClass || "fa-solid fa-star", d.url || ""));
    return;
    }
});

ensureCoreBlocksWithSync();
renderPreview();
}

// SAVE BUTTON (only)
btnSave?.addEventListener("click", async () => {
btnSave.disabled = true;
setSaveStatus("Đang lưu...");

try {
    const ok = await saveLayout();
    if (ok) setSaveStatus("Đã lưu ✓");
    else setSaveStatus("Lưu thất bại", true);
} catch (e) {
    setSaveStatus("Lỗi khi lưu", true);
} finally {
    btnSave.disabled = false;
    setTimeout(() => setSaveStatus(""), 2000);
}
});

function normalizeUrl(url) {
const u = (url || "").trim();
if (!u) return "";
if (/^https?:\/\//i.test(u)) return u;
if (/^mailto:/i.test(u)) return u;
if (/^tel:/i.test(u)) return u;
return "https://" + u;
}

function escapeHtml(s) {
return String(s ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function escapeAttr(s) {
return escapeHtml(s).replaceAll("\n", " ");
}

// Export helper cho viewer dùng chung
window.KN_applyBackground = function (targetBgEl, cfg) {
if (!targetBgEl) return;
const c = cfg || bgCfg;

targetBgEl.style.background = "";

if (!c || !c.type || c.type === "default") return;
if (c.type === "light") { targetBgEl.style.background = "#f8fafc"; return; }
if (c.type === "dark")  { targetBgEl.style.background = "linear-gradient(135deg, #0f172a, #111827)"; return; }
if (c.type === "color") { targetBgEl.style.background = c.color || "#1b1f2a"; return; }
if (c.type === "gradient") {
    const a = c.a || "#00c6ff";
    const b = c.b || "#0072ff";
    const ang = Number.isFinite(c.angle) ? c.angle : 135;
    targetBgEl.style.background = `linear-gradient(${ang}deg, ${a}, ${b})`;
}
};

document.addEventListener("DOMContentLoaded", async () => {
const token = localStorage.getItem(TOKEN_KEY);
if (!token) {
    window.location.href = "/Auth";
    return;
}

// init palettes + binds (safe even if panels absent)
renderColorPalette();
renderGradientPalette();
bindGradSync();

await loadLayout();

// fallback: nếu load fail vẫn có core để thao tác
if (!blocksContainer.querySelector(".block")) {
    ensureCoreBlocksWithSync();
    applyCardStyle();
    renderPreview();
} else {
    // ensure bg panels show đúng type
    syncBgPanels(bgCfg.type);
}
});
