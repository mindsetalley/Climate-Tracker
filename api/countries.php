<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

$rows = $pdo->query(
    'SELECT name, iso_a3, continent, latitude, longitude, impact_score, summary FROM countries'
)->fetchAll();

$out = [];
foreach ($rows as $r) {
    $score = $r['impact_score'] === null ? null : (int)$r['impact_score'];
    [$label, $color] = score_level($score);
    $out[$r['iso_a3']] = [
        'name'      => $r['name'],
        'continent' => $r['continent'],
        'lat'       => $r['latitude']  === null ? null : (float)$r['latitude'],
        'lng'       => $r['longitude'] === null ? null : (float)$r['longitude'],
        'score'     => $score,
        'label'     => $label,
        'color'     => $color,
        'summary'   => $r['summary'],
    ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
