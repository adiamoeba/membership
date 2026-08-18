<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_member()) {
    redirect('dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = trim($_POST['identity'] ?? '');
    $pw = $_POST['password'] ?? '';
    $member = login_member($id, $pw);
    if ($member) {
        redirect('dashboard.php');
    }
    $error = 'Username/email atau password salah.';
}

$pageTitle = 'Masuk';
require __DIR__ . '/_header.php';
?>
<div class="card" style="max-width:420px;margin:0 auto;">
  <h2>Masuk</h2>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form class="stacked" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Username atau Email</label>
    <input type="text" name="identity" required autofocus>
    <label>Password</label>
    <input type="password" name="password" required>
    <button class="btn" type="submit" style="margin-top:20px;width:100%;">Masuk</button>
  </form>
  <p style="margin-top:16px;font-size:.88rem;">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
