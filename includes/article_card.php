<article class="card">
  <?php if (!empty($a['image_url'])): ?>
    <img src="<?= e($a['image_url']) ?>" alt="">
  <?php endif; ?>
  <div class="card-body">
    <span class="tag"><?= e($a['category'] ?? 'Uncategorised') ?></span>
    <h3><a href="<?= url('article.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a></h3>
    <p><?= e($a['summary']) ?></p>
    <small><?= date('j M Y', strtotime($a['published_at'])) ?></small>
  </div>
</article>
