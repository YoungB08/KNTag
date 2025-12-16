<?php

declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;

if (!KNCMS::checkLogin()) {
    echo "Đăng nhập để tạo thẻ Bio cá nhân.";
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>KN BioCard – Thiết kế thẻ Bio NFC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description"
    content="Tùy chỉnh thẻ Bio cá nhân: tên, mô tả, background, logo, QR, layer ảnh & text; tải PNG hoặc PDF. Demo chạy local, không cần backend." />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap"
    rel="stylesheet" />

  <!-- Font Awesome -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />

  <!-- CSS riêng -->
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/desgin.css" />

  <!-- QR + html2canvas + jsPDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
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
          <button class="kn-nav-btn" data-go="auth">
            <i class="fa-solid fa-house"></i> Trang chủ
          </button>
          <button class="kn-nav-btn" data-go="design">
            <i class="fa-solid fa-id-card-clip"></i> Tạo thẻ
          </button>
          <button class="kn-nav-btn">
            <i class="fa-regular fa-clock"></i> Lịch sử
          </button>
          <button class="kn-nav-btn">
            <i class="fa-regular fa-life-ring"></i> Hỗ trợ
          </button>
        </div>

        <div class="kn-nav-right">
          <div class="kn-nav-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input placeholder="Tìm kiếm..." />
          </div>
          <div class="kn-avatar-wrap" id="kn-avatar-wrap">
            <div class="kn-avatar" id="kn-avatar">U</div>
            <div class="kn-dropdown" id="kn-dropdown">
              <div class="kn-dropdown-header" id="kn-dropdown-email">
                Đang đăng nhập: Guest
              </div>
              <div class="kn-dropdown-item">
                <i class="fa-regular fa-user"></i>
                Hồ sơ của tôi
              </div>
              <div class="kn-dropdown-item" id="kn-dropdown-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Đăng xuất
              </div>
            </div>
          </div>
        </div>
      </nav>
    </div>

    <!-- MAIN LAYOUT -->
    <section class="app-wrapper">
      <!-- LEFT: PREVIEW -->
      <div class="panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Xem trước thẻ</div>
            <div class="small muted">
              Kéo QR / logo / text / layer trực tiếp trên thẻ để chỉnh vị trí.
            </div>
          </div>
          <span class="badge-demo">Demo local – không cần backend</span>
        </div>

        <div class="preview-area">
          <div class="card-wrapper">

            <!-- FRONT -->
            <div id="card-front" class="bio-card">
              <!-- lớp tối background -->
              <div class="bg-dark-layer" id="front-dark-layer"></div>

              <!-- logo góc trên phải -->
              <div class="bio-logo" id="card-logo"></div>

              <div class="bio-card-inner">
                <!-- block text chính (draggable) -->
                <div class="bio-main" id="bio-text-block">
                  <div class="bio-icon" id="card-icon">
                    <img id="card-icon-img"
                      src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg/1f464.svg"
                      alt="Avatar" />
                  </div>
                  <div>
                    <div class="bio-title" id="card-title">Guest User</div>
                    <div class="bio-subtitle" id="card-subtitle">
                      Thẻ bio cá nhân tạo bằng KN BioCard
                    </div>
                  </div>
                </div>

                <!-- QR -->
                <div class="bio-qr" id="bio-qr-wrapper">
                  <div id="qr-container"></div>
                </div>
              </div>
            </div>

            <!-- BACK -->
            <div class="back-card" id="card-back">
              <div class="back-overlay"></div>
              <div class="back-dark-layer" id="back-dark-layer"></div>

              <div class="back-content">
                <div class="back-logo-wrap">
                  <!-- logo KNTech cố định -->
                  <img
                    id="back-logo-img"
                    class="back-logo-img"
                    src="https://dummyimage.com/120x120/2563eb/ffffff&text=KN"
                    alt="KNTech Logo" />
                </div>
                <div class="back-short" id="back-short">
                  Thẻ Bio cá nhân KNTech – chạm để xem thông tin.
                </div>
                <div class="back-copy">
                  © KNTech. All rights reserved.
                </div>
              </div>
            </div>

          </div>

          <div class="preview-actions">
            <button class="btn btn-primary" id="btn-download-png">
              <i class="fa-solid fa-download"></i> Tải PNG (mặt trước)
            </button>
            <button class="btn" id="btn-download-pdf">
              <i class="fa-regular fa-file-pdf"></i> Tải PDF (2 mặt)
            </button>
            <button class="btn btn-reset" id="btn-reset-layout">
              <i class="fa-solid fa-rotate-left"></i> Reset layout
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT: FORM -->
      <div class="panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Tùy chỉnh thẻ Bio</div>
            <div class="small muted">Thông tin, background, layer ảnh & text…</div>
          </div>
          <div class="user-pill">
            <div class="user-avatar" id="user-avatar">U</div>
            <span id="user-email-label" class="small"></span>
            <button class="logout-btn" id="logout-btn" title="Đăng xuất">Thoát</button>
          </div>
        </div>

        <div class="form-grid">
          <!-- TEXT CHÍNH -->
          <div class="field">
            <div class="field-label">Tên hiển thị</div>
            <input
              type="text"
              id="input-title"
              class="field-input"
              value="Guest User" />
          </div>

          <div class="field">
            <div class="field-label">Mô tả ngắn</div>
            <textarea
              id="input-subtitle"
              class="field-input">Thẻ bio cá nhân tạo bằng KN BioCard</textarea>
          </div>

          <div class="field">
            <div class="slider-row">
              <span class="field-label">Cỡ chữ tiêu đề</span>
              <span id="label-title-size">100%</span>
            </div>
            <input
              type="range"
              min="80"
              max="140"
              value="100"
              id="input-title-size" />
          </div>

          <div class="field">
            <div class="slider-row">
              <span class="field-label">Cỡ chữ mô tả</span>
              <span id="label-subtitle-size">100%</span>
            </div>
            <input
              type="range"
              min="80"
              max="140"
              value="100"
              id="input-subtitle-size" />
          </div>

          <!-- Font -->
          <div class="field">
            <div class="field-label">Font chữ chính (tiêu đề / mô tả / mặt sau)</div>
            <div class="pill-options" id="font-presets">
              <button class="pill-option active" data-font="inter">Hiện đại</button>
              <button class="pill-option" data-font="poppins">Trẻ trung</button>
              <button class="pill-option" data-font="playfair">Sang trọng</button>
            </div>
          </div>

          <div class="line"></div>

          <!-- Avatar -->
          <div class="field">
            <div class="field-label">Ảnh avatar / icon tròn</div>
            <input type="file" id="input-avatar" accept="image/*" class="field-input-file" />
            <p class="field-note">
              Hình tròn ở góc trên trái (mặt trước).
            </p>
          </div>

          <!-- Background -->
          <div class="field">
            <div class="field-label">Background thẻ (gradient nhanh)</div>
            <div class="pill-options" id="bg-presets">
              <button class="pill-option active" data-bg="default">
                Xanh dương ➝ Xanh mint
              </button>
              <button class="pill-option" data-bg="purple">
                Tím ➝ Xanh biển
              </button>
              <button class="pill-option" data-bg="orange">
                Cam ➝ Hồng
              </button>
              <button class="pill-option" data-bg="dark">
                Xanh đậm ➝ Đen mềm
              </button>
            </div>
            <p class="field-note">
              Hoặc upload ảnh để dùng làm background riêng.
            </p>
          </div>

          <div class="field">
            <div class="field-label">Upload ảnh nền</div>
            <input type="file" id="input-bg-image" accept="image/*" class="field-input-file" />
            <button class="btn btn-reset small" id="btn-remove-bg">
              Xóa ảnh nền (giữ gradient)
            </button>
          </div>

          <!-- Độ tối background -->
          <div class="field">
            <div class="slider-row">
              <span class="field-label">Độ tối background</span>
              <span id="label-darkness">0%</span>
            </div>
            <input
              type="range"
              min="0"
              max="60"
              value="0"
              id="input-darkness" />
            <p class="field-note">
              Dùng khi background quá sáng – tăng độ tối để họa tiết / chữ nổi hơn (cả 2 mặt).
            </p>
          </div>

          <!-- Logo -->
          <div class="field">
            <div class="field-label">Logo góc trên phải (mặt trước)</div>
            <input type="file" id="input-logo" accept="image/*" class="field-input-file" />
            <div class="field-note">
              Logo cố định ở góc trên phải, layer khác không được che lên.
            </div>
            <button class="btn small" id="btn-toggle-logo">Ẩn / hiện logo</button>
          </div>

          <!-- CUSTOM LAYERS -->
          <div class="field">
            <div class="field-label">Layer hình ảnh & text (giống Photoshop)</div>
            <input
              type="file"
              id="input-custom-layer"
              accept="image/*"
              multiple
              class="field-input-file" />
            <p class="field-note">
              Mỗi file là một layer ảnh độc lập: kéo thả, đổi size, ẩn / hiện, xóa.
            </p>
          </div>

          <!-- TEXT LAYER CONTROL -->
          <div class="field">
            <div class="field-label">Thêm text layer mặt trước</div>
            <input
              type="text"
              id="input-layer-text"
              class="field-input"
              placeholder="Nhập nội dung text layer…" />
            <div class="slider-row">
              <span class="field-label">Cỡ chữ text layer</span>
              <span id="label-layer-text-size">18px</span>
            </div>
            <input
              type="range"
              min="10"
              max="40"
              value="18"
              id="input-layer-text-size" />
            <p class="field-note">Font cho text layer:</p>
            <div class="pill-options" id="layer-text-font">
              <button class="pill-option active" data-font="inter">Hiện đại</button>
              <button class="pill-option" data-font="poppins">Trẻ trung</button>
              <button class="pill-option" data-font="playfair">Sang trọng</button>
            </div>
            <button class="btn small" id="btn-add-text-layer" style="margin-top:6px;">
              <i class="fa-solid fa-plus"></i> Thêm text layer
            </button>
          </div>

          <div class="field">
            <div class="field-label">Danh sách layer</div>
            <div id="layer-list" class="layer-list"></div>
          </div>

          <!-- QR -->
          <div class="field">
            <div class="field-label">QR – URL</div>
            <input
              type="url"
              id="input-qr-url"
              class="field-input"
              placeholder="https://example.com"
              value="https://example.com" />
            <p class="field-note">
              Dán link website, Linktree, profile mạng xã hội hoặc link NFC.
            </p>
          </div>

          <div class="field">
            <div class="slider-row">
              <span class="field-label">QR – kích thước</span>
              <span id="label-qr-size">120px</span>
            </div>
            <input
              type="range"
              min="80"
              max="200"
              value="120"
              id="input-qr-size" />
          </div>

          <div class="line"></div>

          <!-- BACK SIDE -->
          <div class="field">
            <div class="field-label">Mặt sau thẻ</div>
            <label class="checkbox-row">
              <input type="checkbox" id="chk-sync-back" checked />
              <span>Background mặt sau đồng bộ với mặt trước</span>
            </label>
            <label class="checkbox-row">
              <input type="checkbox" id="chk-edit-back" checked />
              <span>Cho phép chỉnh mô tả ngắn mặt sau</span>
            </label>
          </div>

          <div class="field">
            <div class="field-label">Mô tả ngắn mặt sau</div>
            <textarea
              id="input-back-short"
              class="field-input">Thẻ Bio cá nhân KNTech – chạm để xem thông tin.</textarea>
            <p class="field-note">
              Đoạn text ngắn nằm dưới logo và phía trên dòng copyright.
            </p>
          </div>

          <div class="field">
            <div class="slider-row">
              <span class="field-label">Cỡ chữ mô tả mặt sau</span>
              <span id="label-back-size">100%</span>
            </div>
            <input
              type="range"
              min="80"
              max="140"
              value="100"
              id="input-back-size" />
          </div>
        </div>
      </div>
    </section>
  </main>
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/knloader.css">
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/knloader.js"></script>

  <script>
    KNLoader.showFor(2000, {
      brand: "KN BioCard",
      msg: "Đang khởi tạo giao diện…"
    });
  </script>
  <!-- JS -->
  <script src="assets/js/desgin.js"></script>
</body>

</html>