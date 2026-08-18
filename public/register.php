<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/membership.php';

if (current_member()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['username' => '', 'email' => '', 'full_name' => '', 'phone' => '', 'member_card' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['username']    = trim($_POST['username'] ?? '');
    $old['email']       = trim($_POST['email'] ?? '');
    $old['full_name']   = trim($_POST['full_name'] ?? '');
    $old['phone']       = trim($_POST['phone'] ?? '');
    $old['member_card'] = trim($_POST['member_card'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if ($old['username'] === '' || $old['email'] === '' || $old['full_name'] === '') {
        $errors[] = 'Username, email, dan nama lengkap wajib diisi.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if ($password !== $password2) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if (!$errors) {
        $exists = db_app()->prepare('SELECT id FROM app_members WHERE username = ? OR email = ?');
        $exists->execute([$old['username'], $old['email']]);
        if ($exists->fetch()) {
            $errors[] = 'Username atau email sudah terdaftar.';
        }
    }

    if (!$errors) {
        // Ketentuan #1: cocokkan dengan anggota SLiMS yang sudah ada
        $slimsMember = find_slims_member($old['email'], $old['member_card']);
        $isSlimsMember = $slimsMember && slims_membership_still_valid($slimsMember);

        $stmt = db_app()->prepare(
            'INSERT INTO app_members
             (slims_member_id, is_slims_member, username, email, password_hash, full_name, phone, access_type)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $slimsMember['member_id'] ?? null,
            $isSlimsMember ? 1 : 0,
            $old['username'],
            $old['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $old['full_name'],
            $old['phone'],
            $isSlimsMember ? 'full_access' : 'regular',
        ]);

        $_SESSION['member_id'] = db_app()->lastInsertId();

        if ($isSlimsMember) {
            flash_set('success', 'Akun berhasil dibuat. Anda terdeteksi sebagai anggota perpustakaan terdaftar sehingga langsung mendapat akses penuh ke koleksi membership.');
        } else {
            flash_set('success', 'Akun berhasil dibuat. Untuk membaca koleksi bertanda "Membership" secara penuh, silakan pilih paket berlangganan.');
        }
        redirect('dashboard.php');
    }
}

$pageTitle = 'Daftar Member';
require __DIR__ . '/_header.php';
?>
<div class="card" style="max-width:520px;margin:0 auto;">
  <h2>Daftar Member Baru</h2>
  <p style="color:var(--muted);font-size:.9rem;">
    Jika Anda sudah terdaftar sebagai anggota perpustakaan (SLiMS), isi juga
    <em>No. Anggota</em> agar sistem otomatis memberi akses penuh tanpa perlu berlangganan.
  </p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form class="stacked" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label>Nama Lengkap</label>
    <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required>

    <label>No. Anggota Perpustakaan (jika sudah punya)</label>
    <input type="text" name="member_card" value="<?= e($old['member_card']) ?>" placeholder="Opsional">

    <label>Email</label>
    <input type="email" name="email" value="<?= e($old['email']) ?>" required>

    <label>No. HP</label>
    <input type="tel" name="phone" value="<?= e($old['phone']) ?>">

    <label>Username</label>
    <input type="text" name="username" value="<?= e($old['username']) ?>" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Konfirmasi Password</label>
    <input type="password" name="password_confirm" required>

    <button class="btn" type="submit" style="margin-top:20px;width:100%;">Daftar</button>
  </form>
  <p style="margin-top:16px;font-size:.88rem;">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
