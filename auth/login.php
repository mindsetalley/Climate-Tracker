<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';

$next = $_GET['next'] ?? $_POST['next'] ?? '';
if (current_user()) { header('Location: ' . safe_next($next)); exit; }

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, full_name, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user);
        header('Location: ' . safe_next($next));
        exit;
    }
    $error = 'Invalid email or password.';   // same message either way
}

$pageTitle = 'Log in';
require __DIR__ . '/../includes/header.php';
?>
<section class="container auth-box">
  <h1>Log in</h1>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p>No account? <a href="<?= url('auth/register.php') ?>">Register</a></p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
