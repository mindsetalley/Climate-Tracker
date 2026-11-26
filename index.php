<?php
require __DIR__ . '/config/db.php';
$pageTitle = 'Understanding Our Changing Planet';

$featured = $pdo->query(
    'SELECT a.title, a.slug, a.summary, a.image_url, a.published_at, c.name AS category
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     WHERE a.is_featured = 1 ORDER BY a.published_at DESC LIMIT 1'
)->fetch();

$latest = $pdo->query(
    'SELECT a.title, a.slug, a.summary, a.image_url, a.published_at, c.name AS category
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     ORDER BY a.published_at DESC LIMIT 6'
)->fetchAll();

$spotlight = $pdo->query(
    "SELECT id, name, scientific_name, category, conservation_status, habitat, image_url
     FROM species
     ORDER BY FIELD(conservation_status,'CR','EN','VU','NT','LC','EW','EX'), RAND() LIMIT 4"
)->fetchAll();

// Snapshot figures computed from imported data
$maxY = (int)$pdo->query("SELECT MAX(year) FROM climate_data WHERE indicator = 'avg_temp_c'")->fetchColumn();
$warming = null;
if ($maxY) {
    $from = $maxY - 9;
    $warming = $pdo->query(
        "SELECT AVG(r.v - b.v) FROM
           (SELECT country_id, AVG(value) v FROM climate_data
            WHERE indicator = 'avg_temp_c' AND year BETWEEN $from AND $maxY GROUP BY country_id) r
         JOIN
           (SELECT country_id, AVG(value) v FROM climate_data
            WHERE indicator = 'avg_temp_c' AND year BETWEEN 1950 AND 1959 GROUP BY country_id) b
         USING (country_id)"
    )->fetchColumn();
}
$scored    = (int)$pdo->query('SELECT COUNT(*) FROM countries WHERE impact_score IS NOT NULL')->fetchColumn();
$highCount = (int)$pdo->query('SELECT COUNT(*) FROM countries WHERE impact_score > 60')->fetchColumn();
$topCountry = $pdo->query('SELECT name, iso_a3, impact_score FROM countries WHERE impact_score IS NOT NULL ORDER BY impact_score DESC, name LIMIT 1')->fetch();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <h1>Understanding Our Changing Planet</h1>
    <p>Explore how climate change is reshaping countries, ecosystems and species around the world.</p>
    <a class="btn" href="<?= url('tracker.php') ?>">Explore Climate Tracker</a>
  </div>
</section>

<?php if ($featured): ?>
<section class="container section">
  <div class="featured">
    <?php if ($featured['image_url']): ?><img src="<?= e($featured['image_url']) ?>" alt=""><?php endif; ?>
    <div>
      <span class="tag">Featured · <?= e($featured['category'] ?? 'Uncategorised') ?></span>
      <h2><a href="<?= url('article.php?slug=' . urlencode($featured['slug'])) ?>"><?= e($featured['title']) ?></a></h2>
      <p><?= e($featured['summary']) ?></p>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="container section">
<h2>Climate Snapshot</h2>
  <div class="stats">
    <div class="stat">
      <span class="num"><?= $warming !== null ? ($warming >= 0 ? '+' : '') . number_format((float)$warming, 1) . ' °C' : '—' ?></span>
      <span>Average land warming across countries (<?= $maxY ? ($maxY - 9) . '–' . $maxY : 'latest 10 years' ?> vs 1950–1959)</span>
    </div>
    <div class="stat">
      <span class="num"><?= $highCount ?></span>
      <span>of <?= $scored ?> countries in the High or Very High vulnerability bands</span>
    </div>
    <?php if ($topCountry): ?>
    <div class="stat">
      <span class="num"><a href="<?= url('country.php?iso=' . urlencode($topCountry['iso_a3'])) ?>"><?= e($topCountry['name']) ?></a></span>
      <span>Most vulnerable country in the dataset (score <?= (int)$topCountry['impact_score'] ?>)</span>
    </div>
    <?php endif; ?>
    <div class="stat">
      <span class="num">1995–2024</span>
      <span>Years of vulnerability data on the <a href="<?= url('tracker.php') ?>">globe timeline</a></span>
    </div>
  </div>
  <p class="note">Warming is the equal-weighted average of each country's national land temperature change, so small and large countries count the same. It is not the same as the global mean temperature anomaly published by NASA or the Met Office, which includes oceans. Vulnerability comes from the ND-GAIN index (see <a href="<?= url('about.php#methodology') ?>">methodology</a>).</p>
