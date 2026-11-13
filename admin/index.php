<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$counts = [
    'Articles'   => $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
    'Categories' => $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'Users'      => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
];

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1>Admin Dashboard</h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="stats">
    <?php foreach ($counts as $label => $n): ?>
      <div class="stat"><span class="num"><?= (int)$n ?></span><span><?= e($label) ?></span></div>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
