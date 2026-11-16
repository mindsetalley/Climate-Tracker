<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$iso = strtoupper(trim($_GET['iso'] ?? ''));
if (!preg_match('/^[A-Z]{3}$/', $iso)) not_found('Country not found.');

$stmt = $pdo->prepare('SELECT * FROM countries WHERE iso_a3 = ?');
$stmt->execute([$iso]);
$country = $stmt->fetch();
if (!$country) not_found('Country not found.');

$cid = (int)$country['id'];

// Time series, grouped by indicator
$stmt = $pdo->prepare(
    "SELECT indicator, year, value FROM climate_data
     WHERE country_id = ? AND indicator IN ('avg_temp_c','rainfall_mm') ORDER BY year"
);
$stmt->execute([$cid]);
$series = [];
foreach ($stmt as $r) {
    $series[$r['indicator']]['labels'][] = (int)$r['year'];
    $series[$r['indicator']]['values'][] = (float)$r['value'];
}

$stmt = $pdo->prepare('SELECT event_type, event_date, description FROM climate_events WHERE country_id = ? ORDER BY event_date DESC');
$stmt->execute([$cid]);
$events = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT DISTINCT s.name, s.url FROM climate_data d JOIN sources s ON s.id = d.source_id WHERE d.country_id = ?');
$stmt->execute([$cid]);
$sources = $stmt->fetchAll();

$score = $country['impact_score'] === null ? null : (int)$country['impact_score'];
[$label, $color] = score_level($score);

$pageTitle = $country['name'] . ' – Climate Overview';
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <a href="<?= url('countries.php') ?>">← All countries</a>
  <h1><?= e($country['name']) ?> – Climate Overview</h1>
  <p class="mute"><?= e($country['continent']) ?> · <?= e($country['iso_a3']) ?></p>

  <div class="card score-card">
    <div>
      <span class="badge" style="background:<?= $color ?>"><?= e($label) ?></span>
      <?php if ($score !== null): ?>
        <div class="big"><?= $score ?><small> / 100</small></div>
        <div class="bar"><i style="width:<?= $score ?>%;background:<?= $color ?>"></i></div>
      <?php endif; ?>
      <p class="note">Climate Impact and Vulnerability Index. Scores are currently placeholders until the index methodology is built.</p>
    </div>
    <div>
      <?= $country['summary'] ? '<p>' . e($country['summary']) . '</p>' : '<p class="mute">No summary written yet.</p>' ?>
      <a href="<?= url('tracker.php?country=' . urlencode($country['iso_a3'])) ?>">View on the globe →</a>
    </div>
  </div>

  <div class="chart-grid">
    <div class="card"><h3>Average temperature (°C)</h3>
      <?= isset($series['avg_temp_c']) ? '<canvas id="tempChart"></canvas>' : '<p class="mute">No data available.</p>' ?></div>
    <div class="card"><h3>Annual rainfall (mm)</h3>
      <?= isset($series['rainfall_mm']) ? '<canvas id="rainChart"></canvas>' : '<p class="mute">No data available.</p>' ?></div>
  </div>

  <h2>Documented climate events</h2>
  <?php if (!$events): ?>
    <p class="mute">No events recorded yet.</p>
  <?php else: ?>
    <ul class="events">
      <?php foreach ($events as $ev): ?>
        <li>
          <span class="tag"><?= e(event_label($ev['event_type'])) ?></span>
          <strong><?= $ev['event_date'] ? date('j M Y', strtotime($ev['event_date'])) : 'Date unknown' ?></strong>
          <p><?= e($ev['description']) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <p class="note">
    <strong>Exposure vs vulnerability:</strong> a country can face frequent hazards but cope well because of strong infrastructure,
    while another with fewer hazards may suffer more. The index tries to account for both.
  </p>
  <?php if ($sources): ?>
    <p class="note">Data source:
      <?= implode(', ', array_map(fn($s) => e($s['name']), $sources)) ?>
    </p>
  <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
const temp = <?= json_encode($series['avg_temp_c'] ?? null, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const rain = <?= json_encode($series['rainfall_mm'] ?? null, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

function draw(id, data, type, label, color) {
  if (!data) return;
  new Chart(document.getElementById(id), {
    type,
    data: { labels: data.labels, datasets: [{ label, data: data.values, borderColor: color, backgroundColor: color + '99', tension: 0.3, pointRadius: 2 }] },
    options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { maxTicksLimit: 8 } } } }
  });
}
draw('tempChart', temp, 'line', '°C', '#e0793a');
draw('rainChart', rain, 'bar', 'mm', '#2f7fc1');
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
