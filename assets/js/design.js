(()=>{
"use strict";

/**
 * KN Card Designer (single-file)
 * - API base: /api/card/<system>
 * - Load by ?card_id=ID
 *   - if not found (logged-in) => auto create new card and replace URL
 * - If loaded layout missing fields => deep-merge default layout
 * - Access levels:
 *   Level 1: only edit name + avatar (default)
 *   Level 2: allow layers
 *   Level 3: allow hide logo
 */

if (window.__KN_CARD_DESIGN_INIT__) return;
window.__KN_CARD_DESIGN_INIT__ = true;

const API_BASE = "/api/card/";
const TOKEN_KEY = "kn_token";

const $ = (id) => document.getElementById(id);
const qs = (sel, root=document) => root.querySelector(sel);
const qsa = (sel, root=document) => Array.from(root.querySelectorAll(sel));
const on = (el, ev, fn, opt) => el && el.addEventListener(ev, fn, opt);
const setText = (el, v) => { if (el) el.textContent = (v ?? ""); };
const clamp = (n, a, b) => Math.max(a, Math.min(b, n));

function getCardIdFromUrl(){
  const q = new URLSearchParams(location.search);
  const v = Number(q.get("card_id") || 0);
  return Number.isFinite(v) && v > 0 ? v : 0;
}
function setCardIdToUrl(id){
  const url = new URL(location.href);
  url.searchParams.set("card_id", String(id));
  history.replaceState({}, "", url.toString());
}

async function api(system, opts = {}){
  const url = API_BASE + system + (opts.qs ? ("?" + new URLSearchParams(opts.qs).toString()) : "");
  const init = {
    method: opts.method || "GET",
    credentials: "include",
    headers: opts.headers || {}
  };
  if (opts.json){
    init.method = opts.method || "POST";
    init.headers["Content-Type"] = "application/json";
    init.body = JSON.stringify(opts.json);
  }
  if (opts.form){
    init.method = opts.method || "POST";
    init.body = opts.form;
  }
  const res = await fetch(url, init);
  const data = await res.json().catch(() => null);
  if (!data || typeof data !== "object") throw new Error("BAD_JSON");
  if (!data.ok) throw new Error(data.error || "API_ERROR");
  return data;
}

function deepMerge(base, patch){
  if (patch == null) return base;
  if (typeof patch !== "object") return patch;
  if (Array.isArray(patch)) return patch;

  const out = { ...(base || {}) };
  for (const k of Object.keys(patch)){
    const pv = patch[k];
    const bv = out[k];
    if (pv && typeof pv === "object" && !Array.isArray(pv)){
      out[k] = deepMerge((bv && typeof bv === "object" && !Array.isArray(bv)) ? bv : {}, pv);
    } else {
      out[k] = pv;
    }
  }
  return out;
}

function cssUrlToPlain(v){
  if (!v) return null;
  const m = String(v).match(/^url\((?:"|')?(.*?)(?:"|')?\)$/);
  return m ? m[1] : String(v);
}
function plainToCssUrl(v){
  if (!v) return "";
  return `url('${String(v).replace(/'/g, "%27")}')`;
}

function defaultLayoutFactory(uiSeed = {}){
  return {
    version: 1,
    ui: {
      title: uiSeed.title ?? "Guest User",
      subtitle: uiSeed.subtitle ?? "Thẻ card tạo bằng KN",
      titleSize: 100,
      subtitleSize: 100,
      font: "inter",
      darkness: 0,
      qr: { url: uiSeed.qrUrl ?? "https://example.com", size: 120 },
      avatar: uiSeed.avatar ?? null,
      logo: uiSeed.logo ?? null,
      logoVisible: false,
      backShort: uiSeed.backShort ?? "Thẻ KNTech – chạm để xem thông tin.",
      backSize: 100,
      chkSyncBack: true,
      chkEditBack: true
    },
    background: {
      mode: "default",
      css: "linear-gradient(135deg, #3b82f6, #10b981)",
      image: null,
      bgSize: "cover",
      bgPos: "center"
    },
    blocks: {
      qr: { leftPct: 0.78, topPct: 0.62 },
      text: { leftPct: 0.06, topPct: 0.16 },
      logo: { leftPct: 0.78, topPct: 0.10 }
    },
    layers: []
  };
}

document.addEventListener("DOMContentLoaded", () => {
  const card = $("card-front");
  if (!card) return;

  const cardBack = $("card-back");
  const backOverlay = cardBack ? qs(".back-overlay", cardBack) : null;
  const backDarkLayer = $("back-dark-layer");
  const frontDarkLayer = $("front-dark-layer");

  const backShortEl = $("back-short");

  const cardTitle = $("card-title");
  const cardSubtitle = $("card-subtitle");
  const cardIconImg = $("card-icon-img");
  const cardLogo = $("card-logo");
  const bioQrWrapper = $("bio-qr-wrapper");
  const bioTextBlock = $("bio-text-block");

  const inputTitle = $("input-title");
  const inputSubtitle = $("input-subtitle");
  const inputTitleSize = $("input-title-size");
  const labelTitleSize = $("label-title-size");
  const inputSubtitleSize = $("input-subtitle-size");
  const labelSubtitleSize = $("label-subtitle-size");

  const fontPresets = $("font-presets");

  const inputAvatar = $("input-avatar");
  const bgPresets = $("bg-presets");
  const inputBgImage = $("input-bg-image");
  const btnRemoveBg = $("btn-remove-bg");

  const inputDarkness = $("input-darkness");
  const labelDarkness = $("label-darkness");

  const inputLogo = $("input-logo");
  const btnToggleLogo = $("btn-toggle-logo");

  const inputCustomLayer = $("input-custom-layer");
  const layerList = $("layer-list");

  const inputLayerText = $("input-layer-text");
  const inputLayerTextSize = $("input-layer-text-size");
  const labelLayerTextSize = $("label-layer-text-size");
  const layerTextFont = $("layer-text-font");
  const btnAddTextLayer = $("btn-add-text-layer");

  const inputQrUrl = $("input-qr-url");
  const inputQrSize = $("input-qr-size");
  const labelQrSize = $("label-qr-size");

  const chkSyncBack = $("chk-sync-back");
  const chkEditBack = $("chk-edit-back");
  const inputBackShort = $("input-back-short");
  const inputBackSize = $("input-back-size");
  const labelBackSize = $("label-back-size");

  const btnDownloadPng = $("btn-download-png");
  const btnDownloadPdf = $("btn-download-pdf");
  const btnResetLayout = $("btn-reset-layout");
  const btnSaveBio = $("btn-save-bio");

  const userEmailLabel = $("user-email-label");
  const userAvatar = $("user-avatar");
  const logoutBtn = $("logout-btn");

  const avatarNav = $("kn-avatar");
  const avatarWrap = $("kn-avatar-wrap");
  const dropdownEmail = $("kn-dropdown-email");
  const dropdownLogout = $("kn-dropdown-logout");

  const ACCESS_LEVEL = clamp(Number(document.body?.dataset?.accessLevel || window.KN_ACCESS_LEVEL || 3), 1, 3);

  const FONT_MAP = {
    inter: '"Inter", system-ui, -apple-system, BlinkMacSystemFont, sans-serif',
    poppins: '"Poppins", system-ui, -apple-system, BlinkMacSystemFont, sans-serif',
    playfair: '"Playfair Display", "Times New Roman", serif',
  };

  const disable = (el, v) => { if (el) el.disabled = !!v; };
  function applyTierGates(){
    const allowLayers = ACCESS_LEVEL >= 2;
    const allowHideLogo = ACCESS_LEVEL >= 3;

    disable(inputCustomLayer, !allowLayers);
    disable(inputLayerText, !allowLayers);
    disable(inputLayerTextSize, !allowLayers);
    disable(btnAddTextLayer, !allowLayers);
    if (layerTextFont) qsa("button", layerTextFont).forEach(b => b.disabled = !allowLayers);
    if (layerList){
      layerList.style.pointerEvents = allowLayers ? "" : "none";
      layerList.style.opacity = allowLayers ? "" : "0.55";
    }

    disable(btnToggleLogo, !allowHideLogo);
  }
  applyTierGates();

  let currentBgMode = "default";
  let logoVisible = false;
  let layerCounter = 0;
  let currentMaxZ = 30;
  let currentCardId = 0;

  function syncBackBackground(){
    if (!chkSyncBack || !chkSyncBack.checked) return;
    if (!backOverlay) return;

    backOverlay.style.background = card.style.background || "";
    if (card.style.backgroundImage){
      backOverlay.style.backgroundImage = card.style.backgroundImage;
      backOverlay.style.backgroundSize = card.style.backgroundSize;
      backOverlay.style.backgroundPosition = card.style.backgroundPosition;
    } else {
      backOverlay.style.backgroundImage = "";
    }
  }

  function applyBgPreset(name){
    currentBgMode = name || "default";
    card.style.backgroundImage = "";
    if (currentBgMode === "default") card.style.background = "linear-gradient(135deg, #3b82f6, #10b981)";
    else if (currentBgMode === "purple") card.style.background = "linear-gradient(135deg, #6366f1, #0ea5e9)";
    else if (currentBgMode === "orange") card.style.background = "linear-gradient(135deg, #fb923c, #f97316)";
    else if (currentBgMode === "dark") card.style.background = "linear-gradient(135deg, #0f172a, #111827)";
    else card.style.background = "linear-gradient(135deg, #3b82f6, #10b981)";
    syncBackBackground();
  }

  function applyDarkness(){
    const val = Number(inputDarkness?.value || 0) || 0;
    const alpha = val / 100;
    setText(labelDarkness, val + "%");
    const bg = `rgba(0,0,0,${alpha})`;
    if (frontDarkLayer) frontDarkLayer.style.background = bg;
    if (backDarkLayer) backDarkLayer.style.background = bg;
  }

  function renderQR(){
    if (!inputQrUrl || !inputQrSize) return;
    const url = (inputQrUrl.value || "").trim() || "https://example.com";
    const size = Number(inputQrSize.value) || 120;
    setText(labelQrSize, size + "px");
    const container = $("qr-container");
    if (!container || !window.QRCode) return;
    container.innerHTML = "";
    new window.QRCode(container, {
      text: url,
      width: size,
      height: size,
      colorDark: "#000000",
      colorLight: "#ffffff",
      correctLevel: window.QRCode.CorrectLevel.H,
    });
  }

  function isOverlapWithLogo(newX, newY, el){
    if (!logoVisible || !cardLogo) return false;
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
    return (
      elLeft < logoRight + margin &&
      elRight > logoLeft - margin &&
      elTop < logoBottom + margin &&
      elBottom > logoTop - margin
    );
  }

  function makeDraggable(el, opts = {}){
    if (!el) return;
    const avoidLogo = !!opts.avoidLogo;

    let isDown = false;
    let offsetX = 0;
    let offsetY = 0;

    const getPoint = (e) => {
      if (e.touches && e.touches[0]) return e.touches[0];
      if (e.changedTouches && e.changedTouches[0]) return e.changedTouches[0];
      return e;
    };

    const startDrag = (e) => {
      e.preventDefault();
      const p = getPoint(e);
      const rect = el.getBoundingClientRect();
      isDown = true;
      offsetX = p.clientX - rect.left;
      offsetY = p.clientY - rect.top;
      el.style.cursor = "grabbing";
      el.dataset.knDragging = "1";
    };

    const endDrag = () => {
      isDown = false;
      el.style.cursor = "grab";
      el.dataset.knDragging = "0";
    };

    const onMove = (e) => {
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

      if (avoidLogo && isOverlapWithLogo(x, y, el)) return;

      el.style.position = "absolute";
      el.style.left = x + "px";
      el.style.top = y + "px";
    };

    on(el, "mousedown", startDrag);
    on(document, "mousemove", onMove);
    on(document, "mouseup", endDrag);

    on(el, "touchstart", startDrag, { passive: false });
    on(document, "touchmove", onMove, { passive: false });
    on(document, "touchend", endDrag);
  }

  function setActiveLayerItem(item){
    if (!layerList) return;
    qsa(".layer-item", layerList).forEach((el) => el.classList.remove("active"));
    if (item) item.classList.add("active");
  }

  function createLayerListItem({ id, type, previewUrl, name, fontLabel, sizePx }){
    if (!layerList) return null;

    const item = document.createElement("div");
    item.className = "layer-item";
    item.dataset.layerId = String(id);
    item.dataset.layerType = type;

    const mainRow = document.createElement("div");
    mainRow.className = "layer-main-row";

    if (type === "image"){
      mainRow.innerHTML = `
        <div class="layer-thumb" style="background-image:url('${previewUrl || ""}')"></div>
        <div class="layer-name">Img ${id} – ${name || "image"}</div>
        <div class="layer-actions">
          <button type="button" data-eye="1" title="Ẩn/hiện layer"><i class="fa-regular fa-eye"></i></button>
          <button type="button" data-delete="1" title="Xóa layer"><i class="fa-regular fa-trash-can"></i></button>
        </div>`;
    } else {
      mainRow.innerHTML = `
        <div class="layer-thumb"><span>T</span></div>
        <div class="layer-name">Text ${id} – ${fontLabel || "Sans"}</div>
        <div class="layer-actions">
          <button type="button" data-eye="1" title="Ẩn/hiện layer"><i class="fa-regular fa-eye"></i></button>
          <button type="button" data-delete="1" title="Xóa layer"><i class="fa-regular fa-trash-can"></i></button>
        </div>`;
    }
    item.appendChild(mainRow);

    const sizeRow = document.createElement("div");
    sizeRow.className = "layer-size-row";
    if (type === "image"){
      sizeRow.innerHTML = `
        <input type="range" min="40" max="260" value="${sizePx}" data-size-slider="${id}">
        <span data-size-label="${id}">${sizePx}px</span>`;
    } else {
      sizeRow.innerHTML = `
        <input type="range" min="10" max="40" value="${sizePx}" data-size-slider="${id}">
        <span data-size-label="${id}">${sizePx}px</span>`;
    }
    item.appendChild(sizeRow);

    layerList.appendChild(item);
    setActiveLayerItem(item);
    return item;
  }

  function createImageLayer(src, name, opts = {}){
    if (ACCESS_LEVEL < 2) return null;
    const id = ++layerCounter;

    const img = document.createElement("img");
    img.src = src;
    img.className = "card-layer";
    img.dataset.layerId = String(id);
    img.dataset.layerType = "image";
    img.dataset.src = src;
    img.style.zIndex = String(opts.z ?? (++currentMaxZ));
    img.dataset.size = String(opts.size ?? 120);
    if (opts.size) img.style.width = opts.size + "px";
    if (opts.hidden) img.style.display = "none";

    card.appendChild(img);
    makeDraggable(img, { avoidLogo: true });

    createLayerListItem({
      id,
      type: "image",
      previewUrl: src,
      name,
      sizePx: Number(opts.size ?? 120)
    });

    return img;
  }

  function createTextLayer(content, fontKey, sizePx, opts = {}){
    if (ACCESS_LEVEL < 2) return null;
    if (!String(content || "").trim()) return null;

    const id = ++layerCounter;
    const family = FONT_MAP[fontKey] || FONT_MAP.inter;

    const el = document.createElement("div");
    el.className = "card-layer-text";
    el.dataset.layerId = String(id);
    el.dataset.layerType = "text";
    el.textContent = content;
    el.style.fontFamily = family;
    el.style.fontSize = sizePx + "px";
    el.dataset.size = String(sizePx);
    el.style.zIndex = String(opts.z ?? (++currentMaxZ));
    if (opts.hidden) el.style.display = "none";

    card.appendChild(el);
    makeDraggable(el, { avoidLogo: true });

    const fontLabel = fontKey === "poppins" ? "Pop" : fontKey === "playfair" ? "Serif" : "Sans";
    createLayerListItem({ id, type: "text", fontLabel, sizePx });

    return el;
  }

  function clearLayersUI(){
    qsa(".card-layer, .card-layer-text", card).forEach((el) => el.remove());
    if (layerList) layerList.innerHTML = "";
    layerCounter = 0;
    currentMaxZ = 30;
  }

  function getActiveFontKey(){
    const btn = qs(".pill-option.active", fontPresets);
    return btn?.dataset?.font || "inter";
  }

  function readElementPosPct(el){
    const cardRect = card.getBoundingClientRect();
    const leftPx = parseFloat(el.style.left) || 0;
    const topPx = parseFloat(el.style.top) || 0;
    return {
      leftPct: cardRect.width ? Number((leftPx / cardRect.width).toFixed(6)) : 0,
      topPct: cardRect.height ? Number((topPx / cardRect.height).toFixed(6)) : 0
    };
  }

  function applyPosFromPct(el, pos){
    if (!el) return;
    const cardRect = card.getBoundingClientRect();
    const x = (pos?.leftPct || 0) * cardRect.width;
    const y = (pos?.topPct || 0) * cardRect.height;
    el.style.position = "absolute";
    el.style.left = Math.round(x) + "px";
    el.style.top = Math.round(y) + "px";
  }

  function collectLayout(){
    const layers = [];
    qsa(".card-layer, .card-layer-text", card).forEach((el) => {
      const isText = el.classList.contains("card-layer-text");
      const pos = readElementPosPct(el);
      const z = Number(el.style.zIndex) || 0;
      const hidden = el.style.display === "none";

      if (!isText){
        layers.push({
          type: "image",
          src: el.getAttribute("src") || el.dataset.src || null,
          ...pos,
          width: el.style.width || (el.dataset.size ? (el.dataset.size + "px") : null),
          z,
          hidden
        });
      } else {
        layers.push({
          type: "text",
          text: el.textContent || "",
          ...pos,
          fontSize: el.style.fontSize || (el.dataset.size ? (el.dataset.size + "px") : null),
          fontFamily: el.style.fontFamily || null,
          z,
          hidden
        });
      }
    });

    const bgImage = card.style.backgroundImage || "";
    return {
      version: 1,
      ui: {
        title: inputTitle?.value || "",
        subtitle: inputSubtitle?.value || "",
        titleSize: Number(inputTitleSize?.value || 100),
        subtitleSize: Number(inputSubtitleSize?.value || 100),
        font: getActiveFontKey(),
        darkness: Number(inputDarkness?.value || 0),
        qr: { url: inputQrUrl?.value || "", size: Number(inputQrSize?.value || 120) },
        avatar: cardIconImg?.src || null,
        logo: cssUrlToPlain(cardLogo?.style?.backgroundImage || ""),
        logoVisible: !!logoVisible,
        backShort: inputBackShort?.value || "",
        backSize: Number(inputBackSize?.value || 100),
        chkSyncBack: !!chkSyncBack?.checked,
        chkEditBack: !!chkEditBack?.checked,
      },
      background: {
        mode: currentBgMode,
        css: card.style.background || "",
        image: cssUrlToPlain(bgImage) || null,
        bgSize: card.style.backgroundSize || "",
        bgPos: card.style.backgroundPosition || ""
      },
      blocks: {
        qr: bioQrWrapper ? readElementPosPct(bioQrWrapper) : { leftPct: 0, topPct: 0 },
        text: bioTextBlock ? readElementPosPct(bioTextBlock) : { leftPct: 0, topPct: 0 },
        logo: cardLogo ? readElementPosPct(cardLogo) : { leftPct: 0, topPct: 0 },
      },
      layers
    };
  }

  function applyLayout(rawLayout){
    const seed = defaultLayoutFactory({
      title: inputTitle?.value || "Guest User",
      subtitle: inputSubtitle?.value || "Thẻ card tạo bằng KN",
      qrUrl: inputQrUrl?.value || "https://example.com",
      avatar: inputAvatar?.dataset?.default || null
    });

    const layout = deepMerge(seed, rawLayout || {});

    if (inputTitle) inputTitle.value = layout.ui.title ?? inputTitle.value;
    setText(cardTitle, inputTitle?.value || "Guest User");

    if (inputSubtitle) inputSubtitle.value = layout.ui.subtitle ?? inputSubtitle.value;
    setText(cardSubtitle, inputSubtitle?.value || "");

    if (inputTitleSize) inputTitleSize.value = String(layout.ui.titleSize ?? 100);
    if (inputSubtitleSize) inputSubtitleSize.value = String(layout.ui.subtitleSize ?? 100);
    if (inputTitleSize) inputTitleSize.dispatchEvent(new Event("input"));
    if (inputSubtitleSize) inputSubtitleSize.dispatchEvent(new Event("input"));

    const fontKey = layout.ui.font || "inter";
    if (fontPresets) qsa(".pill-option", fontPresets).forEach((b) => b.classList.toggle("active", (b.dataset.font === fontKey)));
    const family = FONT_MAP[fontKey] || FONT_MAP.inter;
    if (cardTitle) cardTitle.style.fontFamily = family;
    if (cardSubtitle) cardSubtitle.style.fontFamily = family;
    if (backShortEl) backShortEl.style.fontFamily = family;

    currentBgMode = layout.background.mode || "default";
    if (layout.background.image){
      card.style.backgroundImage = plainToCssUrl(layout.background.image);
      card.style.backgroundSize = layout.background.bgSize || "cover";
      card.style.backgroundPosition = layout.background.bgPos || "center";
      card.style.background = layout.background.css || card.style.background;
    } else {
      card.style.backgroundImage = "";
      card.style.background = layout.background.css || seed.background.css;
    }
    syncBackBackground();

    if (inputDarkness) inputDarkness.value = String(layout.ui.darkness ?? 0);
    applyDarkness();

    if (cardIconImg){
      const fallbackAvt = inputAvatar?.dataset?.default || cardIconImg.src;
      cardIconImg.src = layout.ui.avatar || fallbackAvt;
    }

    if (cardLogo){
      if (layout.ui.logo) cardLogo.style.backgroundImage = plainToCssUrl(layout.ui.logo);
      logoVisible = !!layout.ui.logoVisible;
      cardLogo.classList.toggle("show", logoVisible);
    }

    if (inputBackShort) inputBackShort.value = String(layout.ui.backShort ?? seed.ui.backShort);
    setText(backShortEl, inputBackShort?.value || "");
    if (inputBackSize) inputBackSize.value = String(layout.ui.backSize ?? 100);
    if (inputBackSize) inputBackSize.dispatchEvent(new Event("input"));
    if (chkSyncBack) chkSyncBack.checked = !!layout.ui.chkSyncBack;
    if (chkEditBack) chkEditBack.checked = !!layout.ui.chkEditBack;

    if (inputQrUrl && layout.ui.qr?.url != null) inputQrUrl.value = String(layout.ui.qr.url);
    if (inputQrSize && layout.ui.qr?.size != null) inputQrSize.value = String(layout.ui.qr.size);
    renderQR();

    requestAnimationFrame(() => {
      applyPosFromPct(bioQrWrapper, layout.blocks.qr);
      applyPosFromPct(bioTextBlock, layout.blocks.text);
      applyPosFromPct(cardLogo, layout.blocks.logo);
    });

    clearLayersUI();
    const layers = Array.isArray(layout.layers) ? layout.layers : [];
    layers.sort((a, b) => (Number(a.z || 0) - Number(b.z || 0)));
    layers.forEach((ly) => {
      if (ly.type === "image" && ly.src){
        const size = parseInt(String(ly.width || "120").replace("px",""), 10) || 120;
        const el = createImageLayer(ly.src, "loaded", { z: Number(ly.z || 0), size, hidden: !!ly.hidden });
        requestAnimationFrame(() => applyPosFromPct(el, ly));
      } else if (ly.type === "text"){
        const size = parseInt(String(ly.fontSize || "18").replace("px",""), 10) || 18;
        const fontFamily = ly.fontFamily || FONT_MAP.inter;
        let fontKey2 = "inter";
        for (const k of Object.keys(FONT_MAP)) if (FONT_MAP[k] === fontFamily) fontKey2 = k;
        const el = createTextLayer(ly.text || "", fontKey2, size, { z: Number(ly.z || 0), hidden: !!ly.hidden });
        if (el && ly.fontFamily) el.style.fontFamily = ly.fontFamily;
        requestAnimationFrame(() => applyPosFromPct(el, ly));
      }
    });
    const zMax = layers.reduce((m, x) => Math.max(m, Number(x.z || 0)), 30);
    currentMaxZ = Math.max(currentMaxZ, zMax);
  }

  async function uploadIfDataUrl(src){
    if (!src || typeof src !== "string") return src;
    if (!src.startsWith("data:image/")) return src;
    const blob = await (await fetch(src)).blob();
    const fd = new FormData();
    fd.append("file", blob, "layer.png");
    const r = await api("upload_image", { form: fd });
    return r.upload?.url || src;
  }

  async function saveCard({ title, status } = {}){
    const layout = collectLayout();

    const layers = Array.isArray(layout.layers) ? layout.layers : [];
    const imgIdx = layers.map((ly, idx) => ({ ly, idx }))
      .filter(x => x.ly.type === "image" && typeof x.ly.src === "string" && x.ly.src.startsWith("data:image/"));

    if (imgIdx.length){
      const replaced = await Promise.all(imgIdx.map(async (x) => {
        try { return { idx: x.idx, url: await uploadIfDataUrl(x.ly.src) }; }
        catch { return { idx: x.idx, url: x.ly.src }; }
      }));
      replaced.forEach(({ idx, url }) => { layout.layers[idx].src = url; });
    }

    const payload = {
      id: currentCardId || 0,
      title: title ?? (inputTitle?.value || "Untitled Card"),
      status: status ?? "draft",
      layout
    };
    const res = await api("save", { json: payload });
    currentCardId = Number(res.id || currentCardId || 0);
    if (currentCardId) setCardIdToUrl(currentCardId);
    return res;
  }

  async function loadCard(id){
    const res = await api("get", { qs: { id: String(id) } });
    currentCardId = Number(res.card?.id || id || 0);
    applyLayout(res.card?.layout || null);
    return res;
  }

  async function createNewCard(){
    const seed = defaultLayoutFactory({
      title: inputTitle?.value || "Guest User",
      subtitle: inputSubtitle?.value || "Thẻ card tạo bằng KN",
      qrUrl: inputQrUrl?.value || "https://example.com",
      avatar: inputAvatar?.dataset?.default || null
    });
    const res = await api("save", { json: { id: 0, title: "New Card", status: "draft", layout: seed } });
    currentCardId = Number(res.id || res.card?.id || 0);
    if (currentCardId) setCardIdToUrl(currentCardId);
    applyLayout(seed);
    return res;
  }

  async function loadOrCreateFromUrl(){
    const id = getCardIdFromUrl();
    if (!id){
      await createNewCard();
      return;
    }
    try {
      await loadCard(id);
    } catch {
      await createNewCard();
    }
  }

  window.KNCard = {
    save: saveCard,
    load: loadCard,
    create: createNewCard,
    loadOrCreateFromUrl,
    getCardId: () => currentCardId
  };

  on(chkSyncBack, "change", syncBackBackground);
  on(chkEditBack, "change", () => {
    const editable = !!chkEditBack?.checked;
    if (inputBackShort) inputBackShort.disabled = !editable;
  });
  on(inputBackShort, "input", () => setText(backShortEl, inputBackShort.value || ""));
  on(inputBackSize, "input", () => {
    const val = Number(inputBackSize.value || 100);
    setText(labelBackSize, val + "%");
    const base = 11;
    if (backShortEl) backShortEl.style.fontSize = (base * val) / 100 + "px";
  });

  on(inputTitle, "input", () => setText(cardTitle, inputTitle.value || "Guest User"));
  on(inputSubtitle, "input", () => setText(cardSubtitle, inputSubtitle.value || "Thẻ card tạo bằng KN"));
  on(inputTitleSize, "input", () => {
    const val = Number(inputTitleSize.value || 100);
    setText(labelTitleSize, val + "%");
    const base = 22;
    if (cardTitle) cardTitle.style.fontSize = (base * val) / 100 + "px";
  });
  on(inputSubtitleSize, "input", () => {
    const val = Number(inputSubtitleSize.value || 100);
    setText(labelSubtitleSize, val + "%");
    const base = 14;
    if (cardSubtitle) cardSubtitle.style.fontSize = (base * val) / 100 + "px";
  });

  on(fontPresets, "click", (e) => {
    const btn = e.target.closest(".pill-option");
    if (!btn) return;
    qsa(".pill-option", fontPresets).forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
    const key = btn.dataset.font;
    const family = FONT_MAP[key] || FONT_MAP.inter;
    if (cardTitle) cardTitle.style.fontFamily = family;
    if (cardSubtitle) cardSubtitle.style.fontFamily = family;
    if (backShortEl) backShortEl.style.fontFamily = family;
  });

  if (inputAvatar && inputAvatar.dataset.default && cardIconImg) cardIconImg.src = inputAvatar.dataset.default;
  on(inputAvatar, "change", (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { if (cardIconImg) cardIconImg.src = ev.target.result; };
    reader.readAsDataURL(file);
  });

  on(bgPresets, "click", (e) => {
    const btn = e.target.closest(".pill-option");
    if (!btn) return;
    qsa(".pill-option", bgPresets).forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
    applyBgPreset(btn.dataset.bg);
  });
  on(inputBgImage, "change", (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      card.style.backgroundImage = `url('${ev.target.result}')`;
      card.style.backgroundSize = "cover";
      card.style.backgroundPosition = "center";
      syncBackBackground();
    };
    reader.readAsDataURL(file);
  });
  on(btnRemoveBg, "click", () => {
    if (inputBgImage) inputBgImage.value = "";
    applyBgPreset(currentBgMode);
  });

  on(inputDarkness, "input", applyDarkness);

  on(inputLogo, "change", (e) => {
    const file = e.target.files?.[0];
    if (!file || !cardLogo) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      cardLogo.style.backgroundImage = `url('${ev.target.result}')`;
      cardLogo.classList.add("show");
      logoVisible = true;
    };
    reader.readAsDataURL(file);
  });
  on(btnToggleLogo, "click", () => {
    if (ACCESS_LEVEL < 3 || !cardLogo) return;
    logoVisible = !logoVisible;
    cardLogo.classList.toggle("show", logoVisible);
  });

  on(inputQrUrl, "input", renderQR);
  on(inputQrSize, "input", renderQR);

  on(inputCustomLayer, "change", (e) => {
    if (ACCESS_LEVEL < 2) return;
    const files = Array.from(e.target.files || []);
    files.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (ev) => createImageLayer(ev.target.result, file.name);
      reader.readAsDataURL(file);
    });
    inputCustomLayer.value = "";
  });
  on(layerTextFont, "click", (e) => {
    if (ACCESS_LEVEL < 2) return;
    const btn = e.target.closest(".pill-option");
    if (!btn) return;
    qsa(".pill-option", layerTextFont).forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  });
  on(inputLayerTextSize, "input", () => {
    const val = Number(inputLayerTextSize?.value || 18);
    setText(labelLayerTextSize, val + "px");
  });
  on(btnAddTextLayer, "click", () => {
    if (ACCESS_LEVEL < 2) return;
    const content = inputLayerText?.value || "";
    const size = Number(inputLayerTextSize?.value || 18);
    const activeBtn = qs(".pill-option.active", layerTextFont) || qs('[data-font="inter"]', layerTextFont);
    const fontKey = activeBtn?.dataset?.font || "inter";
    createTextLayer(content, fontKey, size);
  });

  on(layerList, "click", (e) => {
    if (ACCESS_LEVEL < 2) return;
    const item = e.target.closest(".layer-item");
    if (!item) return;

    const id = item.dataset.layerId;
    const type = item.dataset.layerType;
    const selector = type === "text"
      ? `.card-layer-text[data-layer-id="${id}"]`
      : `.card-layer[data-layer-id="${id}"]`;
    const layerEl = qs(selector, card);
    if (!layerEl){ item.remove(); return; }

    if (e.target.closest("button[data-delete]")){
      layerEl.remove();
      item.remove();
      return;
    }

    if (e.target.closest("button[data-eye]")){
      const hidden = layerEl.style.display === "none";
      layerEl.style.display = hidden ? "block" : "none";
      const icon = e.target.closest("button").querySelector("i");
      if (icon){
        icon.classList.toggle("fa-eye", hidden);
        icon.classList.toggle("fa-eye-slash", !hidden);
      }
      return;
    }

    currentMaxZ += 1;
    layerEl.style.zIndex = String(currentMaxZ);
    setActiveLayerItem(item);
  });

  on(layerList, "input", (e) => {
    if (ACCESS_LEVEL < 2) return;
    const slider = e.target;
    const id = slider.getAttribute("data-size-slider");
    if (!id) return;

    const val = Number(slider.value);
    const label = qs(`span[data-size-label="${id}"]`, layerList);

    const imgEl = qs(`.card-layer[data-layer-id="${id}"]`, card);
    const textEl = qs(`.card-layer-text[data-layer-id="${id}"]`, card);

    if (imgEl){
      imgEl.style.width = val + "px";
      imgEl.dataset.size = String(val);
    }
    if (textEl){
      textEl.style.fontSize = val + "px";
      textEl.dataset.size = String(val);
    }
    if (label) label.textContent = val + "px";
  });

  makeDraggable(bioQrWrapper, { avoidLogo: true });
  makeDraggable(cardLogo, { avoidLogo: false });
  makeDraggable(bioTextBlock, { avoidLogo: true });

  on(btnResetLayout, "click", () => {
    [bioQrWrapper, cardLogo, bioTextBlock].forEach((el) => {
      if (!el) return;
      el.style.position = "";
      el.style.left = "";
      el.style.top = "";
    });

    if (inputTitleSize) inputTitleSize.value = 100;
    setText(labelTitleSize, "100%");
    if (cardTitle) cardTitle.style.fontSize = "22px";

    if (inputSubtitleSize) inputSubtitleSize.value = 100;
    setText(labelSubtitleSize, "100%");
    if (cardSubtitle) cardSubtitle.style.fontSize = "14px";

    if (inputBackSize) inputBackSize.value = 100;
    setText(labelBackSize, "100%");
    if (backShortEl) backShortEl.style.fontSize = "11px";

    if (inputQrSize) inputQrSize.value = 120;
    renderQR();

    if (fontPresets){
      qsa(".pill-option", fontPresets).forEach((b) => b.classList.remove("active"));
      const b = qs('[data-font="inter"]', fontPresets);
      if (b) b.classList.add("active");
    }
    const family = FONT_MAP.inter;
    if (cardTitle) cardTitle.style.fontFamily = family;
    if (cardSubtitle) cardSubtitle.style.fontFamily = family;
    if (backShortEl) backShortEl.style.fontFamily = family;

    if (inputDarkness) inputDarkness.value = 0;
    applyDarkness();

    clearLayersUI();

    if (inputLayerText) inputLayerText.value = "";
    if (inputLayerTextSize) inputLayerTextSize.value = 18;
    setText(labelLayerTextSize, "18px");
  });

  on(btnDownloadPng, "click", () => {
    if (!window.html2canvas) return;
    window.html2canvas(card, { scale: 3 }).then((canvas) => {
      const link = document.createElement("a");
      link.download = "kn-card-front.png";
      link.href = canvas.toDataURL("image/png");
      link.click();
    });
  });

  on(btnDownloadPdf, "click", () => {
    if (!window.html2canvas || !window.jspdf) return;
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF("l", "mm", "credit-card");
    window.html2canvas(card, { scale: 3 }).then((canvasFront) => {
      const imgFront = canvasFront.toDataURL("image/png");
      const w = pdf.internal.pageSize.getWidth();
      const h = pdf.internal.pageSize.getHeight();
      pdf.addImage(imgFront, "PNG", 0, 0, w, h);

      pdf.addPage();
      window.html2canvas(cardBack, { scale: 3 }).then((canvasBack) => {
        const imgBack = canvasBack.toDataURL("image/png");
        pdf.addImage(imgBack, "PNG", 0, 0, w, h);
        pdf.save("kn-card-2-mat.pdf");
      });
    });
  });

  on(btnSaveBio, "click", async (e) => {
    e.preventDefault();
    if (btnSaveBio) btnSaveBio.disabled = true;
    try {
      await saveCard({ status: "draft" });
    } catch (err) {
      console.error(err);
      alert("Lưu thất bại: " + String(err?.message || err));
    } finally {
      if (btnSaveBio) btnSaveBio.disabled = false;
    }
  });

  on(document, "keydown", async (e) => {
    if ((e.ctrlKey || e.metaKey) && (e.key || "").toLowerCase() === "s"){
      e.preventDefault();
      try { await saveCard({ status: "draft" }); }
      catch (err) { console.error(err); }
    }
  });

  async function hydrateUser(){
    try {
      const me = await api("me");
      const email = me.user?.email || me.user?.name || "Guest";
      setText(userEmailLabel, email);
      setText(userAvatar, String(email || "U").charAt(0).toUpperCase());
      setText(avatarNav, String(email || "U").charAt(0).toUpperCase());
      setText(dropdownEmail, "Đang đăng nhập: " + email);
    } catch {
      setText(userEmailLabel, "");
      setText(userAvatar, "U");
      setText(avatarNav, "U");
      setText(dropdownEmail, "Đang đăng nhập: Guest");
    }
  }

  on(logoutBtn, "click", () => {
    try { localStorage.removeItem(TOKEN_KEY); } catch {}
    location.href = "/Auth";
  });
  on(dropdownLogout, "click", () => {
    try { localStorage.removeItem(TOKEN_KEY); } catch {}
    location.href = "/Auth";
  });
  on(avatarNav, "click", () => { if (avatarWrap) avatarWrap.classList.toggle("open"); });
  on(document, "click", (e) => {
    if (!avatarWrap) return;
    if (!avatarWrap.contains(e.target)) avatarWrap.classList.remove("open");
  });

  const path = location.pathname.toLowerCase();
  qsa(".kn-nav-btn").forEach((btn) => {
    const target = btn.dataset.go;
    if (target === "design" && path.includes("design")) btn.classList.add("active");
    if (target === "auth" && path.includes("auth")) btn.classList.add("active");
    on(btn, "click", () => {
      if (target === "auth") location.href = "/Auth";
      if (target === "design") location.href = "Home";
    });
  });

  applyBgPreset("default");
  applyDarkness();
  renderQR();
  syncBackBackground();
  hydrateUser();

  loadOrCreateFromUrl().catch((e) => {
    console.error("LOAD_OR_CREATE_FAILED:", e);
    applyLayout(defaultLayoutFactory({
      title: inputTitle?.value || "Guest User",
      subtitle: inputSubtitle?.value || "Thẻ card tạo bằng KN",
      qrUrl: inputQrUrl?.value || "https://example.com",
      avatar: inputAvatar?.dataset?.default || null
    }));
  });
});
})();