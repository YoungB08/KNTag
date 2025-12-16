<?php

declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use KNCMS\KNCMS;
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

<body>

  <main>
    <header>
      <div class="brand">
        <div class="brand-logo">KN</div>
        <div>
          <div class="brand-text-title">KN BioCard</div>
          <div class="brand-text-sub">Tạo thẻ Bio cá nhân – NFC ready</div>
        </div>
      </div>
    </header>

    <section class="auth-wrapper">
      <div class="auth-card">
        <div class="auth-tabs">
          <button class="auth-tab active" id="tab-login">Đăng nhập</button>
          <button class="auth-tab" id="tab-register">Đăng ký</button>
        </div>

        <div class="auth-title">
          <span id="auth-title-text">Chào mừng quay lại</span>
          <span class="emoji">👋</span>
        </div>
        <p class="auth-desc" id="auth-desc-text">
          Đăng nhập để tiếp tục tạo BioCard cực ngầu.
        </p>

        <div class="auth-error" id="auth-error" style="display:none"></div>

        <div class="field">
          <div class="field-label">Email</div>
          <input
            id="auth-email"
            type="email"
            placeholder="you@example.com"
            class="field-input"
            autocomplete="email" />
        </div>

        <div class="field">
          <div class="field-label">Mật khẩu</div>
          <input
            id="auth-password"
            type="password"
            placeholder="••••••••"
            class="field-input"
            autocomplete="current-password" />
        </div>

        <button class="auth-button" id="auth-submit">
          <span id="auth-submit-text">→ Đăng nhập</span>
        </button>

        <p class="auth-footer">
          Đăng nhập/đăng ký chạy qua API (JWT). Token lưu ở LocalStorage.
        </p>
      </div>
    </section>
  </main>
  <script src="<?= KNCMS::baseUrl(); ?>/assets/js/kncms.js"></script>

  <script>
    const tabLogin = document.getElementById("tab-login");
    const tabRegister = document.getElementById("tab-register");
    const authTitleText = document.getElementById("auth-title-text");
    const authDescText = document.getElementById("auth-desc-text");
    const authSubmit = document.getElementById("auth-submit");
    const authSubmitText = document.getElementById("auth-submit-text");
    const authEmail = document.getElementById("auth-email");
    const authPassword = document.getElementById("auth-password");
    const authError = document.getElementById("auth-error");

    const TOKEN_KEY = "kn_token";
    let mode = "login";

    function setError(msg) {
      authError.textContent = msg || "";
      authError.style.display = msg ? "block" : "none";
    }

    function setLoading(isLoading) {
      authSubmit.disabled = !!isLoading;
      authSubmit.style.opacity = isLoading ? "0.85" : "1";
    }

    function goToDesign() {
      window.location.href = "/Auth";
    }

    function switchMode(nextMode) {
      mode = nextMode;

      if (nextMode === "login") {
        tabLogin.classList.add("active");
        tabRegister.classList.remove("active");
        authTitleText.textContent = "Chào mừng quay lại";
        authDescText.textContent = "Đăng nhập để tiếp tục tạo BioCard cực ngầu.";
        authSubmitText.textContent = "→ Đăng nhập";
      } else {
        tabLogin.classList.remove("active");
        tabRegister.classList.add("active");
        authTitleText.textContent = "Tạo tài khoản mới";
        authDescText.textContent = "Đăng ký lần đầu để bắt đầu sử dụng BioCard.";
        authSubmitText.textContent = "✦ Đăng ký & vào app";
      }

      setError("");
    }

    tabLogin.addEventListener("click", () => switchMode("login"));
    tabRegister.addEventListener("click", () => switchMode("register"));

    function normalizeEmail(s) {
      return (s || "").trim().toLowerCase();
    }

    async function callApi(path, payload) {
      const res = await fetch(path, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json().catch(() => ({}));
      return {
        res,
        data
      };
    }

    function pickValidationMessage(fields) {
      // fields dạng: {email:["required"], password:["min:6"]} ...
      if (!fields || typeof fields !== "object") return "Dữ liệu không hợp lệ.";
      if (fields.email) return "Email không hợp lệ hoặc bị thiếu.";
      if (fields.password) return "Mật khẩu không hợp lệ (6–72 ký tự).";
      return "Dữ liệu không hợp lệ.";
    }

    authSubmit.addEventListener("click", async () => {
      const email = normalizeEmail(authEmail.value);
      const password = (authPassword.value || "").trim();

      setError(""); // fallback text (ẩn)

      if (!email || !password) {
        notifyError("Vui lòng nhập đầy đủ email và mật khẩu.");
        return;
      }

      const endpoint = mode === "register" ?
        "/api/register" :
        "/api/login";

      setLoading(true);

      try {
        const {
          res,
          data
        } = await callApi(endpoint, {
          email,
          password
        });

        /* ===== ERROR HANDLING ===== */
        if (!res.ok || !data || data.ok !== true) {
          const code = data?.error || "UNKNOWN";

          switch (code) {
            case "VALIDATION_ERROR":
              notifyError(pickValidationMessage(data.fields));
              break;

            case "EMAIL_EXISTS":
              notifyError("Email đã tồn tại, hãy chọn email khác.");
              break;

            case "INVALID_CREDENTIALS":
              notifyError("Sai email hoặc mật khẩu.");
              break;

            case "RATE_LIMITED":
              notifyError("Bạn thao tác quá nhanh. Vui lòng thử lại sau.");
              break;

            default:
              notifyError(data?.message || "Có lỗi xảy ra, vui lòng thử lại.");
          }

          return;
        }

        /* ===== SUCCESS ===== */
        if (data.token) {
          localStorage.setItem(TOKEN_KEY, data.token);
        }

        if (mode === "register") {
          notifySuccess("🎉 Đăng ký thành công! Đang vào ứng dụng…");
        } else {
          notifySuccess("👋 Đăng nhập thành công! Chào mừng quay lại.");
        }

        // Delay nhẹ cho mượt UX
        setTimeout(goToDesign, 900);

      } catch (e) {
        notifyError("Không thể kết nối máy chủ. Vui lòng kiểm tra mạng.");
      } finally {
        setLoading(false);
      }
    });
    // Nếu đã có token thì đi thẳng vào app
    const existing = localStorage.getItem(TOKEN_KEY);
    if (existing) {
      goToDesign();
    }
  </script>

</body>
  <link rel="stylesheet" href="<?= KNCMS::baseUrl() ?>/assets/styles/knloader.css">
  <script src="<?= KNCMS::baseUrl() ?>/assets/js/knloader.js"></script>

  <script>
    KNLoader.showFor(2000, {
      brand: "KN BioCard",
      msg: "Đang khởi tạo giao diện…"
    });
  </script>
</html>