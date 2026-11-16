INSERT INTO sources (name, url) VALUES ('Placeholder (sample data)', NULL);

INSERT INTO countries (name, iso_a2, iso_a3, continent, latitude, longitude, impact_score) VALUES
('Ghana','GH','GHA','Africa',7.95,-1.02,58),
('Nigeria','NG','NGA','Africa',9.08,8.68,70),
('Kenya','KE','KEN','Africa',0.02,37.91,66),
('Somalia','SO','SOM','Africa',5.15,46.20,90),
('Egypt','EG','EGY','Africa',26.82,30.80,62),
('South Africa','ZA','ZAF','Africa',-30.56,22.94,52),
('Ethiopia','ET','ETH','Africa',9.15,40.49,74),
('Senegal','SN','SEN','Africa',14.50,-14.45,64),
('Mozambique','MZ','MOZ','Africa',-18.67,35.53,85),
('Morocco','MA','MAR','Africa',31.79,-7.09,55),
('Tanzania','TZ','TZA','Africa',-6.37,34.89,63),
('Niger','NE','NER','Africa',17.61,8.08,87),
('Bangladesh','BD','BGD','Asia',23.68,90.36,92),
('India','IN','IND','Asia',20.59,78.96,78),
('Pakistan','PK','PAK','Asia',30.38,69.35,84),
('China','CN','CHN','Asia',35.86,104.20,55),
('Indonesia','ID','IDN','Asia',-0.79,113.92,68),
('Philippines','PH','PHL','Asia',12.88,121.77,82),
('Vietnam','VN','VNM','Asia',14.06,108.28,76),
('Thailand','TH','THA','Asia',15.87,100.99,62),
('Japan','JP','JPN','Asia',36.20,138.25,38),
('Maldives','MV','MDV','Asia',3.20,73.22,88),
('Nepal','NP','NPL','Asia',28.39,84.12,75),
('Saudi Arabia','SA','SAU','Asia',23.89,45.08,60),
('Germany','DE','DEU','Europe',51.17,10.45,28),
('United Kingdom','GB','GBR','Europe',55.38,-3.44,26),
('France','FR','FRA','Europe',46.23,2.21,30),
('Norway','NO','NOR','Europe',60.47,8.47,15),
('Netherlands','NL','NLD','Europe',52.13,5.29,33),
('Spain','ES','ESP','Europe',40.46,-3.75,42),
('Italy','IT','ITA','Europe',41.87,12.57,40),
('United States','US','USA','North America',37.09,-95.71,44),
('Canada','CA','CAN','North America',56.13,-106.35,36),
('Mexico','MX','MEX','North America',23.63,-102.55,56),
('Haiti','HT','HTI','North America',18.97,-72.29,86),
('Brazil','BR','BRA','South America',-14.24,-51.93,60),
('Peru','PE','PER','South America',-9.19,-75.02,64),
('Argentina','AR','ARG','South America',-38.42,-63.62,45),
('Chile','CL','CHL','South America',-35.68,-71.54,50),
('Colombia','CO','COL','South America',4.57,-74.30,58),
('Australia','AU','AUS','Oceania',-25.27,133.78,54),
('Papua New Guinea','PG','PNG','Oceania',-6.31,143.96,72),
('Fiji','FJ','FJI','Oceania',-17.71,178.07,80),
('New Zealand','NZ','NZL','Oceania',-40.90,174.89,34);

UPDATE countries SET summary = 'Ghana faces coastal erosion and flooding along its southeastern coast, including the Volta delta, while the north is increasingly exposed to drought and heat that affect farming.' WHERE iso_a3 = 'GHA';
UPDATE countries SET summary = 'Low-lying and densely populated, Bangladesh is highly exposed to river and coastal flooding, tropical cyclones and saltwater intrusion in its coastal regions.' WHERE iso_a3 = 'BGD';
UPDATE countries SET summary = 'Most of the Maldives sits barely above sea level, making sea-level rise and coastal flooding a long-term threat to its islands.' WHERE iso_a3 = 'MDV';
UPDATE countries SET summary = 'Pakistan is exposed to extreme heat, glacier melt in the north and severe monsoon flooding along the Indus basin.' WHERE iso_a3 = 'PAK';
UPDATE countries SET summary = 'Norway is seeing glacier retreat and rapid Arctic warming, particularly in Svalbard, though its capacity to adapt is high.' WHERE iso_a3 = 'NOR';

-- Documented events
INSERT INTO climate_events (country_id, event_type, event_date, description)
SELECT id, 'flood', '2022-08-01', 'Catastrophic monsoon floods affected millions of people across Pakistan.' FROM countries WHERE iso_a3='PAK';
INSERT INTO climate_events (country_id, event_type, event_date, description)
SELECT id, 'cyclone', '2019-03-14', 'Cyclone Idai made landfall near Beira, causing widespread flooding and loss of life.' FROM countries WHERE iso_a3='MOZ';
INSERT INTO climate_events (country_id, event_type, event_date, description)
SELECT id, 'wildfire', '2019-12-01', 'The "Black Summer" bushfire season burned millions of hectares in south-eastern Australia.' FROM countries WHERE iso_a3='AUS';
INSERT INTO climate_events (country_id, event_type, event_date, description)
SELECT id, 'flood', '2021-07-14', 'Heavy rainfall caused deadly flash floods in western Germany, notably in the Ahr valley.' FROM countries WHERE iso_a3='DEU';
INSERT INTO climate_events (country_id, event_type, event_date, description)
SELECT id, 'cyclone', '2013-11-08', 'Typhoon Haiyan struck the central Philippines, one of the strongest tropical cyclones recorded at landfall.' FROM countries WHERE iso_a3='PHL';

-- SAMPLE climate series (1990-2020): synthetic, to be replaced with real data
INSERT INTO climate_data (country_id, source_id, indicator, year, value)
SELECT c.id, (SELECT id FROM sources WHERE name='Placeholder (sample data)'), 'avg_temp_c', yr.y,
       ROUND(27 - ABS(c.latitude) * 0.35 + (yr.y - 1990) * 0.025 + (MOD(yr.y * 7 + c.id * 3, 10) - 5) * 0.04, 2)
FROM countries c
CROSS JOIN (
  SELECT 1990 + t.d * 10 + o.d AS y
  FROM (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) t
  CROSS JOIN (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
              UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) o
) yr
WHERE yr.y <= 2020;

INSERT INTO climate_data (country_id, source_id, indicator, year, value)
SELECT c.id, (SELECT id FROM sources WHERE name='Placeholder (sample data)'), 'rainfall_mm', yr.y,
       600 + MOD(yr.y * 37 + c.id * 53, 400)
FROM countries c
CROSS JOIN (
  SELECT 1990 + t.d * 10 + o.d AS y
  FROM (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) t
  CROSS JOIN (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
              UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) o
) yr
WHERE yr.y <= 2020;+
