<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$all = $pdo->query('SELECT id, name, iso_a3, impact_score FROM countries ORDER BY name')->fetchAll();
$byIso = [];
foreach ($all as $c) $byIso[$c['iso_a3']] = $c;

// Up to 3 valid, distinct countries from ?c[]=GHA&c[]=NOR
$sel = [];
foreach ((array)($_GET['c'] ?? []) as $code) {
    $code = strtoupper(trim((string)$code));
    if (preg_match('/^[A-Z]{3}$/', $code) && isset($byIso[$code]) && !in_array($code, $sel, true)) $sel[] = $code;
    if (count($sel) === 3) break;
}

function latest_val(array $s): ?float { return $s ? $s[max(array_keys($s))] : null; }
function avg_years(array $s, int $from, int $to): ?float {
    $v = array_filter($s, fn($y) => $y >= $from && $y <= $to, ARRAY_FILTER_USE_KEY);
    return $v ? array_sum($v) / count($v) : null;
}

$indicators = ['avg_temp_c', 'rainfall_mm', 'nd_gain_vulnerability', 'nd_gain_readiness',
               'nd_gain_exposure', 'nd_gain_sensitivity', 'nd_gain_capacity'];
$ser = [];
if (count($sel) >= 2) {
    $ids = array_map(fn($c) => (int)$byIso[$c]['id'], $sel);
    $idToIso = array_combine($ids, $sel);
    $in1 = implode(',', array_fill(0, count($ids), '?'));
    $in2 = implode(',', array_fill(0, count($indicators), '?'));
    $stmt = $pdo->prepare("SELECT country_id, indicator, year, value FROM climate_data
                           WHERE country_id IN ($in1) AND indicator IN ($in2) ORDER BY year");
    $stmt->execute([...$ids, ...$indicators]);
    foreach ($stmt as $r) {
        $ser[$idToIso[(int)$r['country_id']]][$r['indicator']][(int)$r['year']] = (float)$r['value'];
    }
}

// Table values per country
$m = [];
foreach ($sel as $iso) {
    $s = $ser[$iso] ?? [];
    $t = $s['avg_temp_c'] ?? [];
    $tMax = $t ? max(array_keys($t)) : null;
    $now  = $tMax ? avg_years($t, $tMax - 9, $tMax) : null;
    $then = avg_years($t, 1950, 1959);
    $r = $s['rainfall_mm'] ?? [];
    $rMax = $r ? max(array_keys($r)) : null;
    $m[$iso] = [
        'exposure'    => latest_val($s['nd_gain_exposure'] ?? []),
        'sensitivity' => latest_val($s['nd_gain_sensitivity'] ?? []),
        'capacity'    => latest_val($s['nd_gain_capacity'] ?? []),
        'readiness'   => latest_val($s['nd_gain_readiness'] ?? []),
        'temp_now'    => $now,
        'temp_change' => ($now !== null && $then !== null) ? $now - $then : null,
        'rain_now'    => $rMax ? avg_years($r, $rMax - 9, $rMax) : null,
    ];
}

$rows = [
    ['Exposure',               'exposure',    1, ''],
    ['Sensitivity',            'sensitivity', 1, ''],
    ['Low adaptive capacity',  'capacity',    1, ''],
    ['Readiness (higher = better)', 'readiness', 1, ''],
    ['Temperature, last 10 years', 'temp_now', 1, ' °C'],
    ['Change since the 1950s', 'temp_change', 1, ' °C'],
    ['Rainfall, last 10 years (avg)', 'rain_now', 0, ' mm'],
];

// Chart data: one dataset per country
$palette = ['#e0793a', '#2f7fc1', '#1f8a5b'];
$chartDefs = [
    'avg_temp_c'            => 'Average annual temperature (°C)',
    'rainfall_mm'           => 'Annual rainfall (mm)',
    'nd_gain_vulnerability' => 'ND-GAIN vulnerability (higher = more vulnerable)',
    'nd_gain_readiness'     => 'ND-GAIN readiness (higher = more ready)',
];
$chartData = [];
foreach ($chartDefs as $ind => $title) {
    $years = [];
    foreach ($sel as $iso) foreach (array_keys($ser[$iso][$ind] ?? []) as $y) $years[$y] = true;
    if (!$years) continue;
    $years = array_keys($years);
    sort($years);
    $datasets = [];
    foreach ($sel as $i => $iso) {
        $s = $ser[$iso][$ind] ?? [];
        if (!$s) continue;
        $datasets[] = ['label' => $byIso[$iso]['name'], 'color' => $palette[$i],
                       'data' => array_map(fn($y) => $s[$y] ?? null, $years)];
    }
    $chartData[$ind] = ['title' => $title, 'labels' => $years, 'datasets' => $datasets];
}

$pageTitle = 'Compare countries';
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <h1>Compare countries</h1>
  <p class="mute">Choose two or three countries to compare side by side.</p>

  <form method="get" class="filters">
    <?php for ($i = 0; $i < 3; $i++): ?>
      <select name="c[]">
        <option value=""><?= $i < 2 ? 'Choose a country' : 'Add a third (optional)' ?></option>
        <?php foreach ($all as $c): ?>
          <option value="<?= e($c['iso_a3']) ?>" <?= ($sel[$i] ?? '') === $c['iso_a3'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endfor; ?>
    <button class="btn" type="submit">Compare</button>
  </form>

  <?php if (count($sel) < 2): ?>
    <p class="note">Pick at least two countries to see a comparison.</p>
  <?php else: ?>
    <div class="table-wrap">
    <table class="table cmp">
      <tr>
        <th></th>
        <?php foreach ($sel as $i => $iso): ?>
          <th style="border-top:4px solid <?= $palette[$i] ?>">
            <a href="<?= url('country.php?iso=' . urlencode($iso)) ?>"><?= e($byIso[$iso]['name']) ?></a>
          </th>
        <?php endforeach; ?>
      </tr>
      <tr>
        <td>Impact score (0–100)</td>
        <?php foreach ($sel as $iso):
            $sc = $byIso[$iso]['impact_score'] === null ? null : (int)$byIso[$iso]['impact_score'];
            [$label, $color] = score_level($sc); ?>
          <td><span class="badge" style="background:<?= $color ?>"><?= e($label) ?><?= $sc !== null ? ' · ' . $sc : '' ?></span></td>
        <?php endforeach; ?>
      </tr>
      <?php foreach ($rows as [$label, $key, $dec, $suffix]): ?>
        <tr>
          <td><?= e($label) ?></td>
          <?php foreach ($sel as $iso): $v = $m[$iso][$key]; ?>
            <td><?php
                if ($v === null) echo '—';
                else echo e(($key === 'temp_change' && $v > 0 ? '+' : '') . number_format($v, $dec) . $suffix);
            ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </table>
    </div>
    <p class="note">Impact score: ND-GAIN vulnerability scaled 0–100 across countries (higher = more vulnerable). Exposure, sensitivity and low adaptive capacity are ND-GAIN components for the latest year (higher = more vulnerable). The change since the 1950s compares the last 10 years of data with 1950–1959. A dash means no data for that country.</p>

    <div class="chart-grid">
      <?php foreach ($chartData as $key => $c): ?>
        <div class="card"><h3><?= e($c['title']) ?></h3><canvas id="cmp-<?= e($key) ?>"></canvas></div>
      <?php endforeach; ?>
    </div>
    <p class="note">Vulnerability and readiness charts show the raw ND-GAIN values (0–100), not the scaled impact score. Temperature and rainfall are national averages from CRU TS4.10 via the World Bank CCKP; rainfall is less reliable than temperature, especially for small countries.</p>
  <?php endif; ?>
</section>

<?php if (count($sel) >= 2 && $chartData): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
const charts = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
Object.entries(charts).forEach(([key, c]) => {
  new Chart(document.getElementById('cmp-' + key), {
    type: 'line',
    data: {
      labels: c.labels,
      datasets: c.datasets.map(d => ({
        label: d.label, data: d.data, borderColor: d.color, backgroundColor: d.color,
        tension: 0.3, pointRadius: 0, borderWidth: 2, spanGaps: true
      }))
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'bottom' } },
      scales: { x: { ticks: { maxTicksLimit: 8 } } }
    }
  });
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
