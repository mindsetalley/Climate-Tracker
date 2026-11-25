<?php
// Usage (from project root): php scripts/import_nd_gain.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run this from the command line.'); }

require __DIR__ . '/../config/db.php';

$raw = realpath(__DIR__ . '/../data/raw/nd-gain');
if (!$raw) exit("Folder data/raw/nd-gain not found.\n");

// indicator => file path inside data/raw/nd-gain (missing files are skipped)
$files = [
    'nd_gain_index'          => 'gain/gain.csv',
    'nd_gain_vulnerability'  => 'vulnerability/vulnerability.csv',
    'nd_gain_readiness'      => 'readiness/readiness.csv',
    // vulnerability components
    'nd_gain_exposure'       => 'vulnerability/exposure.csv',
    'nd_gain_sensitivity'    => 'vulnerability/sensitivity.csv',
    'nd_gain_capacity'       => 'vulnerability/capacity.csv',
    // vulnerability sectors
    'nd_gain_food'           => 'vulnerability/food.csv',
    'nd_gain_health'         => 'vulnerability/health.csv',
    'nd_gain_ecosystems'     => 'vulnerability/ecosystems.csv',
    'nd_gain_habitat'        => 'vulnerability/habitat.csv',
    'nd_gain_infrastructure' => 'vulnerability/infrastructure.csv',
    // readiness components
    'nd_gain_economic'       => 'readiness/economic.csv',
    'nd_gain_governance'     => 'readiness/governance.csv',
    'nd_gain_social'         => 'readiness/social.csv',
];
function read_series(string $path): array {
    $text  = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents($path));
    $lines = preg_split('/\r\n|\r|\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $header = array_shift($lines) ?? '';
    $delim  = substr_count($header, "\t") >= substr_count($header, ',') ? "\t" : ',';
    $cols   = str_getcsv($header, $delim, '"', '\\');

    $yearCols = [];
    foreach ($cols as $i => $c) if (preg_match('/^\d{4}$/', trim($c))) $yearCols[$i] = (int)$c;

    $out = [];
    $max = 0.0;
    foreach ($lines as $line) {
        $row = str_getcsv($line, $delim, '"', '\\');
        $iso = strtoupper(trim($row[0] ?? ''));
        if (!preg_match('/^[A-Z]{3}$/', $iso)) continue;
        $vals = [];
        foreach ($yearCols as $i => $year) {
            $v = trim($row[$i] ?? '');
            if ($v === '' || !is_numeric($v)) continue;
            $vals[$year] = (float)$v;
            $max = max($max, abs((float)$v));
        }
        if ($vals) $out[$iso] = ['name' => trim($row[1] ?? $iso), 'v' => $vals];
    }

    $scaled = $max > 0 && $max <= 1.0;
    if ($scaled) foreach ($out as &$s) foreach ($s['v'] as &$v) $v *= 100;
    unset($s, $v);
    return [$out, $scaled];
}

// ---- Read files -------------------------------------------------------
$series = [];
foreach ($files as $indicator => $rel) {
    $path = "$raw/$rel";
    if (!is_file($path)) { echo "SKIP  $indicator (not found: $rel)\n"; continue; }
    [$data, $scaled] = read_series($path);
    $series[$indicator] = $data;
    printf("READ  %-22s %3d countries%s\n", $indicator, count($data), $scaled ? '  (rescaled 0-1 -> 0-100)' : '');
}
if (!$series) exit("Nothing to import.\n");

// ---- Map data for new countries --------------------------------------
$geo = json_decode(file_get_contents(__DIR__ . '/../assets/data/countries.geojson'), true);
$lookup = [];
foreach ($geo['features'] as $f) {
    $p = $f['properties'];
    $b = $f['bbox'] ?? null;
    $info = [
        'a2'        => preg_match('/^[A-Z]{2}$/', $p['ISO_A2'] ?? '') ? $p['ISO_A2'] : null,
        'continent' => $p['CONTINENT'] ?? null,
        'lat'       => $b ? round(($b[1] + $b[3]) / 2, 5) : null,
        'lng'       => $b ? round(($b[0] + $b[2]) / 2, 5) : null,
        'name'      => $p['ADMIN'] ?? null,
    ];

    foreach (['ADM0_A3', 'ISO_A3'] as $k) {
        if (preg_match('/^[A-Z]{3}$/', $p[$k] ?? '')) $lookup[$p[$k]] = $info;
    }
}

