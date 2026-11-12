CREATE DATABASE IF NOT EXISTS climate_tracker
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE climate_tracker;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE,
  slug VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  author_id INT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  summary VARCHAR(300),
  content MEDIUMTEXT NOT NULL,
  image_url VARCHAR(255),
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
  FULLTEXT KEY ft_articles (title, summary, content)
);

CREATE TABLE countries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  iso_a2 CHAR(2) NOT NULL UNIQUE,
  iso_a3 CHAR(3) NOT NULL UNIQUE,
  continent VARCHAR(30),
  latitude DECIMAL(8,5),
  longitude DECIMAL(8,5),
  impact_score TINYINT UNSIGNED NULL,   -- 0-100 composite index
  summary TEXT
);

CREATE TABLE sources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  url VARCHAR(255)
);

CREATE TABLE climate_data (
  id INT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  source_id INT NULL,
  indicator VARCHAR(60) NOT NULL,       -- e.g. 'avg_temp_c', 'rainfall_mm'
  year SMALLINT NOT NULL,
  value DECIMAL(12,4) NOT NULL,
  UNIQUE KEY uq_country_ind_year (country_id, indicator, year),
  FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE SET NULL
);

CREATE TABLE climate_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  event_type ENUM('flood','drought','heatwave','cyclone','wildfire','sea_level','other') NOT NULL,
  event_date DATE,
  description TEXT,
  FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
);

CREATE TABLE species (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  scientific_name VARCHAR(150),
  category ENUM('mammal','bird','marine','reptile','amphibian','insect','plant') NOT NULL,
  conservation_status ENUM('LC','NT','VU','EN','CR','EW','EX') NOT NULL,
  habitat VARCHAR(255),
  population_trend ENUM('increasing','stable','decreasing','unknown') DEFAULT 'unknown',
  image_url VARCHAR(255)
);

CREATE TABLE species_threats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  species_id INT NOT NULL,
  threat_type VARCHAR(80) NOT NULL,
  description TEXT,
  FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE CASCADE
);

-- For highlighting a species' range on the globe
CREATE TABLE species_regions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  species_id INT NOT NULL,
  latitude DECIMAL(8,5) NOT NULL,
  longitude DECIMAL(8,5) NOT NULL,
  label VARCHAR(80),
  FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE CASCADE
);

INSERT INTO categories (name, slug) VALUES
('Global Warming','global-warming'),
('Deforestation','deforestation'),
('Rising Sea Levels','rising-sea-levels'),
('Extreme Weather','extreme-weather'),
('Wildlife and Biodiversity','wildlife-biodiversity'),
('Climate Policies','climate-policies'),
('Renewable Energy','renewable-energy'),
('Environmental Conservation','environmental-conservation');
