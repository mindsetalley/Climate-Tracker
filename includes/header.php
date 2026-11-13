<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'Global Climate Change Tracker';
$current = basename($_SERVER['SCRIPT_NAME']);
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Climate Tracker</title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="container nav-wrap">
    <a href="<?= url() ?>" class="logo">🌍 Climate<span>Tracker</span></a>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">☰</button>
    <nav id="mainNav">
      <a href="<?= url() ?>" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>
      <a href="<?= url('news.php') ?>">News</a>
      <a href="<?= url('tracker.php') ?>">Climate Tracker</a>
      <a href="<?= url('countries.php') ?>">Countries</a>
      <a href="<?= url('species.php') ?>">Species</a>
      <a href="<?= url('about.php') ?>">About</a>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?><a href="<?= url('admin/') ?>">Admin</a><?php endif; ?>
        <form method="post" action="<?= url('auth/logout.php') ?>" class="nav-form">
          <?= csrf_field() ?>
          <button type="submit">Log out (<?= e($user['name']) ?>)</button>
        </form>
      <?php else: ?>
        <a href="<?= url('auth/login.php') ?>">Log in</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main>
