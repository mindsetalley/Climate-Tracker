<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$article = [
    'title' => '', 'summary' => '', 'content' => '', 'category_id' => '',
    'image_url' => '', 'is_featured' => 0, 'published_at' => date('Y-m-d H:i:s'),
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) not_found('Article not found.');
    $article = $existing;
}

$cats   = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oldImage = $article['image_url'];

    $article['title']       = trim($_POST['title'] ?? '');
    $article['summary']     = trim($_POST['summary'] ?? '');
    $article['content']     = trim($_POST['content'] ?? '');
    $article['category_id'] = $_POST['category_id'] ?? '';
    $article['image_url']   = trim($_POST['image_url'] ?? '');
    $article['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    if ($article['title'] === '' || mb_strlen($article['title']) > 200) $errors[] = 'Title is required (max 200 characters).';
    if (mb_strlen($article['summary']) > 300) $errors[] = 'Summary must be 300 characters or fewer.';
    if ($article['content'] === '') $errors[] = 'Content is required.';

    $catId = null;
    if ($article['category_id'] !== '') {
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $stmt->execute([(int)$article['category_id']]);
        if ($stmt->fetch()) $catId = (int)$article['category_id'];
        else $errors[] = 'Invalid category.';
    }

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['published_at'] ?? '');
    if (!$dt) $errors[] = 'Enter a valid publish date.';
    else $article['published_at'] = $dt->format('Y-m-d H:i:s');

    $img = $article['image_url'];
    if ($img !== '' && !is_upload_url($img)
        && !(filter_var($img, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $img))) {
        $errors[] = 'Image URL must start with http:// or https://';
    }

    $uploaded = handle_upload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], $errors);

    if (!$errors) {
        if ($uploaded) $article['image_url'] = $uploaded;
        $image = $article['image_url'] ?: null;

        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE articles SET category_id=?, title=?, summary=?, content=?, image_url=?, is_featured=?, published_at=? WHERE id=?'
            );
            $stmt->execute([$catId, $article['title'], $article['summary'], $article['content'],
                            $image, $article['is_featured'], $article['published_at'], $id]);
            if ($oldImage !== $image) delete_upload($oldImage);
            set_flash('Article updated.');
        } else {
            $slug = unique_slug($pdo, slugify($article['title']));
            $stmt = $pdo->prepare(
                'INSERT INTO articles (category_id, author_id, title, slug, summary, content, image_url, is_featured, published_at)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$catId, current_user()['id'], $article['title'], $slug, $article['summary'],
                            $article['content'], $image, $article['is_featured'], $article['published_at']]);
            set_flash('Article created.');
        }
        header('Location: ' . url('admin/articles.php'));
        exit;
    }
    if ($uploaded) delete_upload($uploaded);  // validation failed after upload: don't leave orphan files
}

$pageTitle = $id ? 'Edit article' : 'New article';
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1><?= $id ? 'Edit article' : 'New article' ?></h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post" enctype="multipart/form-data" class="form wide">
    <?= csrf_field() ?>
    <label>Title <input type="text" name="title" maxlength="200" value="<?= e($article['title']) ?>" required></label>
    <label>Summary <textarea name="summary" rows="2" maxlength="300"><?= e($article['summary']) ?></textarea></label>
    <label>Content <small>(blank line = new paragraph)</small>
      <textarea name="content" rows="14" required><?= e($article['content']) ?></textarea></label>
    <label>Category
      <select name="category_id">
        <option value="">— None —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (string)$article['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Upload image <small>(JPG/PNG/WebP, max 2 MB. Overrides the URL below.)</small>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
    <label>…or image URL <input type="text" name="image_url" value="<?= e($article['image_url']) ?>"></label>
    <?php if ($article['image_url']): ?><img class="preview" src="<?= e($article['image_url']) ?>" alt=""><?php endif; ?>
    <label>Publish date <small>(future date = scheduled)</small>
      <input type="datetime-local" name="published_at" value="<?= e(date('Y-m-d\TH:i', strtotime($article['published_at']))) ?>"></label>
    <label class="check"><input type="checkbox" name="is_featured" <?= $article['is_featured'] ? 'checked' : '' ?>> Featured on homepage</label>
    <button class="btn" type="submit"><?= $id ? 'Save changes' : 'Publish' ?></button>
  </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
