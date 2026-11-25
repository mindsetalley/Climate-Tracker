<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$base = (int)$pdo->query('SELECT MAX(score_year) FROM countries')->fetchColumn();

$rows = $pdo->query(
    "SELECT c.iso_a3, d.year, d.value
     FROM climate_data d JOIN countries c ON c.id = d.country_id
     WHERE d.indicator = 'nd_gain_vulnerability'
     ORDER BY d.year"
)->fetchAll();

// Scale with the base year's min/max, same as countries.impact_score
$baseVals = [];
foreach ($rows as $r) if ((int)$r['year'] === $base) $baseVals[] = (float)$r['value'];
if (!$baseVals) { echo json_encode(['years' => [], 'scores' => new stdClass(), 'bands' => [], 'base' => $base]); exit; }
$min = min($baseVals);
$max = max($baseVals);
$span = max($max - $min, 0.0001);

$scores = [];
$years  = [];
foreach ($rows as $r) {
    $s = (int)round(((float)$r['value'] - $min) / $span * 100);
    $scores[$r['iso_a3']][(int)$r['year']] = max(0, min(100, $s));
    $years[(int)$r['year']] = true;
}
$years = array_keys($years);
sort($years);

$bands = [];
foreach ([20, 40, 60, 80, 100] as $top) {
    [$label, $color] = score_level($top);
    $bands[] = ['max' => $top, 'color' => $color, 'label' => $label];
}

echo json_encode(['years' => $years, 'base' => $base, 'bands' => $bands, 'scores' => $scores]);
