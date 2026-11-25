<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$preselect = strtoupper(preg_replace('/[^A-Za-z]/', '', $_GET['country'] ?? ''));
$bands = [[0, 20], [21, 40], [41, 60], [61, 80], [81, 100]];

$pageTitle = 'Climate Tracker';
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <h1>Climate Tracker</h1>
  <p class="mute">Drag to rotate, scroll to zoom, click a country to explore it.</p>

  <div class="search-bar">
    <input type="search" id="countrySearch" list="countryList" placeholder="Jump to a country…" autocomplete="off">
    <datalist id="countryList"></datalist>
  </div>

  <div id="yearBox" class="year-box" hidden>
    <button type="button" id="playBtn" class="btn" aria-label="Play timeline">▶</button>
    <input type="range" id="yearSlider" min="1995" max="2024" step="1" value="2024" aria-label="Year">
    <strong id="yearLabel">2024</strong>
  </div>

  <div class="tracker-grid">
    <div id="globe" class="globe-box tall"></div>
    <aside class="card" id="panel">
      <h3>Pick a country</h3>
      <p class="mute">Click any coloured country on the globe, or search above.</p>
    </aside>
  </div>

  <div class="legend">
    <?php foreach ($bands as [$lo, $hi]): [$label, $color] = score_level($lo); ?>
      <span style="--c:<?= $color ?>"><?= e($label) ?> (<?= $lo ?>–<?= $hi ?>)</span>
    <?php endforeach; ?>
    <span style="--c:#33443b">No data yet</span>
  </div>
  <p class="note">Scores are currently placeholders. The Climate Impact and Vulnerability Index methodology will be published here once it's built.</p>
  <p class="note">Small island states and microstates that are too small for the map outline appear as coloured dots.</p>
  <p class="note">The year slider uses one fixed colour scale (set by the latest year) so colours are comparable across years. Years before 2024 can be clipped at the ends of the scale.</p>
</section>

<script>
  window.CT = {
    geo: <?= json_encode(url('assets/data/countries.geojson')) ?>,
    api: <?= json_encode(url('api/countries.php')) ?>,
    countryUrl: <?= json_encode(url('country.php?iso=')) ?>,
    preselect: <?= json_encode($preselect) ?>,
    scores: <?= json_encode(url('api/scores.php')) ?>
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/globe.gl@2.27.2/dist/globe.gl.min.js"></script>
<script src="<?= url('assets/js/globe.js') ?>?v=<?= filemtime(__DIR__ . '/assets/js/globe.js') ?>"></script>
<script src="<?= url('assets/js/tracker.js') ?>?v=<?= filemtime(__DIR__ . '/assets/js/tracker.js') ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
