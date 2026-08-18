<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_admin()) {
    redirect('index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $admin = login_admin(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
    if ($admin) {
        redirect('index.php');
    }
    $error = 'Username atau password salah.';
}

$pageTitle = 'Login Admin';
require __DIR__ . '/_header.php';
?>
<div class="card" style="max-width:380px;margin:40px auto;">
  <h2>Login Admin</h2>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form class="stacked" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Username</label>
    <input type="text" name="username" required autofocus>
    <label>Password</label>
    <input type="password" name="password" required>
    <button class="btn" type="submit" style="margin-top:20px;width:100%;">Masuk</button>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
