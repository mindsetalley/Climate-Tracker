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
</section>

<script>
  window.CT = {
    geo: <?= json_encode(url('assets/data/countries.geojson')) ?>,
    api: <?= json_encode(url('api/countries.php')) ?>,
    countryUrl: <?= json_encode(url('country.php?iso=')) ?>,
    preselect: <?= json_encode($preselect) ?>
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/globe.gl@2.27.2/dist/globe.gl.min.js"></script>
<script src="<?= url('assets/js/globe.js') ?>"></script>
<script src="<?= url('assets/js/tracker.js') ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