// ---- Ensure countries exist ------------------------------------------
$existing = $pdo->query('SELECT iso_a3 FROM countries')->fetchAll(PDO::FETCH_COLUMN);
$existing = array_flip($existing);
$insCountry = $pdo->prepare('INSERT INTO countries (name, iso_a2, iso_a3, continent, latitude, longitude) VALUES (?,?,?,?,?,?)');

$added = 0;
$noMap = [];
foreach ($series as $data) {
    foreach ($data as $iso => $s) {
        if (isset($existing[$iso])) continue;
        $g = $lookup[$iso] ?? null;
        if (!$g) $noMap[$iso] = $s['name'];
        $insCountry->execute([$g['name'] ?? $s['name'], $g['a2'] ?? null, $iso, $g['continent'] ?? null, $g['lat'] ?? null, $g['lng'] ?? null]);        $existing[$iso] = true;
        $added++;
    }
}
echo "Countries added: $added\n";
if ($noMap) echo 'No map match (will not show on the globe): ' . implode(', ', array_map(fn($k, $v) => "$v ($k)", array_keys($noMap), $noMap)) . "\n";

$ids = $pdo->query('SELECT iso_a3, id FROM countries')->fetchAll(PDO::FETCH_KEY_PAIR);

// ---- Source row ------------------------------------------------------
$srcName = 'ND-GAIN Country Index';
$stmt = $pdo->prepare('SELECT id FROM sources WHERE name = ?');
$stmt->execute([$srcName]);
$sourceId = $stmt->fetchColumn();
if (!$sourceId) {
    $pdo->prepare('INSERT INTO sources (name, url) VALUES (?, ?)')->execute([$srcName, 'https://gain.nd.edu/our-work/country-index/']);
    $sourceId = $pdo->lastInsertId();
}

// ---- Import values ---------------------------------------------------
$pdo->beginTransaction();
$up = $pdo->prepare(
    'INSERT INTO climate_data (country_id, source_id, indicator, year, value) VALUES (?,?,?,?,?)
     ON DUPLICATE KEY UPDATE value = VALUES(value), source_id = VALUES(source_id)'
);
$rows = 0;
foreach ($series as $indicator => $data) {
    foreach ($data as $iso => $s) {
        foreach ($s['v'] as $year => $value) {
            $up->execute([$ids[$iso], $sourceId, $indicator, $year, round($value, 4)]);
            $rows++;
        }
    }
}

// ---- Remove placeholder data -----------------------------------------
$pdo->exec("DELETE d FROM climate_data d JOIN sources s ON s.id = d.source_id WHERE s.name = 'Placeholder (sample data)'");
$pdo->exec("DELETE FROM sources WHERE name = 'Placeholder (sample data)'");
echo "Values imported: $rows. Placeholder series removed.\n";

// ---- Impact score: scaled vulnerability ------------------------------
if (isset($series['nd_gain_vulnerability'])) {
    $key = 'nd_gain_vulnerability'; $invert = false;
} else {
    $key = 'nd_gain_index'; $invert = true;
    echo "WARNING: vulnerability file not imported. Using 100 minus the overall index as a STAND-IN score.\n";
}

$perYear = [];
foreach ($series[$key] as $s) foreach ($s['v'] as $y => $_) $perYear[$y] = ($perYear[$y] ?? 0) + 1;
$total = count($series[$key]);
$year = null;
foreach ($perYear as $y => $n) if ($n >= 0.5 * $total && ($year === null || $y > $year)) $year = $y;

$vals = [];
foreach ($series[$key] as $iso => $s) {
    if (!isset($s['v'][$year])) continue;
    $vals[$iso] = $invert ? 100 - $s['v'][$year] : $s['v'][$year];
}
$min = min($vals); $max = max($vals);

$pdo->exec('UPDATE countries SET impact_score = NULL, score_year = NULL');
$setScore = $pdo->prepare('UPDATE countries SET impact_score = ?, score_year = ? WHERE id = ?');
foreach ($vals as $iso => $v) {
    $setScore->execute([(int)round(($v - $min) / ($max - $min) * 100), $year, $ids[$iso]]);
}
$pdo->commit();

printf("Scores set for %d countries using %s %d (raw range %.1f to %.1f, scaled to 0-100).\n", count($vals), $key, $year, $min, $max);
echo "Done.\n";
