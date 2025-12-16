<?php

declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;
use KNCMS\Auth\JWT;
use KNCMS\Core\Session;
use KNCMS\Core\Env;

if (!KNCMS::checkLogin()) {
    header('Location: ' . KNCMS::baseUrl() . '/auth');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Đăng nhập – KN BioCard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description"
    content="Đăng nhập / Đăng ký để sử dụng KN BioCard – công cụ tạo thẻ Bio cá nhân (NFC) hiện đại." />

  <!-- Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
    <link rel="stylesheet" href="<?= KNCMS::baseUrl(); ?>/assets/styles/kncms.css">
  <link rel="icon" type="image/png" href="<?= KNCMS::baseUrl(); ?>/assets/fav.png" />
  <link rel="stylesheet" href="<?= KNCMS::baseUrl(); ?>/assets/styles/auth.css" />
</head>

<?php
// --- Server-side auth & load user ---

$userId = null;
if (!empty($_SESSION['auth']['uid'])) {
    $userId = (int)$_SESSION['auth']['uid'];
} else {
    // try cookie token
    $token = $_COOKIE['kn_token'] ?? null;
    if ($token) {
        $secret = Env::get('JWT_SECRET', 'changeme');
        $payload = JWT::decode($token, $secret);
        if (is_array($payload) && !empty($payload['sub'])) {
            $userId = (int)$payload['sub'];
            // hydrate session
            $_SESSION['auth'] = ['uid' => $userId, 'email' => $payload['email'] ?? '', 'ts' => time()];
        }
    }
}

if (empty($userId)) {
    header('Location: ' . KNCMS::baseUrl() . '/auth.html');
    exit;
}

$user = KNCMS::get_row('SELECT id,username,nickname,email,phone FROM users WHERE id=:id LIMIT 1', ['id' => $userId]);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $nickname = trim((string)($_POST['nickname'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));

    // basic validation
    $errors = [];
    if ($username === '') $errors[] = 'Username không được để trống.';
    if ($nickname === '') $errors[] = 'Nickname không được để trống.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';

    if (count($errors) === 0) {
        // update
        KNCMS::exec(
            'UPDATE users SET username=:username, nickname=:nickname, email=:email, phone=:phone WHERE id=:id',
            ['username' => $username, 'nickname' => $nickname, 'email' => $email, 'phone' => $phone, 'id' => $userId]
        );

        $msg = 'Cập nhật thông tin thành công.';
        $user = KNCMS::get_row('SELECT id,username,nickname,email,phone FROM users WHERE id=:id LIMIT 1', ['id' => $userId]);
    } else {
        $msg = implode(' ', $errors);
    }
}
?>

<body>
  <main>
    <header>
      <div class="brand">
        <div class="brand-logo">KN</div>
        <div>
          <div class="brand-text-title">Cập nhật thông tin</div>
          <div class="brand-text-sub">Chỉnh sửa Username, Nickname, Email, Phone</div>
        </div>
      </div>
    </header>

    <section class="auth-wrapper">
      <div class="auth-card">
        <?php if ($msg): ?>
          <div style="margin-bottom:12px;padding:8px;border-radius:8px;background:#ecfdf5;color:#065f46;"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="field">
            <div class="field-label">Username</div>
            <input name="username" type="text" value="<?= htmlspecialchars($user['username'] ?? '') ?>" class="field-input" />
          </div>

          <div class="field">
            <div class="field-label">Nickname</div>
            <input name="nickname" type="text" value="<?= htmlspecialchars($user['nickname'] ?? '') ?>" class="field-input" />
          </div>

          <div class="field">
            <div class="field-label">Email</div>
            <input name="email" type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="field-input" />
          </div>

          <div class="field">
            <div class="field-label">Phone</div>
            <input name="phone" type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="field-input" />
          </div>

          <button type="submit" class="auth-button">Lưu thay đổi</button>
        </form>
      </div>
    </section>
  </main>
  <script src="<?= KNCMS::baseUrl(); ?>/assets/js/kncms.js"></script>
</body>
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/knloader.css">
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/knloader.js"></script>

  <script>
    KNLoader.showFor(800, {
      brand: "KN BioCard",
      msg: "Đang lưu thay đổi…"
    });
  </script>
</html>