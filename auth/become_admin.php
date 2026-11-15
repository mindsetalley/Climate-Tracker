<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_login();

if (current_user()['role'] === 'admin') { header('Location: ' . url('admin/')); exit; }

$cfgFile = __DIR__ . '/../config/admin_pin.php';
$cfg     = is_file($cfgFile) ? require $cfgFile : [];
$pinHash = $cfg['pin_hash'] ?? null;

$uid   = current_user()['id'];
$error = '';
$maxTries = 5;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_attempts WHERE user_id = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
$stmt->execute([$uid]);
$recent = (int)$stmt->fetchColumn();
$locked = $recent >= $maxTries;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($locked) {
        $error = 'Too many attempts. Try again in 15 minutes.';
    } elseif (!$pinHash) {
        $error = 'Admin access is not configured on this server.';
    } elseif (password_verify($_POST['pin'] ?? '', $pinHash)) {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$uid]);
        $pdo->prepare('DELETE FROM admin_attempts WHERE user_id = ?')->execute([$uid]);
        set_flash('You are now an admin.');
        header('Location: ' . url('admin/'));
        exit;
    } else {
        $pdo->prepare('INSERT INTO admin_attempts (user_id) VALUES (?)')->execute([$uid]);
        $left  = $maxTries - $recent - 1;
        $error = $left > 0 ? "Incorrect code. $left attempt(s) left." : 'Too many attempts. Try again in 15 minutes.';
    }
}

$pageTitle = 'Admin access';
require __DIR__ . '/../includes/header.php';
?>
<section class="container auth-box">
  <h1>Admin access</h1>
  <p class="note">Enter the admin access code to upgrade your account.</p>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Access code <input type="password" name="pin" autocomplete="off" required></label>
    <button class="btn" type="submit">Verify</button>
  </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
