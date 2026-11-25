<?php
// Usage (from project root): php scripts/import_cckp.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run this from the command line.'); }

require __DIR__ . '/../config/db.php';

$dir = realpath(__DIR__ . '/../data/raw/cckp');
if (!$dir) exit("Folder data/raw/cckp not found.\n");

$minYear = 1950;   // earlier CRU values are sparse and partly gap-filled

// indicator => [file, plausible min, plausible max]
$files = [
    'avg_temp_c'  => ['tas.json', -60, 60],
    'rainfall_mm' => ['pr.json',    0, 15000],
];

$ids = $pdo->query('SELECT iso_a3, id FROM countries')->fetchAll(PDO::FETCH_KEY_PAIR);

$srcName = 'World Bank Climate Change Knowledge Portal (CRU TS4.10)';
$stmt = $pdo->prepare('SELECT id FROM sources WHERE name = ?');
$stmt->execute([$srcName]);
$sourceId = $stmt->fetchColumn();
if (!$sourceId) {
    $pdo->prepare('INSERT INTO sources (name, url) VALUES (?, ?)')
        ->execute([$srcName, 'https://climateknowledgeportal.worldbank.org/']);
    $sourceId = $pdo->lastInsertId();
}

$up = $pdo->prepare(
    'INSERT INTO climate_data (country_id, source_id, indicator, year, value) VALUES (?,?,?,?,?)
     ON DUPLICATE KEY UPDATE value = VALUES(value), source_id = VALUES(source_id)'
);

$pdo->beginTransaction();
foreach ($files as $indicator => [$file, $lo, $hi]) {
    $path = "$dir/$file";
    if (!is_file($path)) { echo "SKIP  $indicator (missing $file)\n"; continue; }

    $json = json_decode(file_get_contents($path), true);
    if (!is_array($json) || ($json['metadata']['status'] ?? '') !== 'success' || !isset($json['data'])) {
        echo "SKIP  $indicator (unexpected file contents)\n";
        continue;
    }

    $rows = 0; $matched = 0; $unmatched = []; $outOfRange = 0;
    foreach ($json['data'] as $iso => $series) {
        if (!isset($ids[$iso])) { $unmatched[] = $iso; continue; }
        $matched++;
        foreach ($series as $key => $val) {
            $year = (int)substr((string)$key, 0, 4);
            if ($year < $minYear || !is_numeric($val)) continue;
            if ($val < $lo || $val > $hi) { $outOfRange++; continue; }
            $up->execute([$ids[$iso], $sourceId, $indicator, $year, round((float)$val, 3)]);
            $rows++;
        }
    }

    printf("READ  %-12s %3d countries matched, %d values imported (from %d)", $indicator, $matched, $rows, $minYear);
    if ($outOfRange) echo ", $outOfRange out-of-range values skipped";
    echo "\n";
    echo '      Not in your database: ' . count($unmatched) . ' codes'
       . ($unmatched ? ' (' . implode(', ', array_slice($unmatched, 0, 40)) . (count($unmatched) > 40 ? ', ...' : '') . ')' : '')
       . "\n";

    $missing = $pdo->query(
        "SELECT c.name FROM countries c
         WHERE NOT EXISTS (SELECT 1 FROM climate_data d WHERE d.country_id = c.id AND d.indicator = " . $pdo->quote($indicator) . ")
         ORDER BY c.name"
    )->fetchAll(PDO::FETCH_COLUMN);
    echo '      Your countries with no data: ' . count($missing) . ($missing ? ' (' . implode(', ', $missing) . ')' : '') . "\n";
}
$pdo->commit();
echo "Done.\n";
