# Acharya Books — Session Checkpoint (Saved)

**Last saved:** May 16, 2026  
**Project folder:** `/root/acharya-books/`  
**Stack:** Plain PHP + MySQL (no Laravel / no `artisan`)

---

## ✅ Work completed (save point)

### Public frontend (classical UI)
- **Home grid** — `index.php` → `HomeController` → all active courses with `image_path` thumbnails
- **Learn hub** — `learn.php?course={slug}` → sub-course card matrix
- **Layout** — `includes/public/layout_start.php`, `layout_end.php`, `assets/css/classical.css`
- **Noto Sans Telugu**, pure white `#FFFFFF`, borders `#E3E6F0`
- **Media auto-sync** — `catalog_media.php` + `assets/js/classical-media-poll.js` (polls every ~15s when admin updates images)

### Auth & student analytics
- **Unified login** — `login.php` (admin → `admin/dashboard.php`, student → `dashboard.php`)
- **Register** — `register.php` (name, mobile, email, password)
- **Student dashboard** — `dashboard.php` (subscriptions, study progress, exam charts)
- **Study tracking** — `topic-notes.php` records progress via `StudentAnalyticsRepository`

### Admin
- **Content Manager** (4-tier) — `admin/dashboard.php?view=content`
- **Site logo manager** — Admin Overview: circular (+) upload → `platform_settings.site_logo_path`
- **Course/sub-course images** — Content Manager `image_path` fields

### Controllers & models
- `controllers/HomeController.php`, `HeaderController.php`, `UserController.php`
- `models/PlatformRepository.php`, `StudentAnalyticsRepository.php`
- `includes/public_site_helpers.php` — `public_media_url()`, cache versions

### Database migrations (run in order when DB is up)
```bash
cd /root/acharya-books
php database/migrate_dynamic_hierarchy.php      # if fresh DB
php database/migrate_content_manager.php
php database/migrate_content_manager_v2.php
php database/update_lms_content_core.php
php database/update_lms_master_v3.php             # image_path columns
php database/update_frontend_user_and_media_core.php  # platform_settings, topic_study_progress
```

---

## 🚀 Resume after break

### 1. Start server
```bash
cd /root/acharya-books
php -S 0.0.0.0:8081 -t .
```

### 2. Open in browser
| Page | URL |
|------|-----|
| Home | http://localhost:8081/index.php |
| Learn (example) | http://localhost:8081/learn.php?course=tg-tet |
| Login | http://localhost:8081/login.php |
| Register | http://localhost:8081/register.php |
| Student dashboard | http://localhost:8081/dashboard.php |
| Admin | http://localhost:8081/admin/dashboard.php |
| Content Manager | http://localhost:8081/admin/dashboard.php?view=content |
| Logo upload | Admin → Dashboard Overview (top branding card) |

### 3. MySQL config
Copy `config/database.local.php.example` → `config/database.local.php` if needed.

---

## ✅ Resumed (May 16, 2026)
- **Classical layout** — `course.php`, `subject.php`, `exams.php` → `CourseController` + `includes/public/views/*`
- **Media poll** — sub-course thumbnails on course overview (`view=course`)

## ⏳ Optional next steps
- Migrate `sub_course.php`, `topic-notes.php`, `exam.php` to classical layout
- Wire more granular study progress (scroll % on notes)
- Production deploy + `.env` / HTTPS

---

## 📁 Key files map

```
acharya-books/
├── index.php, learn.php, login.php, register.php, dashboard.php
├── catalog_media.php
├── controllers/     HomeController, HeaderController, UserController
├── models/          PlatformRepository, StudentAnalyticsRepository, …
├── includes/public/ layout + views (home_grid, learn_grid, student_dashboard)
├── includes/admin/  content_manager, overview (logo), actions.php
├── assets/css/classical.css
├── assets/js/classical-media-poll.js
└── database/        migrations listed above
```

---

## 💬 Quick prompt when you reopen

> "Acharya Books checkpoint నుండి కొనసాగించు — classical UI + admin image sync పూర్తి చేసిన స్టేట్."

Git: project initialized with commit **"checkpoint: classical frontend, auth, analytics, logo manager"** (see `git log`).
