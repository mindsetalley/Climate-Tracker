# 🌍 Global Climate Change Tracker

A web platform that combines a climate news blog, an interactive 3D climate globe, country-level climate dashboards and a threatened-species tracker, with the aim of making climate information accessible and easier to understand.

> **Status:** Core build complete (news, accounts, admin, countries, globe, species). Climate figures and impact scores are **placeholder data** for now. The next phase replaces them with real sources and a documented index.

## Tech stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, vanilla JavaScript |
| Backend | PHP 8.2 (PDO) |
| Database | MySQL / MariaDB |
| 3D globe | [Globe.gl](https://globe.gl) 2.27.2 (via jsDelivr) |
| Charts | [Chart.js](https://www.chartjs.org) 4.4.1 (via jsDelivr) |
| Map data | Natural Earth 110m country boundaries (GeoJSON) |
| Local server | XAMPP |

## Features

**Public site**
- Responsive layout with shared header and footer; burger menu on narrow and half-screen windows
- Homepage with hero, climate snapshot stats, featured article, live mini-globe, threatened-species spotlight and latest news
- **News:** category filter, search and pagination, single article pages with related articles
- **Climate Tracker:** interactive 3D globe with real country borders coloured by impact score (green → red). Hover tooltips, click-to-select side panel, country search, deep links (`tracker.php?country=GHA`)
- **Countries:** searchable directory with continent filter and sorting; per-country dashboard with score, summary, temperature and rainfall charts, and documented climate events
- **Species:** searchable directory with category and IUCN status filters; detail pages with threats, habitat, population trend and a globe showing the species' range

**Accounts and security**
- Registration and login with `password_hash()` / `password_verify()`
- Dedicated session name (avoids clashes with other localhost projects), session ID regeneration at login
- CSRF protection on all state-changing forms
- Role-based access (`user` / `admin`), with the role **read from the database on every request**
- Admin upgrade through an access code (`auth/become_admin.php`): stored as a hash, rate-limited to 5 attempts per 15 minutes per account

**Admin panel** (`/admin/`)
- Dashboard with content counts
- Articles: create, edit, delete, image upload or URL, scheduled publishing, featured toggle
- Categories: add and delete
- Countries: edit score and summary, add and delete climate events
- Species: create, edit, delete, with threats and range locations entered line by line

## Project structure

```
climate-tracker/
├── admin/              Admin pages (dashboard, articles, categories, countries, species)
├── api/
│   └── countries.php   JSON feed used by the globe
├── assets/
│   ├── css/style.css
│   ├── data/countries.geojson    Country boundaries (Natural Earth)
│   ├── img/uploads/              Uploaded images (git-ignored)
│   └── js/
│       ├── main.js               Nav toggle
│       ├── globe.js              Reusable globe component
│       └── tracker.js            Tracker page logic
├── auth/               register, login, logout, become_admin
├── config/
│   ├── db.php                    DB credentials (git-ignored)
│   ├── db.example.php
│   ├── admin_pin.php             Admin code hash (git-ignored)
│   └── admin_pin.example.php
├── includes/           header, footer, auth, functions, article and species card partials
├── sql/
│   ├── schema.sql
│   ├── seed_articles.sql
│   ├── seed_countries.sql
│   └── seed_species.sql
├── article.php         Single article
├── news.php            News listing
├── countries.php       Country directory
├── country.php         Country dashboard
├── tracker.php         Full interactive globe
├── species.php         Species directory
├── species_view.php    Species detail
└── index.php           Homepage
```

## Setup

**Requirements:** XAMPP (or Apache + PHP 8.2+ + MySQL) and Git.

1. **Clone into your web root**
   ```bash
   git clone https://github.com/YOUR-USERNAME/climate-tracker.git
   ```
2. **Create the database.** Start Apache and MySQL, open phpMyAdmin, SQL tab, and run in order:
   1. `sql/schema.sql`
   2. `sql/seed_articles.sql` (optional)
   3. `sql/seed_countries.sql`
   4. `sql/seed_species.sql`

   The schema file does not include the `admin_attempts` table. Run this once as well:
   ```sql
   CREATE TABLE admin_attempts (
     id INT AUTO_INCREMENT PRIMARY KEY,
     user_id INT NOT NULL,
     attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   );
   ```
3. **Configure the DB connection.** Copy `config/db.example.php` to `config/db.php` and fill in your credentials.
4. **Set the admin access code.** Generate a hash for a long passphrase (e.g. a temporary `hash.php` containing `<?php echo password_hash('your-passphrase', PASSWORD_DEFAULT);`, then delete it). Copy `config/admin_pin.example.php` to `config/admin_pin.php` and paste the hash in single quotes.
5. **Check the base URL.** `BASE_URL` in `includes/functions.php` must match the folder name (default `/climate-tracker`).
6. **Download the map data** if `assets/data/countries.geojson` is missing:
   ```powershell
   curl.exe -L -o assets/data/countries.geojson https://raw.githubusercontent.com/vasturiano/globe.gl/master/example/datasets/ne_110m_admin_0_countries.geojson
   ```
7. **Open** `http://localhost/climate-tracker/`.

### Becoming an admin

Register an account, log in, then visit `/auth/become_admin.php` and enter the access code. The "Admin" link appears in the navigation afterwards.

## Database

| Table | Purpose |
|-------|---------|
| `users` | Accounts and roles |
| `admin_attempts` | Rate-limiting for the admin access code |
| `categories`, `articles` | News content |
| `countries` | Name, ISO codes, continent, coordinates, impact score, summary |
| `climate_data` | Indicator values by country and year (unique per country, indicator, year) |
| `climate_events` | Documented floods, droughts, cyclones, etc. |
| `sources` | Data source references |
| `species`, `species_threats`, `species_regions` | Species tracker and range markers for the globe |

## Data notes

- **Impact scores are placeholders.** They are illustrative values, not output from a real index.
- **Temperature and rainfall series are synthetic** (labelled "Placeholder (sample data)" on the dashboards).
- Seeded climate events are real, documented events.
- Species statuses follow the IUCN Red List as of seeding. Red List assessments change, so verify at [iucnredlist.org](https://www.iucnredlist.org).
- The impact score will become a **Climate Impact and Vulnerability Index** (exposure, vulnerability and adaptive capacity, normalised and weighted), with the methodology shown publicly.

## Security notes

- All database access uses PDO prepared statements
- All output is escaped (`e()` / `htmlspecialchars`); the globe's tooltips and panel escape or use `textContent`
- Passwords and the admin code are hashed, never stored in plain text
- Redirects after login are restricted to on-site paths
- Upload validation checks the real MIME type, enforces a size limit and renames files
- Credentials, the admin code hash and uploads are excluded from version control

## Known gaps

- `about.php` does not exist yet, so the About link in the nav returns a 404
- No password reset, email verification or account management page
- No automated tests

## Roadmap

- [x] Step 1: Setup, schema, DB connection
- [x] Step 2: Shared layout and homepage
- [x] Step 3: Authentication
- [x] Step 4: News listing, search, article pages
- [x] Step 5: Admin panel (articles, categories, uploads)
- [x] Step 6: Countries and climate data
- [x] Step 7: Interactive 3D globe
- [x] Step 8: Species tracker

**Next phase**
- [ ] Replace placeholder data with real sources (World Bank CCKP, Our World in Data, NASA, IUCN Red List)
- [ ] Build and document the Climate Impact and Vulnerability Index
- [ ] About and methodology page
- [ ] Country comparison
- [ ] Historical globe (year slider)
- [ ] Climate stats on the homepage driven by real data
- [ ] Polish: accessibility, loading states, SEO, error pages
- [ ] Stretch: AI climate assistant using verified sources
