<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$cats   = ['mammal', 'bird', 'marine', 'reptile', 'amphibian', 'insect', 'plant'];
$trends = ['increasing', 'stable', 'decreasing', 'unknown'];

$id = (int)($_GET['id'] ?? 0);
$sp = ['name' => '', 'scientific_name' => '', 'category' => 'mammal', 'conservation_status' => 'LC',
       'habitat' => '', 'population_trend' => 'unknown', 'image_url' => ''];
$threatsText = $regionsText = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM species WHERE id = ?');
    $stmt->execute([$id]);
    $sp = $stmt->fetch();
    if (!$sp) not_found('Species not found.');

    $stmt = $pdo->prepare('SELECT threat_type, description FROM species_threats WHERE species_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $threatsText = implode("\n", array_map(fn($t) => $t['threat_type'] . ' | ' . $t['description'], $stmt->fetchAll()));

    $stmt = $pdo->prepare('SELECT latitude, longitude, label FROM species_regions WHERE species_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $regionsText = implode("\n", array_map(fn($r) => (float)$r['latitude'] . ', ' . (float)$r['longitude'] . ', ' . $r['label'], $stmt->fetchAll()));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['name', 'scientific_name', 'habitat', 'image_url'] as $f) $sp[$f] = trim($_POST[$f] ?? '');
    $sp['category']            = $_POST['category'] ?? '';
    $sp['conservation_status'] = $_POST['conservation_status'] ?? '';
    $sp['population_trend']    = $_POST['population_trend'] ?? '';
    $threatsText = $_POST['threats'] ?? '';
    $regionsText = $_POST['regions'] ?? '';

    if ($sp['name'] === '' || mb_strlen($sp['name']) > 120)             $errors[] = 'Name is required (max 120 characters).';
    if (mb_strlen($sp['scientific_name']) > 150)                        $errors[] = 'Scientific name is too long.';
    if (mb_strlen($sp['habitat']) > 255)                                $errors[] = 'Habitat is too long.';
    if (!in_array($sp['category'], $cats, true))                        $errors[] = 'Invalid category.';
    if (!array_key_exists($sp['conservation_status'], status_map()))    $errors[] = 'Invalid conservation status.';
    if (!in_array($sp['population_trend'], $trends, true))              $errors[] = 'Invalid population trend.';
    if ($sp['image_url'] !== '' && !preg_match('#^https?://#i', $sp['image_url'])) $errors[] = 'Image URL must start with http:// or https://';

    $threats = [];
    foreach (preg_split('/\R/', trim($threatsText)) as $n => $line) {
        $line = trim($line);
        if ($line === '') continue;
        [$t, $d] = array_pad(explode('|', $line, 2), 2, '');
        $t = trim($t); $d = trim($d);
        if ($t === '' || mb_strlen($t) > 80) { $errors[] = 'Threat line ' . ($n + 1) . ': type is required (max 80 characters).'; continue; }
        $threats[] = [$t, $d !== '' ? $d : null];
    }

    $regions = [];
    foreach (preg_split('/\R/', trim($regionsText)) as $n => $line) {
        $line = trim($line);
        if ($line === '') continue;
        [$lat, $lng, $label] = array_pad(array_map('trim', explode(',', $line, 3)), 3, '');
        if (!is_numeric($lat) || !is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            $errors[] = 'Region line ' . ($n + 1) . ': use "latitude, longitude, label" with valid coordinates.';
            continue;
        }
        if (mb_strlen($label) > 80) { $errors[] = 'Region line ' . ($n + 1) . ': label is too long (max 80).'; continue; }
        $regions[] = [(float)$lat, (float)$lng, $label !== '' ? $label : null];
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $vals = [$sp['name'], $sp['scientific_name'] ?: null, $sp['category'], $sp['conservation_status'],
                     $sp['habitat'] ?: null, $sp['population_trend'], $sp['image_url'] ?: null];
            if ($id) {
                $pdo->prepare('UPDATE species SET name=?, scientific_name=?, category=?, conservation_status=?, habitat=?, population_trend=?, image_url=? WHERE id=?')
                    ->execute([...$vals, $id]);
            } else {
                $pdo->prepare('INSERT INTO species (name, scientific_name, category, conservation_status, habitat, population_trend, image_url) VALUES (?,?,?,?,?,?,?)')
                    ->execute($vals);
                $id = (int)$pdo->lastInsertId();
            }
            $pdo->prepare('DELETE FROM species_threats WHERE species_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM species_regions WHERE species_id = ?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO species_threats (species_id, threat_type, description) VALUES (?,?,?)');
            foreach ($threats as $t) $ins->execute([$id, $t[0], $t[1]]);
            $ins = $pdo->prepare('INSERT INTO species_regions (species_id, latitude, longitude, label) VALUES (?,?,?,?)');
            foreach ($regions as $r) $ins->execute([$id, $r[0], $r[1], $r[2]]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        set_flash('Species saved.');
        header('Location: ' . url('admin/species.php'));
        exit;
    }
}

$pageTitle = $id ? 'Edit species' : 'New species';
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1><?= $id ? 'Edit species' : 'New species' ?></h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post" class="form wide">
    <?= csrf_field() ?>
    <label>Name <input type="text" name="name" maxlength="120" value="<?= e($sp['name']) ?>" required></label>
    <label>Scientific name <input type="text" name="scientific_name" maxlength="150" value="<?= e($sp['scientific_name']) ?>"></label>
    <label>Category
      <select name="category">
        <?php foreach ($cats as $c): ?><option value="<?= $c ?>" <?= $sp['category'] === $c ? 'selected' : '' ?>><?= e(ucfirst($c)) ?></option><?php endforeach; ?>
      </select></label>
    <label>IUCN status
      <select name="conservation_status">
        <?php foreach (status_map() as $code => $info): ?><option value="<?= $code ?>" <?= $sp['conservation_status'] === $code ? 'selected' : '' ?>><?= e($code . ' – ' . $info[0]) ?></option><?php endforeach; ?>
      </select></label>
    <label>Population trend
      <select name="population_trend">
        <?php foreach ($trends as $t): ?><option value="<?= $t ?>" <?= $sp['population_trend'] === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option><?php endforeach; ?>
      </select></label>
    <label>Habitat <input type="text" name="habitat" maxlength="255" value="<?= e($sp['habitat']) ?>"></label>
    <label>Image URL <input type="text" name="image_url" value="<?= e($sp['image_url']) ?>"></label>
    <label>Climate threats <small>(one per line: Type | Description)</small>
      <textarea name="threats" rows="5"><?= e($threatsText) ?></textarea></label>
    <label>Range locations <small>(one per line: latitude, longitude, label)</small>
      <textarea name="regions" rows="5"><?= e($regionsText) ?></textarea></label>
    <button class="btn" type="submit">Save species</button>
  </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
