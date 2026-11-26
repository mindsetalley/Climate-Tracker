<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$counts = [
    'Countries' => $pdo->query('SELECT COUNT(*) FROM countries')->fetchColumn(),
    'Species'   => $pdo->query('SELECT COUNT(*) FROM species')->fetchColumn(),
    'Articles'  => $pdo->query('SELECT COUNT(*) FROM articles WHERE published_at <= NOW()')->fetchColumn(),
];

$pageTitle = 'About';
require __DIR__ . '/includes/header.php';
?>
<section class="container about">
  <h1>About Climate Tracker</h1>
  <p class="lead">Climate Tracker is an interactive platform for understanding how climate change is affecting different parts of the world, and the wildlife that depends on them.</p>

  <div class="stats">
    <?php foreach ($counts as $label => $n): ?>
      <div class="stat"><span class="num"><?= (int)$n ?></span><span><?= e($label) ?></span></div>
    <?php endforeach; ?>
  </div>

  <h2>What you can do here</h2>
  <ul>
    <li><a href="<?= url('news.php') ?>">Read climate news</a> and explainers, filtered by topic.</li>
    <li><a href="<?= url('tracker.php') ?>">Explore the 3D globe</a> and click a country to see its climate profile.</li>
    <li><a href="<?= url('countries.php') ?>">Browse countries</a> and view temperature, rainfall and documented climate events.</li>
    <li><a href="<?= url('species.php') ?>">Discover threatened species</a> and see where they live.</li>
  </ul>

  <h2 id="methodology">Methodology</h2>
  <p>The globe colours each country by a 0–100 <strong>Climate Impact and Vulnerability</strong> score, from green (low) to red (very high).</p>
<div class="card note-box">
<strong>How the score works:</strong> each country's score is its ND-GAIN vulnerability value for the latest year with broad coverage, scaled so the least vulnerable country in the dataset is 0 and the most vulnerable is 100. It shows vulnerability relative to other countries, not an absolute measure. Species entries and the seed articles are still sample content.
</div>

  <h3>Exposure, vulnerability and damage are different things</h3>
  <p>A country can face frequent hazards but cope well because of strong infrastructure and institutions, while another with fewer hazards may suffer far more. For that reason the platform aims to show a vulnerability-based index rather than a simple "how much climate change" score, and to keep three ideas separate:</p>
  <ul>
    <li><strong>Exposure:</strong> how strongly a country is hit by hazards such as heat, floods, drought and sea-level rise.</li>
    <li><strong>Vulnerability:</strong> how much those hazards harm people and ecosystems, given sensitivity and the ability to adapt.</li>
    <li><strong>Damage:</strong> losses that have actually occurred, such as documented events.</li>
  </ul>

  <h3>Score bands</h3>
  <div class="legend about-legend">
    <?php foreach ([[0, 20], [21, 40], [41, 60], [61, 80], [81, 100]] as [$lo, $hi]): [$label, $color] = score_level($lo); ?>
      <span style="--c:<?= $color ?>"><?= e($label) ?> (<?= $lo ?>–<?= $hi ?>)</span>
    <?php endforeach; ?>
  </div>

  <h2>Data sources</h2>
  <p>The platform is designed around open, citable sources. Each data point should show where it came from.</p>
  <ul>
    <li><a href="https://gain.nd.edu/our-work/country-index/" target="_blank" rel="noopener">ND-GAIN Country Index</a>: country vulnerability and readiness.</li>
    <li><a href="https://climateknowledgeportal.worldbank.org" target="_blank" rel="noopener">World Bank Climate Change Knowledge Portal</a>: country-level climate information.</li>
    <li><a href="https://ourworldindata.org" target="_blank" rel="noopener">Our World in Data</a>: climate datasets and historical statistics.</li>
    <li><a href="https://climate.nasa.gov" target="_blank" rel="noopener">NASA Climate</a>: global temperature and sea-level information.</li>
    <li><a href="https://www.iucnredlist.org" target="_blank" rel="noopener">IUCN Red List</a>: species conservation status.</li>
    <li>World Bank Climate Change Knowledge Portal, observed data from CRU TS4.10: annual average temperature and rainfall by country, 1950 onwards.</li>
  </ul>
  <p class="note">Listing a source here means it is planned or in use for the platform, not that all of its data is already included.</p>

  <h2>Limitations</h2>
  <ul>
    <li>Country-level scores hide differences inside countries. Coastal and inland regions, or cities and rural areas, can face very different risks.</li>
    <li>Conservation statuses change as assessments are updated. Always check the Red List for the current one.</li>
    <li>This is an educational project, not an official or scientific authority.</li>
  </ul>

  <h2>Contact and corrections</h2>
  <p>Spotted an error or want to suggest a source? Get in touch through the project's GitHub repository.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
