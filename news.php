<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$perPage = 9;
$q       = trim($_GET['q'] ?? '');
$catSlug = trim($_GET['cat'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));

// Build WHERE clause dynamically
$where  = ['a.published_at <= NOW()'];
$params = [];

if ($catSlug !== '') {
    $where[]  = 'c.slug = ?';
    $params[] = $catSlug;
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[]  = '(a.title LIKE ? OR a.summary LIKE ? OR a.content LIKE ?)';
    array_push($params, $like, $like, $like);
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

// Total for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM articles a LEFT JOIN categories c ON c.id = a.category_id $whereSql");
$stmt->execute($params);
$total      = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// Page of articles
$sql = "SELECT a.title, a.slug, a.summary, a.image_url, a.published_at, c.name AS category
        FROM articles a LEFT JOIN categories c ON c.id = a.category_id
        $whereSql ORDER BY a.published_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

// Categories with article counts, for the filter pills
$cats = $pdo->query(
    'SELECT c.name, c.slug, COUNT(a.id) AS n
     FROM categories c LEFT JOIN articles a ON a.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();

$pageTitle = 'Climate News';
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <h1>Climate News</h1>

  <form method="get" class="search-bar">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search articles…">
  <button class="btn" type="submit">Search</button>
</form>

  <div class="pills">
    <a class="pill <?= $catSlug === '' ? 'on' : '' ?>" href="<?= url('news.php' . qs(['cat' => '', 'page' => ''])) ?>">All</a>
    <?php foreach ($cats as $c): ?>
      <a class="pill <?= $catSlug === $c['slug'] ? 'on' : '' ?>"
         href="<?= url('news.php' . qs(['cat' => $c['slug'], 'page' => ''])) ?>">
        <?= e($c['name']) ?> (<?= (int)$c['n'] ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <p class="note"><?= $total ?> article<?= $total === 1 ? '' : 's' ?> found<?= $q ? ' for “' . e($q) . '”' : '' ?></p>

  <?php if (!$articles): ?>
    <p>No articles match your search.</p>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($articles as $a) include __DIR__ . '/includes/article_card.php'; ?>
    </div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
    <nav class="pager">
      <?php if ($page > 1): ?><a href="<?= url('news.php' . qs(['page' => $page - 1])) ?>">← Prev</a><?php endif; ?>
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a class="<?= $p === $page ? 'on' : '' ?>" href="<?= url('news.php' . qs(['page' => $p])) ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?><a href="<?= url('news.php' . qs(['page' => $page + 1])) ?>">Next →</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
