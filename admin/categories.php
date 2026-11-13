<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 60) {
            $errors[] = 'Enter a category name (max 60 characters).';
        } else {
            try {
                $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')->execute([$name, slugify($name)]);
                set_flash('Category added.');
                header('Location: ' . url('admin/categories.php'));
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') $errors[] = 'That category already exists.';
                else throw $e;
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        set_flash('Category deleted. Its articles are now uncategorised.');
        header('Location: ' . url('admin/categories.php'));
        exit;
    }
}

$cats = $pdo->query(
    'SELECT c.id, c.name, c.slug, COUNT(a.id) AS n
     FROM categories c LEFT JOIN articles a ON a.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();

$pageTitle = 'Manage Categories';
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1>Categories</h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post" class="search-bar">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" placeholder="New category name" maxlength="60" required>
    <button class="btn" type="submit">Add</button>
  </form>

  <div class="table-wrap">
  <table class="table">
    <tr><th>Name</th><th>Slug</th><th>Articles</th><th></th></tr>
    <?php foreach ($cats as $c): ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><?= e($c['slug']) ?></td>
        <td><?= (int)$c['n'] ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Delete this category?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="link-btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
