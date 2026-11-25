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
    WHERE country_id = ? AND indicator IN ('nd_gain_index','nd_gain_vulnerability','nd_gain_readiness') ORDER BY year");
$stmt->execute([$cid]);
$series = [];
foreach ($stmt as $r) {
    $series[$r['indicator']]['labels'][] = (int)$r['year'];
    $series[$r['indicator']]['values'][] = (float)$r['value'];
}

$parts = [
    'nd_gain_exposure'       => 'Exposure',
    'nd_gain_sensitivity'    => 'Sensitivity',
    'nd_gain_capacity'       => 'Low adaptive capacity',    'nd_gain_food'           => 'Food',
    'nd_gain_health'         => 'Health',
    'nd_gain_ecosystems'     => 'Ecosystems',
    'nd_gain_habitat'        => 'Human habitat',
    'nd_gain_infrastructure' => 'Infrastructure',
];
$ph = implode(',', array_fill(0, count($parts), '?'));
$stmt = $pdo->prepare(
    "SELECT indicator, value FROM climate_data
     WHERE country_id = ? AND indicator IN ($ph)
       AND year = (SELECT MAX(year) FROM climate_data WHERE country_id = ? AND indicator = 'nd_gain_vulnerability')"
);
$stmt->execute([$cid, ...array_keys($parts), $cid]);
$breakdown = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$charts = [
    'nd_gain_vulnerability' => ['Vulnerability (lower = less vulnerable)', '#e0793a'],
    'nd_gain_readiness'     => ['Readiness to adapt (higher = more ready)', '#2f7fc1'],
    'nd_gain_index'         => ['ND-GAIN Index (higher = better prepared)', '#1f8a5b'],
];

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
      <p class="note">ND-GAIN vulnerability, scaled 0–100 across countries<?= $country['score_year'] ? ' (' . (int)$country['score_year'] . ')' : '' ?>. Higher = more vulnerable. 0 is the least vulnerable country in the dataset and 100 the most.</p>    </div>
    <div>
      <?= $country['summary'] ? '<p>' . e($country['summary']) . '</p>' : '<p class="mute">No summary written yet.</p>' ?>
      <a href="<?= url('tracker.php?country=' . urlencode($country['iso_a3'])) ?>">View on the globe →</a>
    </div>
  </div>

  <div class="chart-grid">
  <?php foreach ($charts as $key => [$title, $col]): if (!isset($series[$key])) continue; ?>
    <div class="card"><h3><?= e($title) ?></h3><canvas id="chart-<?= e($key) ?>"></canvas></div>
  <?php endforeach; ?>
</div>

<?php if ($breakdown): ?>
    <h2>What drives the score</h2>
    <div class="card">
      <?php foreach ($parts as $key => $label): if (!isset($breakdown[$key])) continue; $v = round((float)$breakdown[$key], 1); ?>
        <div class="part">
          <span><?= e($label) ?></span>
          <div class="bar"><i style="width:<?= $v ?>%;background:#e0793a"></i></div>
          <strong><?= $v ?></strong>
        </div>
      <?php endforeach; ?>
      <p class="note">ND-GAIN components for the latest year, 0–100. For every bar, <strong>higher means more vulnerable</strong>. Exposure is how strongly the country is hit by hazards, sensitivity is how much its people and systems depend on what is hit, and low adaptive capacity is a limited ability to cope and recover. Sector rows give the same score for each life-supporting sector.</p>    </div>
  <?php endif; ?>

<?php if (!$series): ?><p class="mute">No indicator data available for this country.</p><?php endif; ?>

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
const series = <?= json_encode($series, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const meta = <?= json_encode(array_map(fn($c) => ['color' => $c[1]], $charts)) ?>;
Object.entries(series).forEach(([key, s]) => {
  new Chart(document.getElementById('chart-' + key), {
    type: 'line',
    data: { labels: s.labels, datasets: [{ data: s.values, borderColor: meta[key].color, backgroundColor: meta[key].color + '33', fill: true, tension: 0.3, pointRadius: 2 }] },
    options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { maxTicksLimit: 8 } } } }
  });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
