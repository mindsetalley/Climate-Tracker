<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_name('climate_sid');   // separate cookie from your other localhost projects
    session_start();
}

function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    if (empty($_SESSION['user_id'])) return $cache = null;

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, full_name, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();

    if (!$u) {                       // stale session, e.g. user deleted
        $_SESSION = [];
        return $cache = null;
    }
    return $cache = ['id' => (int)$u['id'], 'name' => $u['full_name'], 'role' => $u['role']];
}

function login_user(array $user): void {
    session_regenerate_id(true);   // prevents session fixation
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
}

function require_login(): void {
    if (!current_user()) {
        header('Location: ' . url('auth/login.php?next=' . urlencode($_SERVER['REQUEST_URI'])));
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (current_user()['role'] !== 'admin') {
        http_response_code(403);
        exit('403 – Admins only.');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid request token.');
    }
}

// Only allow redirects to paths inside this site
function safe_next(?string $next): string {
    if ($next && str_starts_with($next, BASE_URL . '/') && !str_contains($next, '//')) return $next;
    return url();
}
