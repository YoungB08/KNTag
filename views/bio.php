<?php

declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>KN BioCard – Sắp xếp giao diện Bio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description"
    content="Trang thiết kế Bio: kéo thả sắp xếp avatar, tên, nickname, role, jobs, giới thiệu, icon; mỗi icon có link; tùy chỉnh background & font.">

  <!-- Font + Icon -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600&family=DM+Sans:wght@400;500;600&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />

  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/bio.css" />

</head>

<body>
  <main>
    <!-- NAV -->
    <div class="kn-nav-wrap">
      <nav class="kn-nav">
        <div class="kn-nav-left">
          <div class="kn-nav-logo">KN</div>
          <div class="kn-nav-brand">
            <span class="kn-nav-brand-title">KN BioCard</span>
            <span class="kn-nav-brand-sub">Thẻ Bio cá nhân – NFC</span>
          </div>
        </div>

        <div class="kn-nav-center">
          <button class="kn-nav-btn active">
            <i class="fa-solid fa-user"></i> Bio layout
          </button>
          <button class="kn-nav-btn">
            <i class="fa-solid fa-id-card-clip"></i> Thẻ NFC
          </button>
          <button class="kn-nav-btn">
            <i class="fa-regular fa-clock"></i> Lịch sử
          </button>
        </div>

        <div class="kn-nav-right">
          <div class="kn-nav-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input placeholder="Tìm kiếm..." />
          </div>
          <div class="kn-avatar-wrap" id="nav-avatar-wrap">
            <div class="kn-avatar" id="nav-avatar">U</div>
            <div class="kn-dropdown" id="nav-dropdown">
              <div class="kn-dropdown-header">Đang đăng nhập: Guest</div>
              <div class="kn-dropdown-item">
                <i class="fa-regular fa-user"></i> Hồ sơ của tôi
              </div>
              <div class="kn-dropdown-item">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
              </div>
            </div>
          </div>
        </div>
      </nav>
    </div>


    <!-- MAIN LAYOUT -->
    <section class="app-wrapper">
      <!-- LEFT -->
      <div class="panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Thành phần Bio</div>
            <div class="small muted">Quản lý các layer hiển thị trên Bio.</div>
          </div>
          <span class="badge-soft">Drag & Drop layout</span>
        </div>

        <div class="drag-alert">
          <i class="fa-solid fa-hand-pointer"></i>
          <span>
            Tip: Kéo thả từng block bên dưới (cả trên mobile) để đổi thứ tự hiển thị.
            Block Icon hỗ trợ đặt link – để trống thì không gắn href.
          </span>

        </div>

        <div class="editor-toolbar">
          <button type="button" id="btn-add-text" class="btn-pill btn-pill-primary">
            <i class="fa-solid fa-plus"></i> Thêm block Text
          </button>
          <button type="button" id="btn-add-icon" class="btn-pill btn-pill-outline">
            <i class="fa-solid fa-icons"></i> Thêm block Icon
          </button>
        </div>

        <div class="editor-title-row">
          <button type="button" id="btn-save-layout" class="btn-pill btn-pill-primary">
            <i class="fa-solid fa-floppy-disk"></i> Lưu
          </button>

          <span id="save-status" class="small muted" style="margin-left:10px;"></span>

        </div>

        <div id="blocks" class="blocks-container">
          <!-- Avatar -->
          <div class="block" data-type="avatar">
            <div class="block-header">
              <div class="block-title">Avatar</div>
              <div class="block-desc">Ảnh đại diện tròn ở trên cùng.</div>
              <span class="badge-type">
                <i class="fa-regular fa-image"></i> Bắt buộc
              </span>
            </div>
            <div class="drag-handle">
              <i class="fa-solid fa-grip-vertical"></i>
            </div>
            <div class="block-controls">
              <label for="avatar-file" class="file-pill">
                <i class="fa-regular fa-image"></i> Upload avatar
              </label>
              <input type="file" id="avatar-file" accept="image/*" />
            </div>
          </div>

          <!-- Name -->
          <div class="block" data-type="name">
            <div class="block-header">
              <div class="block-title">Tên hiển thị</div>
              <div class="block-desc">Tên đầy đủ, nổi bật nhất.</div>
            </div>
            <div class="drag-handle">
              <i class="fa-solid fa-grip-vertical"></i>
            </div>
            <div class="block-controls">
              <input type="text" id="name-input" value="Guest User" />
            </div>
          </div>

          <!-- Nickname -->
          <div class="block" data-type="nickname">
            <div class="block-header">
              <div class="block-title">Nickname / Username</div>
              <div class="block-desc">Tên gọi khác, username mạng xã hội.</div>
            </div>
            <div class="drag-handle">
              <i class="fa-solid fa-grip-vertical"></i>
            </div>
            <div class="block-controls">
              <input type="text" id="nick-input" value="@kn.biocard" />
            </div>
          </div>

          <!-- Role -->
          <div class="block" data-type="role">
            <div class="block-header">
              <div class="block-title">Role / Vai trò chính</div>
              <div class="block-desc">Ví dụ: Bio Designer, Creator, Founder…</div>
            </div>
            <div class="drag-handle">
              <i class="fa-solid fa-grip-vertical"></i>
            </div>
            <div class="block-controls">
              <input type="text" id="role-input" value="Bio & NFC Card Designer" />
            </div>
          </div>

          <!-- Jobs -->
          <div class="block" data-type="jobs">
            <div class="block-header">
              <div class="block-title">Jobs / Lĩnh vực</div>
              <div class="block-desc">Ngăn cách bằng dấu phẩy – hiển thị dạng tag.</div>
            </div>
            <div class="drag-handle">
              <i class="fa-solid fa-grip-vertical"></i>
            </div>
            <div class="block-controls">
              <input type="text" id="jobs-input"
                value="NFC Card, Personal Branding, Web Design" />
            </div>
          </div>

          <!-- Intro -->
          <div class="block" data-type="intro">
            <div class="block-header">
              <div class="block-title">Giới thiệu ngắn</div>
              <div class="block-desc">1–3 câu về bản thân / dịch vụ.</div>
            </div>
            <div class="drag-handle">
              <i class="fa-solid fa-grip-vertical"></i>
            </div>
            <div class="block-controls">
              <textarea id="intro-input">Thiết kế Bio page & thẻ NFC cho creator, freelancer và thương hiệu nhỏ – tối ưu giao diện & trải nghiệm chia sẻ link.</textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Xem trước Bio</div>
            <div class="small muted">
              Thứ tự hiển thị theo đúng thứ tự block bên trái (drag & drop).
            </div>
          </div>
        </div>

        <div class="preview-area">
          <div class="preview-controls">
            <div class="preview-control-group">
              <span class="small muted">Background</span>

              <select id="bg-style-select" class="preview-select">
                <option value="default" selected>Default</option>
                <option value="dark">Dark</option>
                <option value="light">Light</option>
                <option value="color">Color</option>
                <option value="gradient">Gradient</option>
              </select>

              <!-- Advanced panels: chỉ hiện khi Color / Gradient -->
              <div id="bg-panel" style="display:none; margin-top:10px;">
                <!-- Color -->
                <div id="bg-color-panel" style="display:none;">
                  <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input id="bg-color-input" type="color" value="#1b1f2a" />
                    <input id="bg-color-hex" class="preview-input" placeholder="#1b1f2a" value="#1b1f2a" />
                  </div>
                  <div id="bg-color-palette" style="margin-top:10px; display:grid; grid-template-columns: repeat(10, 1fr); gap:8px;"></div>
                </div>

                <!-- Gradient -->
                <div id="bg-gradient-panel" style="display:none;">
                  <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input id="bg-grad-a" type="color" value="#00c6ff" />
                    <input id="bg-grad-a-hex" class="preview-input" placeholder="#00c6ff" value="#00c6ff" />
                    <input id="bg-grad-b" type="color" value="#0072ff" />
                    <input id="bg-grad-b-hex" class="preview-input" placeholder="#0072ff" value="#0072ff" />
                  </div>

                  <div style="margin-top:10px; display:flex; gap:10px; align-items:center;">
                    <input id="bg-grad-angle" type="range" min="0" max="360" value="135" />
                    <span id="bg-grad-angle-val" class="small muted">135°</span>
                  </div>

                  <div id="bg-gradient-palette" style="margin-top:10px; display:grid; grid-template-columns: repeat(5, 1fr); gap:10px;"></div>
                </div>
              </div>
            </div>


            <div class="preview-control-group">
              <span class="small muted">Font Bio</span>
              <select id="font-style-select" class="preview-select">
                <option value="inter" selected>Inter</option>
                <option value="poppins">Poppins</option>
                <option value="dm-sans">DM Sans</option>
              </select>
            </div>
          </div>
          <!-- <div class="preview-control-group">
            <span class="small muted">Custom BG</span>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
              <label class="file-pill">
                <input id="bg-custom-file" class="avatar-file-inline" type="file" accept="image/*" class="preview-select" style="padding:10px;" />
              </label>
              <button type="button" id="btn-bg-clear" class="btn-pill btn-pill-outline" style="padding:10px 12px;">
                Xóa BG
              </button>
            </div>
            <div class="small muted" style="margin-top:6px;">
              Ảnh sẽ được căn giữa + cover, nội dung vẫn giữ nguyên.
            </div>
          </div> -->

          <div class="preview-card" id="bio-preview">
            <div class="preview-bg" id="bio-preview-bg"></div>
            <div class="preview-content" id="bio-preview-content"></div>
          </div>

          <div class="preview-note">
            Gợi ý: dùng block Icon cho các link quan trọng (Facebook, IG, TikTok, GitHub…).
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ICON PICKER MODAL -->
  <div class="icon-modal-overlay" id="icon-modal">
    <div class="icon-modal">
      <div class="icon-modal-header">
        <div>
          <div class="icon-modal-title">Chọn icon cho block</div>
          <div class="small muted">Icon social & icon phổ biến. Gõ để tìm nhanh.</div>
        </div>
        <button class="icon-modal-close" id="icon-modal-close">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="icon-search-row">
        <input
          type="text"
          id="icon-search"
          class="icon-search-input"
          placeholder="Tìm: facebook, github, phone, mail..." />
      </div>

      <div class="icon-grid">
        <div class="icon-grid-inner" id="icon-grid"></div>
      </div>
    </div>
  </div>
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/bio_font.js"></script>

  <script src="<?= KNCMS::baseUrl() ?>/assets/js/bio.js"></script>


</body>
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/knloader.css">
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/knloader.js"></script>

  <script>
    KNLoader.showFor(1000, {
      brand: "KN BioCard",
      msg: "Đang khởi tạo giao diện…"
    });
  </script>
</html>