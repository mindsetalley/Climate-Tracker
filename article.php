<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') not_found();

$stmt = $pdo->prepare(
    'SELECT a.*, c.name AS category, c.slug AS cat_slug, u.full_name AS author
     FROM articles a
     LEFT JOIN categories c ON c.id = a.category_id
     LEFT JOIN users u ON u.id = a.author_id
     WHERE a.slug = ? AND a.published_at <= NOW()'
);
$stmt->execute([$slug]);
$article = $stmt->fetch();
if (!$article) not_found('Article not found.');

// Related: same category, excluding this one
$related = [];
if ($article['category_id']) {
    $stmt = $pdo->prepare(
        'SELECT a.title, a.slug, a.summary, a.image_url, a.published_at, c.name AS category
         FROM articles a LEFT JOIN categories c ON c.id = a.category_id
         WHERE a.category_id = ? AND a.id <> ? AND a.published_at <= NOW()
         ORDER BY a.published_at DESC LIMIT 3'
    );
    $stmt->execute([$article['category_id'], $article['id']]);
    $related = $stmt->fetchAll();
}

$pageTitle = $article['title'];
require __DIR__ . '/includes/header.php';
?>
<article class="container article">
  <a href="<?= url('news.php') ?>">← All news</a>
  <?php if ($article['category']): ?>
    <p><a class="tag" href="<?= url('news.php?cat=' . urlencode($article['cat_slug'])) ?>"><?= e($article['category']) ?></a></p>
  <?php endif; ?>
  <h1><?= e($article['title']) ?></h1>
  <p class="meta">
    <?= date('j F Y', strtotime($article['published_at'])) ?>
    <?php if ($article['author']): ?> · by <?= e($article['author']) ?><?php endif; ?>
  </p>
  <?php if ($article['image_url']): ?>
    <img class="article-img" src="<?= e($article['image_url']) ?>" alt="">
  <?php endif; ?>

  <div class="article-body">
    <?php foreach (preg_split('/\R{2,}/', trim($article['content'])) as $para): ?>
      <p><?= nl2br(e($para)) ?></p>
    <?php endforeach; ?>
  </div>
</article>

<?php if ($related): ?>
<section class="container section">
  <h2>Related articles</h2>
  <div class="cards">
    <?php foreach ($related as $a) include __DIR__ . '/includes/article_card.php'; ?>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
