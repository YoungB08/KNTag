Dưới đây là **README chuẩn GitHub (Markdown)**, viết đúng style repo open-source / production, bạn chỉ cần copy dán vào `README.md` là dùng được ngay.

---

```md
# KN Card Module

> Card Design • Order • Checkout • Payment API  
> Endpoint chuẩn: `/api/card/<system>`  
> Built for **KNCMS Core** – PHP thuần, không framework

---

## ✨ Giới thiệu

**KN Card Module** là module độc lập dùng để quản lý **Card** (không phải Bio), bao gồm:

- Lưu & load **nhiều card** theo user
- Lưu toàn bộ **layout editor (JSON)**
- Checkout 2 bước (giao hàng → thanh toán)
- Thanh toán:
  - Banking (chuyển khoản – chờ duyệt)
  - COD
- API RESTful, bảo mật theo owner
- Tương thích 100% với core `KNCMS`

---

## 📁 Cấu trúc thư mục

```

PROJECT_ROOT/
│
├── api/
│   └── card/
│       ├── index.php          # API entry
│       ├── systems.php        # Router + business logic
│       ├── _boot.php          # Load KNCMS
│       ├── _auth.php          # Auth & owner check
│       ├── _res.php           # JSON helpers
│       ├── _upload.php        # Upload ảnh
│       └── .htaccess          # Rewrite /api/card/<system>
│
├── pages/
│   ├── cards_list.php         # List card
│   ├── checkout_step2.php     # Shipping + payment
│   ├── pay_banking.php        # Banking
│   └── pay_cod.php            # COD
│
└── schema.sql                 # Database schema

````

---

## ⚙️ Cài đặt

### 1️⃣ Copy module vào project

```text
/api/card        → PROJECT_ROOT/api/card
/pages           → PROJECT_ROOT/pages
/schema.sql      → import database
````

---

### 2️⃣ Import database

```sql
schema.sql
```

Tạo các bảng:

* `cards`
* `uploads`
* `card_orders`
* `card_payments`

---

### 3️⃣ Bật rewrite (Apache)

File đã có sẵn:

```
/api/card/.htaccess
```

```apache
RewriteEngine On
RewriteRule ^([a-zA-Z0-9_-]+)$ index.php [QSA,L]
```

➡️ Cho phép gọi API dạng:

```
/api/card/list
/api/card/get?id=1
/api/card/save
```

---

## 🔐 Authentication

Module sử dụng:

```php
KNCMS::checkLogin()
```

Thứ tự xác thực:

1. Session (`$_SESSION['auth']['uid']`)
2. JWT cookie (`kn_token`)

✔️ Mỗi card / order đều kiểm tra **owner (user_id)**
✔️ Không truy cập chéo dữ liệu

---

## 🚀 API Endpoints

### 👤 User

| Method | Endpoint       | Mô tả          |
| ------ | -------------- | -------------- |
| GET    | `/api/card/me` | Thông tin user |

---

### 🎨 Card

| Method | Endpoint                 | Mô tả            |
| ------ | ------------------------ | ---------------- |
| GET    | `/api/card/list`         | Danh sách card   |
| GET    | `/api/card/get?id=ID`    | Lấy card         |
| POST   | `/api/card/save`         | Tạo / cập nhật   |
| POST   | `/api/card/delete`       | Xóa card         |
| POST   | `/api/card/upload_image` | Upload ảnh layer |

#### Save Card (JSON)

```json
{
  "id": 12,
  "title": "My Card",
  "status": "draft",
  "layout": { "...": "editor_state" }
}
```

---

### 🛒 Checkout

| Method | Endpoint                      | Mô tả         |
| ------ | ----------------------------- | ------------- |
| POST   | `/api/card/checkout_create`   | Tạo order     |
| POST   | `/api/card/checkout_shipping` | Lưu giao hàng |
| POST   | `/api/card/checkout_payment`  | Chọn payment  |
| GET    | `/api/card/order_get?id=ID`   | Lấy order     |

---

### 💳 Payment

| Method | Endpoint                        | Mô tả                 |
| ------ | ------------------------------- | --------------------- |
| POST   | `/api/card/pay_banking_confirm` | Xác nhận chuyển khoản |
| POST   | `/api/card/pay_cod_confirm`     | Xác nhận COD          |

---

## 🧾 Pages (PHP thuần)

| Page           | URL                         |
| -------------- | --------------------------- |
| Danh sách card | `/pages/cards_list.php`     |
| Checkout       | `/pages/checkout_step2.php` |
| Banking        | `/pages/pay_banking.php`    |
| COD            | `/pages/pay_cod.php`        |

➡️ Tất cả page đều yêu cầu đăng nhập.

---

## 🔗 Kết nối với Editor (JS)

Chỉ cần đổi endpoint:

```js
fetch("/api/card/save")
fetch("/api/card/get?id=123")
fetch("/api/card/upload_image")
```

`layout` = toàn bộ state editor (layer, position, z-index, size).

---

## 📦 Trạng thái Order

```text
draft
→ pending_payment
→ payment_review
→ paid
→ processing
→ shipping
→ done
→ cancel
```

---

## 🧠 Thiết kế kiến trúc

* PHP thuần (no framework)
* 1 API entry → router theo `<system>`
* Mỗi system = 1 chức năng rõ ràng
* Dễ mở rộng admin / webhook / payment gateway

---

## 📌 Ghi chú

* Module này là **CARD**, không phải BIO
* Có thể dùng cho:

  * Card NFC
  * Card in
  * Card digital
* Có thể tách admin panel sau

---

## 📜 License

Private / Internal use
© KNTech

```

---

Nếu bạn muốn, mình có thể:
- Chuẩn hóa README sang **song ngữ (VN/EN)**
- Viết thêm **API Spec (OpenAPI / Swagger)**
- Viết **diagram flow & ERD** cho repo

Cứ nói tiếp hướng bạn muốn đẩy repo này đi đâu (product thật, open-source hay internal).
```
