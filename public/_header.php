<?php
/** @var array|null $member */
$member = current_member();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="index.php"><span class="brand-mark">&#9776;</span> <?= e(APP_NAME) ?></a>
    <nav>
      <a href="biblio_list.php">Koleksi</a>
      <?php if ($member): ?>
        <a href="dashboard.php">Akun Saya</a>
        <a href="logout.php">Keluar</a>
      <?php else: ?>
        <a href="login.php">Masuk</a>
        <a class="btn-nav" href="register.php">Daftar</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap page">
<?php if ($msg = flash_get('error')): ?>
  <div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('success')): ?>
  <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
