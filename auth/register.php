<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';

if (current_user()) { header('Location: ' . url()); exit; }

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name     = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || mb_strlen($name) > 100)          $errors[] = 'Enter your name (max 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8)                           $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                          $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            login_user(['id' => $pdo->lastInsertId(), 'full_name' => $name, 'role' => 'user']);
            header('Location: ' . url());
            exit;
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/../includes/header.php';
?>
<section class="container auth-box">
  <h1>Create account</h1>
  <?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Full name <input type="text" name="full_name" value="<?= e($name) ?>" required></label>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label>Password <input type="password" name="password" minlength="8" required></label>
    <label>Confirm password <input type="password" name="confirm" minlength="8" required></label>
    <button class="btn" type="submit">Register</button>
  </form>
  <p>Already have an account? <a href="<?= url('auth/login.php') ?>">Log in</a></p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
