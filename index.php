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
  <h2>Global Climate Snapshot</h2>
  <div class="stats">
    <div class="stat"><span class="num">+1.2°C</span><span>Temperature anomaly</span></div>
    <div class="stat"><span class="num">+10 cm</span><span>Sea-level rise since 1993</span></div>
    <div class="stat"><span class="num">37 Gt</span><span>Annual CO₂ emissions</span></div>
    <div class="stat"><span class="num">10 M ha</span><span>Yearly forest loss</span></div>
  </div>
  <p class="note">Placeholder figures. We'll replace these with real data later.</p>
</section>

<section class="container section globe-teaser">
  <h2>Interactive Globe</h2>
  <div id="globePreview" class="globe-box"></div>
    <a class="btn" href="<?= url('tracker.php') ?>">Open full tracker</a>
</section>

<?php if ($spotlight): ?>
<section class="container section">
  <h2>Threatened Species</h2>
  <div class="species-grid">
    <?php foreach ($spotlight as $s) include __DIR__ . '/includes/species_card.php'; ?>
  </div>
  <p><a href="<?= url('species.php') ?>">See all species →</a></p>
</section>
<?php endif; ?>

<section class="container section">
  <h2>Latest Climate News</h2>
  <?php if (!$latest): ?>
    <p>No articles yet.</p>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($latest as $a): ?>
        <article class="card">
          <?php if ($a['image_url']): ?>
            <img src="<?= e($a['image_url']) ?>" alt="">
          <?php endif; ?>
          <div class="card-body">
            <span class="tag"><?= e($a['category'] ?? 'Uncategorised') ?></span>
            <h3><a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a></h3>
            <p><?= e($a['summary']) ?></p>
            <small><?= date('j M Y', strtotime($a['published_at'])) ?></small>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/globe.gl@2.27.2/dist/globe.gl.min.js"></script>
<script src="<?= url('assets/js/globe.js') ?>"></script>
<script>
  initClimateGlobe({
    el: document.getElementById('globePreview'),
    geoUrl: <?= json_encode(url('assets/data/countries.geojson')) ?>,
    apiUrl: <?= json_encode(url('api/countries.php')) ?>,
    zoom: false,   // don't hijack page scrolling
    onSelect: code => { location.href = <?= json_encode(url('tracker.php?country=')) ?> + code; }
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
