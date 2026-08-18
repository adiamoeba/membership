<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Beranda';
require __DIR__ . '/_header.php';
?>
<section class="card" style="text-align:center;padding:48px 24px;">
  <h1 style="font-size:2rem;margin:0 0 10px;">Baca Koleksi Digital Perpustakaan</h1>
  <p style="color:var(--muted);max-width:620px;margin:0 auto 22px;">
    Sebagian koleksi bisa dibaca gratis. Untuk judul bertanda
    <span class="badge badge-membership">Membership</span>, Anda perlu menjadi anggota
    untuk membaca lebih dari halaman pratinjau.
    Anggota perpustakaan yang sudah terdaftar otomatis mendapat akses penuh.
  </p>
  <a class="btn" href="biblio_list.php">Jelajahi Koleksi</a>
  <?php if (!current_member()): ?>
    <a class="btn btn-outline" href="register.php" style="margin-left:10px;">Daftar Member</a>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
