<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM species WHERE id = ?');
$stmt->execute([$id]);
$sp = $stmt->fetch();
if (!$sp) not_found('Species not found.');

$stmt = $pdo->prepare('SELECT threat_type, description FROM species_threats WHERE species_id = ? ORDER BY id');
$stmt->execute([$id]);
$threats = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT latitude AS lat, longitude AS lng, label FROM species_regions WHERE species_id = ? ORDER BY id');
$stmt->execute([$id]);
$regions = array_map(fn($r) => ['lat' => (float)$r['lat'], 'lng' => (float)$r['lng'], 'label' => $r['label']], $stmt->fetchAll());

[$sl, $sbg, $sfg] = status_info($sp['conservation_status']);

$pageTitle = $sp['name'];
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <a href="<?= url('species.php') ?>">← All species</a>
  <div class="species-detail">
    <div>
      <h1><?= e($sp['name']) ?></h1>
      <?php if ($sp['scientific_name']): ?><p class="mute"><em><?= e($sp['scientific_name']) ?></em></p><?php endif; ?>
      <p>
        <span class="badge" style="background:<?= $sbg ?>;color:<?= $sfg ?>"><?= e($sl) ?></span>
        <span class="tag"><?= e(ucfirst($sp['category'])) ?></span>
      </p>
      <?php if ($sp['image_url']): ?><img class="article-img" src="<?= e($sp['image_url']) ?>" alt=""><?php endif; ?>
      <p><strong>Habitat:</strong> <?= e($sp['habitat'] ?? 'Unknown') ?></p>
      <p><strong>Population trend:</strong> <?= e(trend_label($sp['population_trend'])) ?></p>

      <h2>Climate threats</h2>
      <?php if (!$threats): ?>
        <p class="mute">No threats recorded yet.</p>
      <?php else: ?>
        <ul class="events">
          <?php foreach ($threats as $t): ?>
            <li><strong><?= e($t['threat_type']) ?></strong><p><?= e($t['description']) ?></p></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <p class="note">Conservation status follows the IUCN Red List, which is updated periodically. Check
        <a href="https://www.iucnredlist.org" target="_blank" rel="noopener">iucnredlist.org</a> for the current assessment.</p>
    </div>

    <div>
      <h2>Where it lives</h2>
      <div id="globe" class="globe-box tall"></div>
      <?php if ($regions): ?>
        <p class="note">Marked regions: <?= e(implode(', ', array_column($regions, 'label'))) ?></p>
      <?php else: ?>
        <p class="note">No range locations recorded yet.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/globe.gl@2.27.2/dist/globe.gl.min.js"></script>
<script src="<?= url('assets/js/globe.js') ?>"></script>
<script>
const regions = <?= json_encode($regions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const api = initClimateGlobe({
  el: document.getElementById('globe'),
  geoUrl: <?= json_encode(url('assets/data/countries.geojson')) ?>,
  apiUrl: <?= json_encode(url('api/countries.php')) ?>,
  plain: true,
  autoRotate: false
});
api.ready.then(() => {
  const g = api.globe;
  g.pointsData(regions).pointLat('lat').pointLng('lng')
   .pointColor(() => '#ffffff').pointAltitude(0.02).pointRadius(0.45)
   .pointLabel(d => '<b>' + esc(d.label) + '</b>')
   .ringsData(regions)
   .ringColor(() => t => 'rgba(255,120,80,' + (1 - t) + ')')
   .ringMaxRadius(6).ringPropagationSpeed(2).ringRepeatPeriod(900);
  if (regions.length) g.pointOfView({ lat: regions[0].lat, lng: regions[0].lng, altitude: 2.2 }, 1000);
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
