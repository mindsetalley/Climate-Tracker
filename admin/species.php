<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $pdo->prepare('DELETE FROM species WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    set_flash('Species deleted.');
    header('Location: ' . url('admin/species.php'));
    exit;
}

$rows = $pdo->query('SELECT id, name, category, conservation_status, population_trend FROM species ORDER BY name')->fetchAll();

$pageTitle = 'Manage Species';
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1>Species</h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <p><a class="btn" href="<?= url('admin/species_form.php') ?>">+ New species</a></p>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Name</th><th>Category</th><th>Status</th><th>Trend</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
      <?php [$sl, $sbg, $sfg] = status_info($r['conservation_status']); ?>
      <tr>
        <td><a href="<?= url('species_view.php?id=' . (int)$r['id']) ?>"><?= e($r['name']) ?></a></td>
        <td><?= e(ucfirst($r['category'])) ?></td>
        <td><span class="badge" style="background:<?= $sbg ?>;color:<?= $sfg ?>"><?= e($r['conservation_status']) ?></span></td>
        <td><?= e(trend_label($r['population_trend'])) ?></td>
        <td class="actions">
          <a href="<?= url('admin/species_form.php?id=' . (int)$r['id']) ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this species?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="link-btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
