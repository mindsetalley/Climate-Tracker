<?php
const BASE_URL = '/climate-tracker'; // change if your folder name differs

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

function excerpt(string $text, int $len = 140): string {
    $text = trim(strip_tags($text));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
}

function not_found(string $msg = 'Page not found.'): never {
    http_response_code(404);
    $pageTitle = '404';
    require __DIR__ . '/header.php';
    echo '<section class="container section"><h1>404</h1><p>' . e($msg) . '</p></section>';
    require __DIR__ . '/footer.php';
    exit;
}

// Build a query string from the current filters, overriding some values
function qs(array $override = []): string {
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return $params ? '?' . http_build_query($params) : '';
}

date_default_timezone_set('Africa/Accra');

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'article';
}

function unique_slug(PDO $pdo, string $base, int $ignoreId = 0): string {
    $base = mb_substr($base, 0, 200);
    $slug = $base;
    $i = 2;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE slug = ? AND id <> ?');
    while (true) {
        $stmt->execute([$slug, $ignoreId]);
        if (!$stmt->fetchColumn()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

function set_flash(string $msg): void { $_SESSION['flash'] = $msg; }
function get_flash(): ?string {
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

const UPLOAD_PREFIX = BASE_URL . '/assets/img/uploads/';

function is_upload_url(?string $u): bool {
    return $u && str_starts_with($u, UPLOAD_PREFIX);
}

function delete_upload(?string $u): void {
    if (!is_upload_url($u)) return;
    $file = __DIR__ . '/../assets/img/uploads/' . basename($u);
    if (is_file($file)) unlink($file);
}

// Returns the new image URL, or null if nothing was uploaded / it failed (error appended)
function handle_upload(array $file, array &$errors): ?string {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) { $errors[] = 'Image upload failed.'; return null; }
    if ($file['size'] > 2 * 1024 * 1024) { $errors[] = 'Image must be under 2 MB.'; return null; }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) { $errors[] = 'Only JPG, PNG or WebP images are allowed.'; return null; }

    $dir = __DIR__ . '/../assets/img/uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        $errors[] = 'Could not save the uploaded image.';
        return null;
    }
    return UPLOAD_PREFIX . $name;
}
