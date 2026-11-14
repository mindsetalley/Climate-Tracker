# 🌍 Global Climate Change Tracker

A web platform that combines a climate news blog, an interactive 3D climate globe, country-level climate data and a threatened-species tracker, with the aim of making climate information accessible and easier to understand.

> **Status:** In development. Phase 1 (news blog and admin) is complete. The globe, country dashboards and species tracker are next.

## Tech stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, vanilla JavaScript |
| Backend | PHP 8+ (PDO) |
| Database | MySQL / MariaDB |
| Planned | Globe.gl (3D globe), Chart.js (charts) |
| Local server | XAMPP |

## Features so far

**Public site**
- Responsive layout with shared header and footer, mobile nav
- Homepage with hero, climate snapshot stats (placeholder figures), featured article and latest news
- News listing with category filter, search and pagination
- Single article pages with related articles

**Accounts**
- Registration and login with `password_hash()` / `password_verify()`
- Session handling with a dedicated session name and session ID regeneration at login
- CSRF protection on all forms
- Role-based access (`user` / `admin`)

**Admin panel** (`/admin/`)
- Dashboard with content counts
- Article create, edit and delete
- Image upload (JPG/PNG/WebP, max 2 MB, MIME-checked, randomised filenames) or image URL
- Scheduled publishing (future-dated articles stay hidden from the public site)
- Featured-article toggle
- Category management

## Project structure

```
climate-tracker/
├── admin/            Admin pages (articles, categories, dashboard)
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── img/uploads/  Uploaded images (git-ignored)
├── auth/             register, login, logout
├── config/
│   ├── db.php        Real DB credentials (git-ignored)
│   └── db.example.php
├── includes/         header, footer, auth, helper functions, article card partial
├── sql/
│   ├── schema.sql
│   └── seed_articles.sql
├── article.php       Single article view
├── news.php          News listing
└── index.php         Homepage
```

## Setup

**Requirements:** XAMPP (or any Apache + PHP 8+ + MySQL stack) and Git.

1. **Clone into your web root**
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/YOUR-USERNAME/climate-tracker.git
   ```
2. **Create the database.** Start Apache and MySQL, open phpMyAdmin → SQL tab, and run `sql/schema.sql`. Optionally run `sql/seed_articles.sql` for sample articles.
3. **Configure the connection.** Copy `config/db.example.php` to `config/db.php` and enter your credentials.
4. **Check the base URL.** `BASE_URL` in `includes/functions.php` must match your folder name (default `/climate-tracker`).
5. **Create the uploads folder.** `assets/img/uploads/` is created automatically on first upload, or add it yourself.
6. **Open the site** at `http://localhost/climate-tracker/`.

### Creating an admin account

Register through `/auth/register.php`, then promote yourself in phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```

Log out and back in for the role to take effect.

## Database

Tables are defined in `sql/schema.sql`:

| Table | Purpose | Used yet? |
|-------|---------|-----------|
| `users` | Accounts and roles | ✅ |
| `categories` | Article categories | ✅ |
| `articles` | News content | ✅ |
| `countries` | Country info, ISO codes, coordinates, impact score | Step 6 |
| `climate_data` | Indicator values by country and year | Step 6 |
| `climate_events` | Documented floods, droughts, etc. | Step 6 |
| `sources` | Data source references | Step 6 |
| `species`, `species_threats`, `species_regions` | Species tracker and globe highlights | Step 8 |

## Security notes

- All database access uses PDO prepared statements
- All output is escaped with `e()` (`htmlspecialchars`)
- Passwords are hashed, never stored in plain text
- Login redirects are restricted to on-site paths
- Upload validation checks the real MIME type, not the file extension
- Credentials and uploads are excluded from version control

## Roadmap

- [x] **Step 1:** Setup, schema, DB connection
- [x] **Step 2:** Shared layout and homepage
- [x] **Step 3:** Authentication
- [x] **Step 4:** News listing, search, article pages
- [x] **Step 5:** Admin panel
- [ ] **Step 6:** Countries and climate data
- [ ] **Step 7:** Interactive 3D globe and country dashboard
- [ ] **Step 8:** Species tracker
- [ ] **Later:** country comparison, historical globe, real data-source integration, AI assistant

## Notes

- Climate figures on the homepage are **placeholders** until real data sources are integrated.
- Impact scores will come from a documented composite index (Climate Impact and Vulnerability Index), with the methodology shown to users.
