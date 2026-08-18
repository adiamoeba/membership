<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/membership.php';

$member = require_login();
$status = member_access_status($member);
$activeSub = member_active_subscription($member);

$stmt = db_app()->prepare(
    "SELECT s.*, p.name AS plan_name, p.price,
            (SELECT status FROM payments WHERE subscription_id = s.id ORDER BY id DESC LIMIT 1) AS payment_status,
            (SELECT id FROM payments WHERE subscription_id = s.id ORDER BY id DESC LIMIT 1) AS payment_id
     FROM member_subscriptions s
     JOIN membership_plans p ON p.id = s.plan_id
     WHERE s.member_id = ?
     ORDER BY s.id DESC"
);
$stmt->execute([$member['id']]);
$subscriptions = $stmt->fetchAll();

$pageTitle = 'Akun Saya';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Halo, <?= e($member['full_name']) ?></h1>

<div class="card" style="margin-bottom:26px;">
  <?php if ($status === 'full_access'): ?>
    <span class="badge badge-lifetime">Anggota Perpustakaan</span>
    <p style="margin:10px 0 0;">Anda terdaftar sebagai anggota perpustakaan dan memiliki
      <strong>akses penuh</strong> ke seluruh koleksi membership tanpa perlu berlangganan.</p>
  <?php elseif ($status === 'active_subscription'): ?>
    <span class="badge badge-membership">Membership Aktif</span>
    <p style="margin:10px 0 0;">Paket: <strong><?= e($activeSub['plan_name']) ?></strong> &middot;
      Berlaku sampai:
      <strong><?= $activeSub['end_date'] ? e(date('d M Y', strtotime($activeSub['end_date']))) : 'Selamanya' ?></strong>
    </p>
  <?php else: ?>
    <span class="badge badge-free">Belum Berlangganan</span>
    <p style="margin:10px 0 0;">Anda belum memiliki membership aktif. Beberapa koleksi hanya
      bisa dibaca sebagian (pratinjau). <a href="subscribe.php">Pilih paket membership &rarr;</a></p>
  <?php endif; ?>
</div>

<h2>Riwayat Langganan</h2>
<?php if (!$subscriptions): ?>
  <p style="color:var(--muted);">Belum ada riwayat langganan.</p>
<?php else: ?>
  <table class="simple">
    <thead><tr><th>Paket</th><th>Mulai</th><th>Berakhir</th><th>Status</th><th>Pembayaran</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($subscriptions as $s): ?>
      <tr>
        <td><?= e($s['plan_name']) ?> (<?= rupiah($s['price']) ?>)</td>
        <td><?= $s['start_date'] ? e(date('d M Y', strtotime($s['start_date']))) : '-' ?></td>
        <td><?= $s['end_date'] ? e(date('d M Y', strtotime($s['end_date']))) : 'Selamanya' ?></td>
        <td><span class="status-pill status-<?= e($s['status'] === 'active' ? 'active' : ($s['status'] === 'pending_payment' ? 'pending' : 'expired')) ?>"><?= e($s['status']) ?></span></td>
        <td><?= $s['payment_status'] ? e($s['payment_status']) : '-' ?></td>
        <td>
          <?php if ($s['status'] === 'pending_payment' && $s['payment_status'] !== 'confirmed'): ?>
            <a href="payment_upload.php?subscription_id=<?= (int)$s['id'] ?>">Upload/Cek Bukti Bayar</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
