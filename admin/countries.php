<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$rows = $pdo->query('SELECT id, name, iso_a3, continent, impact_score FROM countries ORDER BY name')->fetchAll();

$pageTitle = 'Manage Countries';
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1>Countries</h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Name</th><th>ISO</th><th>Continent</th><th>Score</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
      <?php [$label, $color] = score_level($r['impact_score'] === null ? null : (int)$r['impact_score']); ?>
      <tr>
        <td><a href="<?= url('country.php?iso=' . urlencode($r['iso_a3'])) ?>"><?= e($r['name']) ?></a></td>
        <td><?= e($r['iso_a3']) ?></td>
        <td><?= e($r['continent']) ?></td>
        <td><span class="badge" style="background:<?= $color ?>"><?= $r['impact_score'] ?? '—' ?></span></td>
        <td><a href="<?= url('admin/country_edit.php?id=' . (int)$r['id']) ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
