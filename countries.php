<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$q         = trim($_GET['q'] ?? '');
$continent = trim($_GET['continent'] ?? '');
$sort      = ($_GET['sort'] ?? 'name') === 'score' ? 'score' : 'name';

$where = [];
$params = [];
if ($q !== '') {
    $where[]  = 'name LIKE ?';
    $params[] = '%' . addcslashes($q, '%_\\') . '%';
}
if ($continent !== '') {
    $where[]  = 'continent = ?';
    $params[] = $continent;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderSql = $sort === 'score' ? 'ORDER BY impact_score IS NULL, impact_score DESC, name' : 'ORDER BY name';

$stmt = $pdo->prepare("SELECT name, iso_a3, continent, impact_score FROM countries $whereSql $orderSql");
$stmt->execute($params);
$countries = $stmt->fetchAll();

$continents = $pdo->query('SELECT DISTINCT continent FROM countries WHERE continent IS NOT NULL ORDER BY continent')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Countries';
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <h1>Countries</h1>
  <p class="mute">Explore climate impact and vulnerability by country.</p>

  <form method="get" class="search-bar">
    <?php if ($continent): ?><input type="hidden" name="continent" value="<?= e($continent) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search a country…">
    <select name="sort" onchange="this.form.submit()">
      <option value="name"  <?= $sort === 'name'  ? 'selected' : '' ?>>Sort: A–Z</option>
      <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Sort: Highest impact</option>
    </select>
    <button class="btn" type="submit">Search</button>
  </form>

  <div class="pills">
    <a class="pill <?= $continent === '' ? 'on' : '' ?>" href="<?= url('countries.php' . qs(['continent' => ''])) ?>">All</a>
    <?php foreach ($continents as $co): ?>
      <a class="pill <?= $continent === $co ? 'on' : '' ?>" href="<?= url('countries.php' . qs(['continent' => $co])) ?>"><?= e($co) ?></a>
    <?php endforeach; ?>
  </div>

  <p class="note"><?= count($countries) ?> countr<?= count($countries) === 1 ? 'y' : 'ies' ?></p>

  <?php if (!$countries): ?>
    <p>No countries match your search.</p>
  <?php else: ?>
    <div class="country-grid">
      <?php foreach ($countries as $c): ?>
        <?php [$label, $color] = score_level($c['impact_score'] === null ? null : (int)$c['impact_score']); ?>
        <a class="country-card" href="<?= url('country.php?iso=' . urlencode($c['iso_a3'])) ?>">
          <strong><?= e($c['name']) ?></strong>
          <small><?= e($c['continent']) ?></small>
          <span class="badge" style="background:<?= $color ?>">
            <?= e($label) ?><?= $c['impact_score'] !== null ? ' · ' . (int)$c['impact_score'] : '' ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
