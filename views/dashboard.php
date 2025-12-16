<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use KNCMS\KNCMS;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>KN BioCard – Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Dashboard quản trị KN BioCard." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600&family=DM+Sans:wght@400;500;600&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />

  <!-- Base system -->
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/bio.css" />

  <!-- Extra dashboard polish -->
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/dashboard.css" />
</head>

<body>
  <main>
    <!-- NAV: giữ hệ thống -->
    <div class="kn-nav-wrap">
      <nav class="kn-nav">
        <div class="kn-nav-left">
          <div class="kn-nav-logo">KN</div>
          <div class="kn-nav-brand">
            <span class="kn-nav-brand-title">KN BioCard</span>
            <span class="kn-nav-brand-sub">Dashboard – Quản trị</span>
          </div>
        </div>

        <div class="kn-nav-center">
          <button class="kn-nav-btn active">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
          </button>
          <button class="kn-nav-btn">
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
              <div class="kn-dropdown-item"><i class="fa-regular fa-user"></i> Hồ sơ của tôi</div>
              <div class="kn-dropdown-item"><i class="fa-solid fa-gear"></i> Cài đặt</div>
              <div class="kn-dropdown-item"><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</div>
            </div>
          </div>
        </div>
      </nav>
    </div>

    <!-- DASHBOARD BODY: mới hoàn toàn -->
    <section class="dash-wrap">
      <!-- Header strip -->
      <header class="dash-hero">
        <div class="dash-hero-left">
          <div class="dash-title">
            Dashboard
            <span class="dash-dot"></span>
            <span class="dash-sub">Tổng quan hiệu suất Bio & đơn thẻ NFC</span>
          </div>

          <div class="dash-meta">
            <span class="dash-chip">
              <i class="fa-solid fa-shield-halved"></i>
              API: <b id="api-pill">OK</b>
            </span>
            <span class="dash-chip">
              <i class="fa-regular fa-clock"></i>
              Cập nhật: <b id="updated-at">--:--:--</b>
            </span>
          </div>
        </div>

        <div class="dash-hero-right">
          <button class="btn-pill btn-pill-outline dash-btn" type="button" id="btn-export">
            <i class="fa-solid fa-file-export"></i> Export
          </button>
          <button class="btn-pill btn-pill-primary dash-btn" type="button" id="btn-refresh">
            <i class="fa-solid fa-rotate"></i> Refresh
          </button>
        </div>
      </header>

      <!-- KPI row -->
      <section class="dash-grid dash-grid-kpi">
        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-solid fa-user-check"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Bio hoạt động</div>
            <div class="dash-kpi-value" id="kpi-active">128</div>
            <div class="dash-kpi-sub">
              <span class="dash-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +12%</span>
              <span class="dash-muted">so với tuần trước</span>
            </div>
          </div>
        </article>

        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-regular fa-eye"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Views (7 ngày)</div>
            <div class="dash-kpi-value" id="kpi-views">9,842</div>
            <div class="dash-kpi-sub">
              <span class="dash-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +6%</span>
              <span class="dash-muted">tổng lượt xem</span>
            </div>
          </div>
        </article>

        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Clicks (7 ngày)</div>
            <div class="dash-kpi-value" id="kpi-clicks">2,316</div>
            <div class="dash-kpi-sub">
              <span class="dash-trend down"><i class="fa-solid fa-arrow-trend-down"></i> -3%</span>
              <span class="dash-muted">tương tác link</span>
            </div>
          </div>
        </article>

        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-solid fa-id-card-clip"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Thẻ NFC của bạn</div>
            <div class="dash-kpi-value" id="kpi-nfc">3</div>
            <div class="dash-kpi-sub">
              <span class="dash-badge"><i class="fa-solid fa-check-circle"></i> Tất cả hoạt động</span>
            </div>
          </div>
        </article>
      </section>

      <!-- Main two columns -->
      <section class="dash-grid dash-grid-main">
        <!-- LEFT column -->
        <div class="dash-col">
          <!-- Chart card -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Thống kê Bio của bạn</div>
                <div class="dash-card-sub">Lượt xem & click trong 7 ngày gần nhất</div>
              </div>

              <div class="dash-actions">
                <button class="btn btn-outline dash-btn-sm" type="button" onclick="alert('Xem chi tiết thống kê')">
                  <i class="fa-solid fa-chart-bar"></i> Xem chi tiết
                </button>
              </div>
            </div>

            <div class="dash-chart">
              <div class="dash-chart-grid"></div>
              <div class="dash-chart-overlay">
                <div class="dash-chart-big">
                  <i class="fa-solid fa-chart-line"></i>
                  Khu vực biểu đồ
                </div>
                <div class="dash-chart-small">Sau này bro map data từ API vào canvas/chart lib.</div>
              </div>
            </div>

            <div class="dash-split">
              <div class="dash-mini">
                <div class="dash-mini-label">Tỉ lệ click</div>
                <div class="dash-mini-value" id="mini-ctr">18.5%</div>
              </div>
              <div class="dash-mini">
                <div class="dash-mini-label">Nguồn hàng đầu</div>
                <div class="dash-mini-value" id="mini-source">Instagram</div>
              </div>
              <div class="dash-mini">
                <div class="dash-mini-label">Thời gian hot</div>
                <div class="dash-mini-value" id="mini-peak">19:00</div>
              </div>
            </div>
          </section>

          <!-- Links table -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Links của bạn</div>
                <div class="dash-card-sub">Các link được chia sẻ trên Bio</div>
              </div>

              <div class="dash-actions">
                <button class="btn btn-primary dash-btn-sm" type="button" onclick="alert('Thêm link mới')">
                  <i class="fa-solid fa-plus"></i> Thêm link
                </button>
              </div>
            </div>

            <div class="dash-table-wrap">
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Tên link</th>
                    <th>URL</th>
                    <th>Click</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>Portfolio</strong></td>
                    <td>example.com</td>
                    <td>24</td>
                    <td><span class="dash-status ok">Hoạt động</span></td>
                    <td>12/10/2025</td>
                    <td class="dash-td-right"><button class="dash-linkbtn" type="button">Sửa</button></td>
                  </tr>
                  <tr>
                    <td><strong>Instagram</strong></td>
                    <td>instagram.com/user</td>
                    <td>156</td>
                    <td><span class="dash-status ok">Hoạt động</span></td>
                    <td>05/11/2025</td>
                    <td class="dash-td-right"><button class="dash-linkbtn" type="button">Sửa</button></td>
                  </tr>
                  <tr>
                    <td><strong>YouTube</strong></td>
                    <td>youtube.com/@user</td>
                    <td>43</td>
                    <td><span class="dash-status ok">Hoạt động</span></td>
                    <td>15/11/2025</td>
                    <td class="dash-td-right"><button class="dash-linkbtn" type="button">Sửa</button></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="dash-foot">
              <button class="btn btn-outline dash-btn-sm" type="button">
                <i class="fa-solid fa-list"></i> Xem tất cả
              </button>
              <div class="dash-muted">Xem thêm links chi tiết</div>
            </div>
          </section>
        </div>

        <!-- RIGHT column -->
        <aside class="dash-col">
          <!-- Quick actions -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Quick actions</div>
                <div class="dash-card-sub">Tác vụ nhanh cho admin</div>
              </div>
            </div>

            <div class="dash-quick">
              <button class="dash-quick-item" type="button" onclick="alert('Chỉnh sửa Bio')">
                <span class="dash-quick-ic"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Chỉnh sửa Bio</span>
                  <span class="dash-quick-sub">Tùy chỉnh layout, màu sắc</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>

              <button class="dash-quick-item" type="button" onclick="alert('Chia sẻ Bio')">
                <span class="dash-quick-ic"><i class="fa-solid fa-share-nodes"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Chia sẻ Bio</span>
                  <span class="dash-quick-sub">Copy link & mời bạn</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>

              <button class="dash-quick-item" type="button" onclick="alert('Đặt hàng thẻ NFC')">
                <span class="dash-quick-ic"><i class="fa-solid fa-shopping-cart"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Đặt thẻ NFC</span>
                  <span class="dash-quick-sub">In thẻ vật lý gắn NFC</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>

              <button class="dash-quick-item" type="button" onclick="alert('Cài đặt tài khoản')">
                <span class="dash-quick-ic"><i class="fa-solid fa-sliders"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Cài đặt</span>
                  <span class="dash-quick-sub">Hồ sơ, bảo mật, email</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>
            </div>
          </section>

          <!-- Activity timeline -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Hoạt động gần đây</div>
                <div class="dash-card-sub">Lịch sử các thay đổi của bạn</div>
              </div>
            </div>

            <div class="dash-timeline">
              <div class="dash-tl-item">
                <div class="dash-tl-dot"></div>
                <div class="dash-tl-body">
                  <div class="dash-tl-title">Chỉnh sửa Bio</div>
                  <div class="dash-tl-sub">Cập nhật thứ tự block và màu sắc.</div>
                  <div class="dash-tl-time">1 giờ trước</div>
                </div>
              </div>

              <div class="dash-tl-item">
                <div class="dash-tl-dot"></div>
                <div class="dash-tl-body">
                  <div class="dash-tl-title">Thêm link mới</div>
                  <div class="dash-tl-sub">Instagram link được thêm vào Bio.</div>
                  <div class="dash-tl-time">3 giờ trước</div>
                </div>
              </div>

              <div class="dash-tl-item">
                <div class="dash-tl-dot"></div>
                <div class="dash-tl-body">
                  <div class="dash-tl-title">Cập nhật hồ sơ</div>
                  <div class="dash-tl-sub">Nickname & email đã được thay đổi.</div>
                  <div class="dash-tl-time">Hôm nay</div>
                </div>
              </div>

              <div class="dash-tl-item">
                <div class="dash-tl-dot"></div>
                <div class="dash-tl-body">
                  <div class="dash-tl-title">Đặt hàng thẻ NFC</div>
                  <div class="dash-tl-sub">3 chiếc thẻ Premium đã được đặt.</div>
                  <div class="dash-tl-time">Hôm qua</div>
                </div>
              </div>
            </div>
          </section>

          <!-- Stats box -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Số liệu của bạn</div>
                <div class="dash-card-sub">Tổng quan thành tích Bio</div>
              </div>
            </div>

            <div class="dash-status-grid">
              <div class="dash-svc">
                <div class="dash-svc-left">
                  <div class="dash-svc-ic"><i class="fa-solid fa-eye"></i></div>
                  <div>
                    <div class="dash-svc-title">Tổng lượt xem</div>
                    <div class="dash-svc-sub">Tất cả thời gian</div>
                  </div>
                </div>
                <span style="font-size: 18px; font-weight: 800;">1.2K</span>
              </div>

              <div class="dash-svc">
                <div class="dash-svc-left">
                  <div class="dash-svc-ic"><i class="fa-solid fa-arrow-pointer"></i></div>
                  <div>
                    <div class="dash-svc-title">Tổng click</div>
                    <div class="dash-svc-sub">Từ tất cả link</div>
                  </div>
                </div>
                <span style="font-size: 18px; font-weight: 800;">342</span>
              </div>

              <div class="dash-svc">
                <div class="dash-svc-left">
                  <div class="dash-svc-ic"><i class="fa-solid fa-mobile"></i></div>
                  <div>
                    <div class="dash-svc-title">Từ mobile</div>
                    <div class="dash-svc-sub">72% lượt xem</div>
                  </div>
                </div>
                <span class="dash-pill ok">Hot</span>
              </div>
            </div>
          </section>
        </aside>
      </section>
    </section>
  </main>

  <script>
    (function () {
      const wrap = document.getElementById("nav-avatar-wrap");
      const avatar = document.getElementById("nav-avatar");
      avatar.addEventListener("click", (e) => { e.stopPropagation(); wrap.classList.toggle("open"); });
      document.addEventListener("click", () => wrap.classList.remove("open"));

      const updatedAt = document.getElementById("updated-at");
      const btnRefresh = document.getElementById("btn-refresh");

      function stamp() {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        updatedAt.textContent = pad(d.getHours()) + ":" + pad(d.getMinutes()) + ":" + pad(d.getSeconds());
      }
      btnRefresh.addEventListener("click", stamp);
      stamp();
    })();
  </script>

  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/knloader.css">
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/knloader.js"></script>
  <script>
    if (window.KNLoader) KNLoader.showFor(700, { brand: "KN BioCard", msg: "Đang tải Dashboard…" });
  </script>
</body>
</html>
