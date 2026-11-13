<nav class="admin-nav">
  <a href="<?= url('admin/') ?>">Dashboard</a>
  <a href="<?= url('admin/articles.php') ?>">Articles</a>
  <a href="<?= url('admin/article_form.php') ?>">+ New article</a>
  <a href="<?= url('admin/categories.php') ?>">Categories</a>
</nav>
<?php if ($msg = get_flash()): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
