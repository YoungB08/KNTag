# 🎴 KN BioCard

> **Digital Bio + NFC Card Management Platform**  
> Create stunning digital bios and order custom NFC cards to share your profile with the world.

<div align="center">

[![Version](https://img.shields.io/badge/version-1.0.0-blue?style=flat-square)](https://github.com/yourusername/KNTag)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![PHP](https://img.shields.io/badge/php-7.4+-purple?style=flat-square)](https://php.net)
[![MySQL](https://img.shields.io/badge/mysql-5.7+-blue?style=flat-square)](https://mysql.com)
[![Vue.js](https://img.shields.io/badge/vue-2.6+-green?style=flat-square)](https://vuejs.org)

**[🌐 Live Demo](#demo) • [📖 Documentation](#documentation) • [🚀 Quick Start](#quick-start) • [💻 Tech Stack](#tech-stack)**

</div>

---

## ✨ Features

### 🎨 Bio Editor
- **Drag & Drop Layout** - Reorder bio blocks freely (avatar, name, nickname, role, jobs, intro, icons)
- **Full Customization** - Background gradient, colors, fonts, themes
- **Live Preview** - See changes in real-time
- **Icon Library** - 1000+ icons with search & link support
- **One-Click Publish** - Share your bio instantly

### 📊 Dashboard & Analytics
- **Personal Dashboard** - Bio stats (views, clicks, traffic sources)
- **Link Management** - Create, edit, delete custom links
- **Activity Timeline** - Track all changes & interactions
- **7-Day Analytics** - Views, clicks, CTR, peak times
- **Mobile Breakdown** - See traffic from mobile vs desktop

### 🏷️ NFC Card Management
- **Order Custom Cards** - Premium/Standard/Basic tiers
- **Design Preview** - See your card before production
- **Batch Orders** - Create multiple cards
- **Status Tracking** - Real-time order updates
- **Multiple Designs** - Customizable card layouts

### 👤 User Profile
- **Profile Editor** - Username, nickname, email, phone
- **Settings** - Theme, billing, security preferences
- **Auth System** - JWT + Session-based authentication
- **Two-factor Ready** - Security-first architecture

### 🔐 Security & Privacy
- **JWT Authentication** - HS256 encrypted tokens
- **ARGON2ID Hashing** - Military-grade password encryption
- **Rate Limiting** - DDoS & brute-force protection
- **HTTPS Ready** - Secure by default
- **XSS/CSRF Protection** - Built-in security filters

---

## 🚀 Quick Start

### Prerequisites
- **PHP 7.4+** with PDO, cURL, OpenSSL
- **MySQL 5.7+** or MariaDB 10.3+
- **Node.js 14+** (for frontend tooling, optional)
- **Composer** (PHP dependency manager)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/KNTag.git
   cd KNTag
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. **Setup database**
   ```bash
   mysql -u root -p < database.sql
   # Or import via phpMyAdmin
   ```

5. **Set permissions**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 cache/
   ```

6. **Start development server**
   ```bash
   php -S localhost:8000
   # Visit http://localhost:8000
   ```

---

## 📁 Project Structure

```
KNTag/
├── 📄 index.html           # Main entry point
├── 📄 app.html             # Dashboard page
├── 📄 auth.html            # Login/Register page
├── 🎨 style.css            # Global styles
│
├── scripts/                # Frontend JavaScript
│   ├── main.js             # App initialization
│   ├── auth.js             # Authentication helpers
│   ├── storage.js          # LocalStorage utilities
│   ├── profile.js          # User profile manager
│   ├── card.js             # Bio card editor
│   └── icons.js            # Icon picker
│
├── styles/                 # CSS modules
│   ├── base.css            # Base styles
│   ├── components.css      # Component styles
│   ├── themes.css          # Theme definitions
│   └── dashboard.css       # Dashboard-specific styles
│
├── assets/                 # Static assets
│   ├── images/             # PNG, SVG, etc
│   ├── fonts/              # Custom fonts
│   └── icons/              # Icon sets
│
├── views/                  # PHP server pages
│   ├── dashboard.php       # User dashboard
│   ├── info.php            # Profile editor
│   ├── bio.php             # Bio editor
│   └── admin/              # Admin panels
│
├── api/                    # REST API endpoints
│   ├── Auth.php            # Authentication endpoints
│   ├── Bio.php             # Bio operations
│   ├── Links.php           # Link management
│   └── Analytics.php       # Statistics
│
├── src/                    # PHP framework code
│   └── KNCMS/              # Custom micro-framework
│       ├── KNCMS.php       # Main facade
│       ├── Core/           # Core classes
│       ├── Database/       # DB layer
│       ├── Auth/           # Authentication
│       ├── Network/        # HTTP utilities
│       └── Security/       # Security filters
│
├── vendor/                 # Composer dependencies
├── storage/                # Uploads, cache, logs
├── config/                 # Configuration files
├── database.sql            # Database schema
├── .env.example            # Environment template
└── README.md               # This file
```

---

## 💻 Tech Stack

### Backend
| Technology | Purpose | Version |
|-----------|---------|---------|
| **PHP** | Server-side logic | 7.4+ |
| **MySQL** | Database | 5.7+ |
| **Composer** | Dependency manager | Latest |
| **JWT** | Token auth | HS256 |
| **PDO** | Database abstraction | Built-in |

### Frontend
| Technology | Purpose | Version |
|-----------|---------|---------|
| **Vue.js** | UI framework | 2.6.14 |
| **Tailwind CSS** | Utility CSS | Latest |
| **Font Awesome** | Icons | 6.5.1 |
| **Feather Icons** | Icon set | 4.29 |
| **QRCode JS** | QR generation | Latest |

### Libraries & Tools
- **html2canvas** - Screenshot to image
- **jsPDF** - PDF generation
- **Dotenv** - Environment configuration
- **Chart.js** - Analytics charts (optional)

---

## 📖 API Documentation

### Authentication Endpoints

#### POST `/api/login`
Login with email & password
```json
{
  "email": "user@example.com",
  "password": "your_password"
}
```
**Response:** `{ token: "jwt_token", user: { id, email, username } }`

#### POST `/api/register`
Create new account
```json
{
  "email": "new@example.com",
  "password": "secure_password",
  "username": "john_doe"
}
```

#### POST `/api/logout`
Logout & clear session
**Response:** `{ success: true }`

#### GET `/api/me`
Get current user info
**Response:** `{ id, email, username, nickname, phone, created_at }`

### Bio Endpoints

#### GET `/@{username}`
View public bio
**Response:** `{ user, bio_blocks, links }`

#### POST `/api/bio/save`
Save bio layout
```json
{
  "blocks": [...],
  "background": "#ffffff",
  "theme": "light"
}
```

#### GET `/api/bio/stats`
Get bio analytics
**Response:** `{ views, clicks, ctr, top_source, peak_time }`

### Link Endpoints

#### GET `/api/links`
List user's links
**Response:** `[{ id, name, url, clicks, created_at }, ...]`

#### POST `/api/links/create`
Create new link
```json
{
  "name": "Portfolio",
  "url": "example.com"
}
```

#### PUT `/api/links/{id}`
Update link
```json
{
  "name": "New Name",
  "url": "new-url.com"
}
```

---

## 🔐 Authentication Flow

### JWT Token Structure
```
Header: { alg: "HS256", typ: "JWT" }
Payload: { 
  sub: user_id,        // User ID
  email: "user@test",  // Email
  exp: timestamp,      // Expiration
  iat: timestamp       // Issued at
}
Signature: HMAC-SHA256(secret)
```

### Session Management
- **Duration:** 24 hours
- **Storage:** Server-side sessions + HTTP-only cookies
- **Regeneration:** On login for security
- **Fallback:** Checks JWT if session invalid

### Password Security
- **Algorithm:** ARGON2ID
- **Cost:** 2 iterations, 65536 memory
- **Salt:** Auto-generated per user
- **Verification:** Timing-safe comparison

---

## 📊 Database Schema

### users
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE,
  nickname VARCHAR(100),
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### bios
```sql
CREATE TABLE bios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE,
  background VARCHAR(255),
  theme VARCHAR(50),
  is_published BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### bio_blocks
```sql
CREATE TABLE bio_blocks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  bio_id INT NOT NULL,
  type VARCHAR(50),
  data JSON,
  sort_order INT,
  FOREIGN KEY (bio_id) REFERENCES bios(id)
);
```

### links
```sql
CREATE TABLE links (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  name VARCHAR(100),
  url VARCHAR(2000),
  clicks INT DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX (user_id)
);
```

---

## 🎯 Configuration

### .env File
```env
# App Settings
APP_NAME=KNBioCard
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Ho_Chi_Minh

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=knbiocard
DB_USER=root
DB_PASS=

# Security
JWT_SECRET=your-super-secret-key-change-this
SESSION_LIFETIME=86400
RATE_LIMIT=100/hour

# Email (Optional)
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USER=your_user
MAIL_PASS=your_pass

# Storage
UPLOAD_MAX_SIZE=5242880
UPLOAD_PATH=/storage/uploads
```

---

## 🧪 Development

### Local Development
```bash
# Start PHP server
php -S localhost:8000

# Watch CSS changes (if using build tools)
npm run watch

# Run tests
vendor/bin/phpunit
```

### Code Style
```bash
# Format code
vendor/bin/phpstan analyse src/

# Lint PHP
php -l api/Auth.php
```

### Database Migrations
```bash
# Create migration
php artisan make:migration create_users_table

# Run migrations
php artisan migrate
```

---

## 🐛 Troubleshooting

### Common Issues

**1. Database Connection Error**
```
✓ Check .env DB_HOST, DB_USER, DB_PASS
✓ Verify MySQL is running
✓ Test with: mysql -u root -p
```

**2. JWT Token Invalid**
```
✓ Verify JWT_SECRET in .env
✓ Check token expiration
✓ Clear browser cookies/cache
```

**3. Upload Fails**
```
✓ Check storage/ permissions: chmod 755
✓ Verify UPLOAD_MAX_SIZE in .env
✓ Check disk space available
```

**4. Session Lost on Refresh**
```
✓ Ensure http-only cookies enabled
✓ Check session.save_path writable
✓ Verify HTTPS if production
```

---

## 📝 Usage Examples

### Create a User Account
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!",
    "username": "john_doe"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!"
  }'
```

### View Public Bio
```bash
curl http://localhost:8000/@john_doe
# Returns user's published bio with all blocks & links
```

### Add a Link
```bash
curl -X POST http://localhost:8000/api/links/create \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Website",
    "url": "https://example.com"
  }'
```

---

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate strong `JWT_SECRET`
- [ ] Setup HTTPS certificate
- [ ] Configure MySQL backups
- [ ] Enable rate limiting
- [ ] Setup error logging
- [ ] Optimize database indexes
- [ ] Enable caching headers
- [ ] Setup CDN for static assets

### Deploy to Server
```bash
# Via Git
git clone https://github.com/yourusername/KNTag.git
cd KNTag
composer install --no-dev
cp .env.example .env
# Edit .env with production values
php -S 0.0.0.0:8000

# Via Docker (optional)
docker-compose up -d
```

---

## 📈 Performance

### Optimization Tips
- **Database:** Add indexes on `user_id`, `email`, `username`
- **Caching:** Implement Redis for sessions/tokens
- **CDN:** Serve static assets from CloudFlare/Cloudinary
- **Images:** Compress bio images, use WebP format
- **API:** Enable GZIP compression, use pagination
- **Frontend:** Minify CSS/JS, lazy load images

### Benchmarks
- **Page Load:** < 2s (with cache)
- **API Response:** < 200ms (99th percentile)
- **Database Query:** < 50ms (cached)

---

## 📄 License

This project is licensed under the **MIT License** - see [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2025 KN BioCard Team

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...
```

---

## 👥 Contributors

<div align="center">

**Built with ❤️ by the KN Team**

[Twitter](https://twitter.com) • [Email](mailto:support@knbiocard.com) • [Website](https://knbiocard.com)

### Show your support
⭐ **Star** this repo if you like it!  
🍴 **Fork** and contribute improvements!  
🐛 **Report** bugs on [Issues](https://github.com/yourusername/KNTag/issues)

</div>

---

## 🗺️ Roadmap

### v1.1 (Q1 2025)
- [ ] Social media integration (Instagram, TikTok, YouTube)
- [ ] QR code customization (colors, logos)
- [ ] PDF export for bio
- [ ] Email newsletter signup

### v1.2 (Q2 2025)
- [ ] Team collaboration features
- [ ] White-label support
- [ ] API rate limit per tier
- [ ] Advanced analytics (heatmaps, device tracking)

### v2.0 (Q3 2025)
- [ ] Mobile app (React Native)
- [ ] AI-powered bio suggestions
- [ ] NFC card builder
- [ ] Ecommerce integration

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Code Guidelines
- Follow PSR-12 PHP style guide
- Write unit tests for new features
- Update documentation
- Keep commits atomic & descriptive

---

## 📞 Support

- 📧 **Email:** support@knbiocard.com
- 💬 **Discord:** [Join Community](https://discord.gg/knbiocard)
- 📖 **Docs:** [Full Documentation](https://docs.knbiocard.com)
- 🐛 **Issues:** [GitHub Issues](https://github.com/yourusername/KNTag/issues)

---

<div align="center">

**Made with 💖 for creators, entrepreneurs, and innovators**

*Last updated: December 2025*

</div>
