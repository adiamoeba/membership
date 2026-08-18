<?php
$admin = current_admin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Admin <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="index.php"><span class="brand-mark">&#9776;</span> Admin Panel</a>
    <?php if ($admin): ?>
    <nav>
      <a href="plans.php">Paket</a>
      <a href="biblio_membership.php">Mapping Biblio</a>
      <a href="payments.php">Pembayaran</a>
      <a href="logout.php">Keluar (<?= e($admin['full_name']) ?>)</a>
    </nav>
    <?php endif; ?>
  </div>
</header>
<main class="wrap page">
<?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
