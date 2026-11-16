INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Polar Bear','Ursus maritimus','mammal','VU','Arctic sea ice and coasts','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Sea-ice loss','Shrinking, earlier-melting sea ice reduces access to seals, the bears'' main prey.'),
(@s,'Longer fasting periods','Longer ice-free seasons force bears to fast on land, lowering cub survival.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,75.0,-100.0,'Canadian Arctic'),(@s,80.0,20.0,'Svalbard and Barents Sea'),(@s,71.0,-150.0,'Beaufort Sea');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Emperor Penguin','Aptenodytes forsteri','bird','NT','Antarctic coast and sea ice','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Sea-ice breakup','Colonies breed on stable sea ice. Early breakup before chicks fledge can wipe out a whole season''s young.'),
(@s,'Prey shifts','Warming oceans and reduced sea ice affect the availability of krill and fish.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,-75.0,0.0,'Weddell Sea'),(@s,-68.0,100.0,'East Antarctica'),(@s,-77.5,-165.0,'Ross Sea');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Leatherback Turtle','Dermochelys coriacea','reptile','VU','Tropical beaches and open ocean','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Hotter nesting beaches','Warmer sand skews hatchling sex ratios and can reduce hatching success.'),
(@s,'Sea-level rise','Rising seas and erosion flood or wash away nesting beaches.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,5.5,-53.5,'French Guiana and Suriname'),(@s,5.7,0.6,'Ghana coast'),(@s,-0.8,8.8,'Gabon');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Bornean Orangutan','Pongo pygmaeus','mammal','CR','Lowland rainforest and peat swamp, Borneo','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Forest loss','Deforestation destroys the rainforest canopy the species depends on.'),
(@s,'Drought and fire','Prolonged dry spells make peatland fires more frequent and severe.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,0.5,114.0,'Borneo interior'),(@s,1.5,110.5,'Sarawak'),(@s,-1.5,112.0,'Central Kalimantan');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Staghorn Coral','Acropora cervicornis','marine','CR','Shallow Caribbean reefs','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Ocean warming and bleaching','Marine heatwaves cause bleaching and mass mortality of coral colonies.'),
(@s,'Ocean acidification','Lower seawater pH makes it harder for corals to build their skeletons.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,24.9,-80.6,'Florida Keys'),(@s,17.3,-87.8,'Mesoamerican Reef'),(@s,18.2,-77.3,'Jamaica');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Golden Toad','Incilius periglenes','amphibian','EX','Monteverde cloud forest, Costa Rica','unknown');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Climate-linked decline','Last seen in 1989. Many researchers link its disappearance to climate-driven drying of its cloud-forest habitat, alongside disease.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,10.3,-84.8,'Monteverde, Costa Rica');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('African Savanna Elephant','Loxodonta africana','mammal','EN','Savannas and woodlands of sub-Saharan Africa','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Drought','More frequent droughts reduce water and forage, raising mortality and human-elephant conflict.'),
(@s,'Habitat loss','Habitat loss and fragmentation compound climate stress.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,-2.7,37.0,'Amboseli and Kilimanjaro'),(@s,-19.0,23.5,'Okavango and Chobe'),(@s,-24.0,31.5,'Greater Kruger');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Monarch Butterfly (migratory)','Danaus plexippus plexippus','insect','EN','North American meadows and oyamel fir forests','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Extreme weather','Storms, heat and drought on the migration route and at overwintering sites raise mortality.'),
(@s,'Shifting seasons','Warming can disrupt the timing of migration and of the milkweed growth that caterpillars rely on.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,19.6,-100.3,'Overwintering forests, Mexico'),(@s,40.0,-95.0,'Central US breeding range'),(@s,45.0,-78.0,'Great Lakes region');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Koala','Phascolarctos cinereus','mammal','VU','Eucalypt forests, eastern Australia','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Bushfires','Hotter, drier conditions increase fire frequency and intensity. The 2019-20 fires burned large areas of habitat.'),
(@s,'Heat and drought','Heatwaves and drought reduce leaf moisture and can cause heat stress.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,-28.0,153.0,'South-east Queensland and northern NSW'),(@s,-37.5,145.0,'Victoria');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Quiver Tree','Aloidendron dichotomum','plant','VU','Arid regions of Namibia and South Africa','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Rising temperatures and drought','Populations at the warm, dry edge of its range have declined, and its range is shifting poleward.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,-28.5,17.5,'Northern Cape, South Africa'),(@s,-26.5,17.0,'Southern Namibia');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Akikiki','Oreomystis bairdi','bird','CR','Highland forest, Kauai, Hawaii','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Avian malaria','Warming lets disease-carrying mosquitoes move to higher elevations where these birds once found refuge.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,22.1,-159.5,'Alakai Plateau, Kauai');

INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend)
VALUES ('Atlantic Puffin','Fratercula arctica','bird','VU','North Atlantic coasts and open sea','decreasing');
SET @s = LAST_INSERT_ID();
INSERT INTO species_threats (species_id, threat_type, description) VALUES
(@s,'Prey decline','Warming seas shift the distribution of sand eels and other fish that puffins feed their chicks.'),
(@s,'Extreme weather','Severe storms in winter and the breeding season can cause mass mortality.');
INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES
(@s,64.0,-20.0,'Iceland'),(@s,67.5,13.0,'Lofoten, Norway'),(@s,56.2,-2.5,'Scotland coast');
