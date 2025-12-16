<?php

declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>KN BioCard – Bio của KNTech</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description"
    content="Trang Bio cá nhân KN BioCard: chia sẻ link mạng xã hội, portfolio, liên hệ chỉ với một URL.">

  <!-- Font + Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <link
    rel="icon"
    type="image/png"
    href="<?= KNCMS::baseUrl(); ?>/assets/fav.png" />
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/viewer.css" />

  <style>
  </style>
</head>

<body>
  <main>
    <!-- Brand chip -->
    <div class="brand-chip">
      <div class="brand-chip-logo">KN</div>
      <div class="brand-chip-text">Bio được tạo bởi KN BioCard – NFC ready</div>
    </div>

    <!-- BIO card -->
    <section class="bio-shell">
      <div class="bio-inner">
        <div class="bio-avatar" id="bio-avatar">
          <!-- img hoặc icon sẽ được JS render -->
        </div>

        <div class="bio-name" id="bio-name"></div>
        <div class="bio-nick" id="bio-nick"></div>

        <div class="bio-role" id="bio-role"></div>

        <div class="bio-jobs" id="bio-jobs"></div>

        <p class="bio-intro" id="bio-intro"></p>

        <div class="bio-cta-row">
          <a href="#" target="_blank" rel="noopener" id="cta-main" class="btn-cta">
            <i class="fa-solid fa-link"></i>
            <span>Xem portfolio</span>
          </a>
          <a href="#" target="_blank" rel="noopener" id="cta-contact" class="btn-cta secondary">
            <i class="fa-solid fa-message"></i>
            <span>Liên hệ nhanh</span>
          </a>
        </div>

        <div class="links-wrap" id="links-wrap">
          <!-- link items render bằng JS -->
        </div>
      </div>
    </section>

    <div class="footer">
      Thẻ Bio được lưu trữ bởi <a href="#">KNTech</a>. Chạm / quét NFC để mở lại trang này.
    </div>
  </main>
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/knloader.css">
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/knloader.js"></script>

  <script>
    KNLoader.showFor(2000, {
      brand: "KN BioCard",
      msg: "Đang khởi tạo giao diện…"
    });
  </script>

  <script src="<?= KNCMS::baseUrl() ?>/assets/js/viewer.js"></script>
</body>

</html>