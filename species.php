<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$cats   = ['mammal', 'bird', 'marine', 'reptile', 'amphibian', 'insect', 'plant'];
$q      = trim($_GET['q'] ?? '');
$cat    = in_array($_GET['cat'] ?? '', $cats, true) ? $_GET['cat'] : '';
$status = array_key_exists($_GET['status'] ?? '', status_map()) ? $_GET['status'] : '';

$where = [];
$params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(name LIKE ? OR scientific_name LIKE ?)';
    array_push($params, $like, $like);
}
if ($cat !== '')    { $where[] = 'category = ?';            $params[] = $cat; }
if ($status !== '') { $where[] = 'conservation_status = ?'; $params[] = $status; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT id, name, scientific_name, category, conservation_status, habitat, population_trend, image_url
     FROM species $whereSql
     ORDER BY FIELD(conservation_status,'EX','EW','CR','EN','VU','NT','LC'), name"
);
$stmt->execute($params);
$species = $stmt->fetchAll();

$pageTitle = 'Threatened Species';
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <h1>Threatened Species</h1>
  <p class="mute">Animals and plants whose survival is affected by a changing climate. Statuses follow the IUCN Red List.</p>

  <form method="get" class="filters">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name…">
    <select name="cat" onchange="this.form.submit()">
      <option value="">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e(ucfirst($c)) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" onchange="this.form.submit()">
      <option value="">All statuses</option>
      <?php foreach (status_map() as $code => $info): ?>
        <option value="<?= $code ?>" <?= $status === $code ? 'selected' : '' ?>><?= e($info[0]) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Search</button>
  </form>

  <p class="note"><?= count($species) ?> species</p>

  <?php if (!$species): ?>
    <p>No species match your filters.</p>
  <?php else: ?>
    <div class="species-grid">
      <?php foreach ($species as $s) include __DIR__ . '/includes/species_card.php'; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
