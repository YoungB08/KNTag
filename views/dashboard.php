<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function current_user_id(): ?int {
  // ✅ CHỈNH Ở ĐÂY cho khớp hệ login của bro
  // Ví dụ: $_SESSION['uid'] được set khi login thành công
  $uid = $_SESSION['auth']['uid'] ?? null;
  if (is_int($uid)) return $uid;
  if (is_string($uid) && ctype_digit($uid)) return (int)$uid;
  return null;
}

$uid = current_user_id();
$isGuest = ($uid === null);

// Nickname public: nếu guest -> rỗng, nếu login -> lấy nickname user
$nickname = '';
$avatarLetter = 'G';
if (!$isGuest) {
  $u = KNCMS::get_row("SELECT nickname, username FROM users WHERE id=:id LIMIT 1", ['id'=>$uid]);
  $nickname = (string)($u['nickname'] ?? '');
  $displayName = (string)($u['username'] ?? $nickname ?? 'User');
  $avatarLetter = strtoupper(mb_substr(trim($displayName) ?: 'U', 0, 1));
}
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

  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/bio.css" />
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/dashboard.css" />
</head>

<body>
  <main>
    

    <section class="dash-wrap" id="dash-root"
      data-is-guest="<?= $isGuest ? '1' : '0' ?>"
      data-nickname="<?= htmlspecialchars($nickname) ?>"
      data-base="<?= htmlspecialchars(KNCMS::baseUrl()) ?>">

      <header class="dash-hero">
        <div class="dash-hero-left">
          <div class="dash-title">
            Dashboard
            <span class="dash-dot"></span>
            <span class="dash-sub">
              <?= $isGuest ? 'Chỉ hiển thị thông tin public (Guest mode)' : 'Tổng quan hiệu suất Bio & dữ liệu cá nhân' ?>
            </span>
          </div>

          <div class="dash-meta">
            <span class="dash-chip"><i class="fa-solid fa-shield-halved"></i> API: <b id="api-pill">--</b></span>
            <span class="dash-chip"><i class="fa-regular fa-clock"></i> Cập nhật: <b id="updated-at">--:--:--</b></span>
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

      <!-- Guest notice -->
      <?php if ($isGuest): ?>
        <div class="dash-guest-banner">
          <div class="dash-guest-banner-ic"><i class="fa-solid fa-lock"></i></div>
          <div class="dash-guest-banner-txt">
            <div class="dash-guest-banner-title">Bạn đang ở chế độ Guest</div>
            <div class="dash-guest-banner-sub">
              Dashboard sẽ ẩn logs/dữ liệu riêng tư. Đăng nhập để xem thống kê đầy đủ và chỉnh sửa links, bio, NFC.
            </div>
          </div>
          <button class="btn-pill btn-pill-primary" type="button" data-login>
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng nhập
          </button>
        </div>
      <?php endif; ?>

      <!-- KPI -->
      <section class="dash-grid dash-grid-kpi">
        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-solid fa-user-check"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Bio hoạt động</div>
            <div class="dash-kpi-value" id="kpi-active"><?= $isGuest ? '—' : '0' ?></div>
            <div class="dash-kpi-sub">
              <span class="dash-muted"><?= $isGuest ? 'Cần đăng nhập để xem' : 'Cập nhật theo hệ thống' ?></span>
            </div>
          </div>
        </article>

        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-regular fa-eye"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Views (7 ngày)</div>
            <div class="dash-kpi-value" id="kpi-views"><?= $isGuest ? '—' : '0' ?></div>
            <div class="dash-kpi-sub"><span class="dash-muted" id="kpi-views-sub"><?= $isGuest ? 'Public mode' : 'Tổng 7 ngày' ?></span></div>
          </div>
        </article>

        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Clicks (7 ngày)</div>
            <div class="dash-kpi-value" id="kpi-clicks"><?= $isGuest ? '—' : '0' ?></div>
            <div class="dash-kpi-sub"><span class="dash-muted"><?= $isGuest ? 'Ẩn dữ liệu click' : 'Tổng 7 ngày' ?></span></div>
          </div>
        </article>

        <article class="dash-kpi">
          <div class="dash-kpi-ic"><i class="fa-solid fa-id-card-clip"></i></div>
          <div class="dash-kpi-main">
            <div class="dash-kpi-label">Thẻ NFC của bạn</div>
            <div class="dash-kpi-value" id="kpi-nfc"><?= $isGuest ? '—' : '0' ?></div>
            <div class="dash-kpi-sub"><span class="dash-muted"><?= $isGuest ? 'Ẩn' : 'Số thẻ đang sở hữu' ?></span></div>
          </div>
        </article>
      </section>

      <section class="dash-grid dash-grid-main">
        <div class="dash-col">
          <!-- Chart -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Thống kê Bio của bạn</div>
                <div class="dash-card-sub">14 ngày gần nhất (auto, không dùng GET param)</div>
              </div>

              <div class="dash-actions">
                <button class="btn-pill btn-pill-outline dash-btn-sm" type="button" id="btn-stats-detail">
                  <i class="fa-solid fa-chart-column"></i> Xem chi tiết
                </button>
              </div>
            </div>

            <div class="dash-chart" id="chart-box">
              <div class="dash-chart-grid"></div>
              <div class="dash-chart-overlay" id="chart-overlay">
                <div class="dash-chart-big"><i class="fa-solid fa-chart-line"></i> Biểu đồ</div>
                <div class="dash-chart-small">
                  <?= $isGuest ? 'Đăng nhập để xem số liệu views/unique theo ngày.' : 'Đang tải dữ liệu…' ?>
                </div>
              </div>
              <canvas id="chart-canvas" height="240" style="display:none;"></canvas>
            </div>

            <div class="dash-split">
              <div class="dash-mini">
                <div class="dash-mini-label">Tỉ lệ click</div>
                <div class="dash-mini-value" id="mini-ctr"><?= $isGuest ? '—' : '0%' ?></div>
              </div>
              <div class="dash-mini">
                <div class="dash-mini-label">Nguồn hàng đầu</div>
                <div class="dash-mini-value" id="mini-source"><?= $isGuest ? '—' : '—' ?></div>
              </div>
              <div class="dash-mini">
                <div class="dash-mini-label">Thời gian hot</div>
                <div class="dash-mini-value" id="mini-peak"><?= $isGuest ? '—' : '—' ?></div>
              </div>
            </div>
          </section>

          <!-- Links -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Links của bạn</div>
                <div class="dash-card-sub">Lấy từ bio_blocks (type=icon)</div>
              </div>

              <div class="dash-actions">
                <button class="btn-pill btn-pill-primary dash-btn-sm" type="button" id="btn-add-link">
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
                    <th>Icon</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="links-tbody">
                  <tr>
                    <td colspan="4" class="dash-muted" style="padding:14px;">
                      <?= $isGuest ? 'Guest: chỉ xem public, không load links riêng tư.' : 'Đang tải…' ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="dash-foot">
              <button class="btn-pill btn-pill-outline dash-btn-sm" type="button" id="btn-links-all">
                <i class="fa-solid fa-list"></i> Xem tất cả
              </button>
              <div class="dash-muted" id="links-foot-note"></div>
            </div>
          </section>
        </div>

        <aside class="dash-col">
          <!-- Quick actions -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Quick actions</div>
                <div class="dash-card-sub">Các tác vụ sẽ yêu cầu đăng nhập</div>
              </div>
            </div>

            <div class="dash-quick">
              <button class="dash-quick-item" type="button" data-auth-action="edit-bio">
                <span class="dash-quick-ic"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Chỉnh sửa Bio</span>
                  <span class="dash-quick-sub">Layout, màu sắc, font</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>

              <button class="dash-quick-item" type="button" data-auth-action="share-bio">
                <span class="dash-quick-ic"><i class="fa-solid fa-share-nodes"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Chia sẻ Bio</span>
                  <span class="dash-quick-sub">Copy link + QR</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>

              <button class="dash-quick-item" type="button" data-auth-action="order-nfc">
                <span class="dash-quick-ic"><i class="fa-solid fa-cart-shopping"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Đặt thẻ NFC</span>
                  <span class="dash-quick-sub">Gói + preview</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>

              <button class="dash-quick-item" type="button" data-auth-action="settings">
                <span class="dash-quick-ic"><i class="fa-solid fa-sliders"></i></span>
                <span class="dash-quick-txt">
                  <span class="dash-quick-title">Cài đặt</span>
                  <span class="dash-quick-sub">Bảo mật, email</span>
                </span>
                <i class="fa-solid fa-chevron-right dash-quick-go"></i>
              </button>
            </div>
          </section>

          <!-- Activity (ẩn khi guest) -->
          <section class="dash-card" id="activity-card" style="<?= $isGuest ? 'display:none;' : '' ?>">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Hoạt động gần đây</div>
                <div class="dash-card-sub">Chỉ hiển thị khi đã đăng nhập</div>
              </div>
              <button class="btn-pill btn-pill-outline dash-btn-sm" type="button" id="btn-activity-log">
                <i class="fa-regular fa-clock"></i> Log
              </button>
            </div>

            <div class="dash-timeline" id="activity-list">
              <div class="dash-muted" style="padding:10px;">Đang tải…</div>
            </div>
          </section>

          <!-- Public card always visible -->
          <section class="dash-card">
            <div class="dash-card-head">
              <div>
                <div class="dash-card-title">Public info</div>
                <div class="dash-card-sub">Luôn hiển thị cho mọi người</div>
              </div>
            </div>

            <div class="dash-public-box">
              <div class="dash-public-row">
                <div class="dash-public-k">Bio URL</div>
                <div class="dash-public-v" id="public-bio-url">—</div>
              </div>
              <div class="dash-public-row">
                <div class="dash-public-k">NFC</div>
                <div class="dash-public-v">Hỗ trợ chia sẻ nhanh</div>
              </div>
              <div class="dash-public-row">
                <div class="dash-public-k">Privacy</div>
                <div class="dash-public-v"><?= $isGuest ? 'Guest (ẩn dữ liệu)' : 'Authenticated' ?></div>
              </div>
            </div>
          </section>
        </aside>
      </section>

      <!-- Modal auth -->
      <div class="dash-modal" id="auth-modal" aria-hidden="true">
        <div class="dash-modal-inner">
          <div class="dash-modal-title"><i class="fa-solid fa-lock"></i> Cần đăng nhập</div>
          <div class="dash-modal-sub">Chức năng này chỉ hoạt động khi bạn đã đăng nhập.</div>
          <div class="dash-modal-actions">
            <button class="btn-pill btn-pill-outline" type="button" id="auth-modal-close">Đóng</button>
            <button class="btn-pill btn-pill-primary" type="button" data-login>Đăng nhập</button>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="<?= KNCMS::baseUrl() ?>/assets/js/dashboard.js"></script>
  <?php if(!KNCMS::checkLogin()) {
    echo '<script>window.openAuthModal();</script>';
  }?>
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/knloader.css">
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/knloader.js"></script>
  <script>
    if (window.KNLoader) KNLoader.showFor(500, { brand: "KN BioCard", msg: "Đang tải Dashboard…" });
  </script>
</body>
</html>
