<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('SELECT image_url FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
        delete_upload($img ?: null);
        set_flash('Article deleted.');
    } elseif (($_POST['action'] ?? '') === 'feature') {
        $pdo->prepare('UPDATE articles SET is_featured = 1 - is_featured WHERE id = ?')->execute([$id]);
        set_flash('Featured status updated.');
    }
    header('Location: ' . url('admin/articles.php'));
    exit;
}

$rows = $pdo->query(
    'SELECT a.id, a.title, a.slug, a.is_featured, a.published_at, c.name AS category
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     ORDER BY a.published_at DESC'
)->fetchAll();

$pageTitle = 'Manage Articles';
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1>Articles</h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Title</th><th>Category</th><th>Published</th><th>Featured</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
      <?php $scheduled = strtotime($r['published_at']) > time(); ?>
      <tr>
        <td><a href="<?= url('article.php?slug=' . urlencode($r['slug'])) ?>"><?= e($r['title']) ?></a></td>
        <td><?= e($r['category'] ?? '—') ?></td>
        <td><?= date('j M Y H:i', strtotime($r['published_at'])) ?><?= $scheduled ? ' <span class="tag">Scheduled</span>' : '' ?></td>
        <td>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="feature">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="link-btn"><?= $r['is_featured'] ? '★ Yes' : '☆ No' ?></button>
          </form>
        </td>
        <td class="actions">
          <a href="<?= url('admin/article_form.php?id=' . (int)$r['id']) ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this article permanently?')">
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
