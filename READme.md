# 🌍 Global Climate Change Tracker

A web platform that combines a climate news blog, an interactive 3D climate globe with a year-by-year timeline, country-level dashboards built on real vulnerability data, and a threatened-species tracker. The aim is to make climate information accessible and easier to understand.

> **Status:** Core build complete. Country vulnerability data is real (ND-GAIN). Species entries, the homepage snapshot figures and the news seed articles are still sample content, and temperature and rainfall data have not been added yet.

## Tech stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, vanilla JavaScript |
| Backend | PHP 8.2 (PDO) |
| Database | MySQL / MariaDB |
| 3D globe | [Globe.gl](https://globe.gl) 2.27.2 (via jsDelivr) |
| Charts | [Chart.js](https://www.chartjs.org) 4.4.1 (via jsDelivr) |
| Map data | Natural Earth 110m country boundaries (GeoJSON) |
| Climate data | ND-GAIN Country Index (CSV, imported with a PHP CLI script) |
| Local server | XAMPP |

## Features

**Public site**
- Responsive layout, burger menu on narrow and half-screen windows
- Homepage with hero, snapshot stats (placeholder figures), featured article, mini globe, species spotlight and latest news
- **News:** category filter, search, pagination, article pages with related articles
- **Climate Tracker:** 3D globe with real country borders coloured by vulnerability score (green to red); hover tooltips, click-to-select panel, country search, deep links (`tracker.php?country=GHA`)
  - **Year slider (1995–2024)** with play/pause timeline; the colour scale is fixed to the latest year so years are comparable
  - Coloured dots for small countries and island states too small for the map outline
- **Countries:** searchable directory with continent filter and sorting; per-country dashboard with the score, vulnerability / readiness / ND-GAIN index charts (1995–2024), a breakdown of what drives the score (exposure, sensitivity, low adaptive capacity, and sectors) and documented climate events
- **Species:** searchable directory with category and IUCN status filters; detail pages with threats, trend and a globe showing the range
- **About and methodology** page

**Accounts and security**
- Registration and login with `password_hash()` / `password_verify()`
- Dedicated session name, session ID regeneration at login
- CSRF protection on all state-changing forms
- Roles (`user` / `admin`) read from the database on every request
- Admin upgrade through an access code (`auth/become_admin.php`): stored as a hash, rate-limited to 5 attempts per 15 minutes per account

**Admin panel** (`/admin/`)
- Articles: create, edit, delete, image upload or URL, scheduled publishing, featured toggle
- Categories, countries (summary, events) and species (threats, range locations)

## How the score works

Each country's score is its **ND-GAIN vulnerability** value, scaled 0–100 across countries using the latest year's minimum and maximum. 0 is the least vulnerable country in the dataset and 100 the most. It is a relative measure. Higher always means more vulnerable, except for the readiness and overall ND-GAIN Index charts, where higher means better prepared.

Components shown on country pages (all "higher = more vulnerable"): exposure, sensitivity, low adaptive capacity, and the food, health, ecosystems, human habitat and infrastructure sectors.

> Notre Dame Global Adaptation Initiative Country Index (ND-GAIN), University of Notre Dame.

## Project structure

```
climate-tracker/
├── admin/              Admin pages
├── api/
│   ├── countries.php   Country data and colours for the globe
│   └── scores.php      Scores per country per year (for the slider)
├── assets/
│   ├── css/style.css
│   ├── data/countries.geojson    Country boundaries
│   ├── img/uploads/              Uploaded images (git-ignored)
│   └── js/                       main.js, globe.js, tracker.js
├── auth/               register, login, logout, become_admin
├── config/             db.php and admin_pin.php (git-ignored), plus .example versions
├── data/raw/nd-gain/   Downloaded ND-GAIN CSVs (git-ignored, not served)
├── includes/           header, footer, auth, functions, card partials
├── scripts/
│   └── import_nd_gain.php        CLI importer for ND-GAIN data
├── sql/                schema and seed files
└── *.php               Public pages
```

## Setup

**Requirements:** XAMPP (or Apache + PHP 8.2+ + MySQL) and Git.

1. **Clone into your web root.**
2. **Create the database.** In phpMyAdmin, run `sql/schema.sql`, then `sql/seed_articles.sql` (optional) and `sql/seed_species.sql`. Also run these once:
   ```sql
   CREATE TABLE admin_attempts (
     id INT AUTO_INCREMENT PRIMARY KEY,
     user_id INT NOT NULL,
     attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   );
   ALTER TABLE countries
     MODIFY iso_a2 CHAR(2) NULL,
     ADD COLUMN score_year SMALLINT NULL AFTER impact_score;
   ```
3. **Copy `config/db.example.php` to `config/db.php`** and add your credentials.
4. **Set the admin access code:** generate a hash with `password_hash()` and put it in `config/admin_pin.php`.
5. **Check `BASE_URL`** in `includes/functions.php`.
6. **Map file:** make sure `assets/data/countries.geojson` exists (Natural Earth 110m, from the Globe.gl examples).
7. **Import the real data:**
   1. Download the ND-GAIN data from https://gain.nd.edu/our-work/country-index/download-data/
   2. Extract it so that `data/raw/nd-gain/gain/gain.csv`, `.../vulnerability/` and `.../readiness/` exist. Delete any `__MACOSX` folder and `._*` files.
   3. Run `php scripts/import_nd_gain.php`
   4. Run the `UPDATE countries SET ... latitude/longitude` statements for the small countries that have no map polygon (see the project notes), then check `SELECT name FROM countries WHERE latitude IS NULL;` returns nothing.
8. **Open** `http://localhost/climate-tracker/`.

The importer is safe to re-run (it upserts). It reads CSV files with any line-ending style, rescales 0–1 files to 0–100, adds missing countries using the map file, and recalculates the scores.

### Becoming an admin

Register, log in, then visit `/auth/become_admin.php` and enter the access code.

## Database

| Table | Purpose |
|-------|---------|
| `users`, `admin_attempts` | Accounts, roles, rate limiting |
| `categories`, `articles` | News |
| `countries` | Name, ISO codes, continent, coordinates, impact score and its year, summary |
| `climate_data` | Indicator values by country and year (14 ND-GAIN indicators, 1995–2024) |
| `climate_events` | Documented floods, droughts, cyclones, etc. |
| `sources` | Data source references |
| `species`, `species_threats`, `species_regions` | Species tracker and range markers |

## Data notes

- **Real:** ND-GAIN vulnerability, readiness, overall index, components and sectors, 1995–2024.
- **Sample content:** species entries (statuses follow the IUCN Red List as of seeding, so verify them at [iucnredlist.org](https://www.iucnredlist.org)), homepage snapshot figures and seed articles. Seeded climate events are real, documented events but only cover a few countries.
- **Not yet included:** temperature and rainfall time series.
- Scores describe **relative vulnerability**, not damage or absolute climate change. They are country-level and hide differences inside countries.

## Security notes

- PDO prepared statements everywhere
- Output escaped with `e()`; globe tooltips and panels escape or use `textContent`
- Passwords and the admin code are hashed
- Redirects restricted to on-site paths
- Uploads checked by real MIME type, size-limited and renamed
- Credentials, the admin code hash, uploads and raw data files are excluded from git (`data/raw/` is also outside the web-served `assets/` folder)

## Known gaps

- Temperature and rainfall charts (removed along with the placeholder data)
- Homepage snapshot figures are hard-coded
- No password reset, email verification or account page
- The 110m map omits some small countries, shown as dots instead
- No automated tests

## Roadmap

- [x] Steps 1–8: layout, auth, news, admin, countries, globe, species
- [x] About and methodology page
- [x] Real ND-GAIN data, score breakdown, island markers
- [x] Year slider and timeline playback

**Next**
- [ ] Real temperature and rainfall (World Bank CCKP, CRU data)
- [ ] Homepage snapshot driven by real data
- [ ] Country comparison
- [ ] Verified species data (IUCN Red List)
- [ ] Polish: accessibility, loading states, SEO, error pages
- [ ] Stretch: AI climate assistant using verified sources
