<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;

$user = KNCMS::getUserInfo();
// var_dump($user);
function current_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    return $path ?: '/';
}


function activeNav(string $navItem): string {
    $page = current_path();
        $page = trim($page, '/');
        $pageParts = explode('/', $page);
        $page = $pageParts[0] ?? '';
    if( $page === $navItem ) {
        return 'active';
    }
    return '';
}
if (KNCMS::checkLogin()) : ?>
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
                <button class="kn-nav-btn <?= activeNav('dashboard') ?? activeNav('home') ?? activeNav('') ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</button>
                <button class="kn-nav-btn <?= activeNav('bio') ?>" data-go="/bio/layout"><i class="fa-solid fa-user"></i> Bio layout</button>
                <button class="kn-nav-btn <?= activeNav('app') ?>" data-go="/app"><i class="fa-solid fa-id-card-clip"></i> Thẻ NFC</button>
                <button class="kn-nav-btn <?= activeNav('history') ?>" data-go="/history"><i class="fa-regular fa-clock"></i> Lịch sử</button>
                <button class="kn-nav-btn <?= activeNav('card/list') ?>" data-go="/card/list">Cards</button>
            </div>

            <div class="kn-nav-right">
                <div class="kn-nav-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input placeholder="Tìm kiếm..." />
                </div>

                <div class="kn-avatar-wrap" id="nav-avatar-wrap">
                    <div class="kn-avatar" id="nav-avatar"><img class="kn-avatar" src="<?= htmlspecialchars($user['avatar'] ?? KNCMS::baseUrl().'/assets/logo.png') ?>"></div>
                    <div class="kn-dropdown" id="nav-dropdown">
                        <?php if ($isGuest): ?>
                            <div class="kn-dropdown-header">Đang đăng nhập: <?= $user['username'] ?></div>
                            <div class="kn-dropdown-item" data-login>
                                <i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng nhập
                            </div>
                        <?php else: ?>
                            <div class="kn-dropdown-header">Đang đăng nhập: <?= htmlspecialchars($user['nickname'] ?: 'User') ?></div>
                            <div class="kn-dropdown-item" data-go="/me"><i class="fa-regular fa-user"></i> Hồ sơ của tôi</div>
                            <div class="kn-dropdown-item" data-go="/settings"><i class="fa-solid fa-gear"></i> Cài đặt</div>
                            <div class="kn-dropdown-item" data-logout><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </div>
<?php endif; ?>