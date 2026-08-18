<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$totalMembers = db_app()->query('SELECT COUNT(*) c FROM app_members')->fetch()['c'];
$fullAccess = db_app()->query("SELECT COUNT(*) c FROM app_members WHERE access_type='full_access'")->fetch()['c'];
$activeSubs = db_app()->query("SELECT COUNT(*) c FROM member_subscriptions WHERE status='active' AND (end_date IS NULL OR end_date >= CURDATE())")->fetch()['c'];
$pendingPayments = db_app()->query("SELECT COUNT(*) c FROM payments WHERE status='pending' AND proof_file IS NOT NULL")->fetch()['c'];
$mappedBiblio = db_app()->query('SELECT COUNT(*) c FROM biblio_membership WHERE requires_membership = 1')->fetch()['c'];

$pageTitle = 'Dashboard';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Dashboard Admin</h1>
<div class="grid grid-3">
  <div class="card"><div class="meta">Total Member</div><h2><?= (int)$totalMembers ?></h2></div>
  <div class="card"><div class="meta">Anggota Full Access (SLiMS)</div><h2><?= (int)$fullAccess ?></h2></div>
  <div class="card"><div class="meta">Langganan Aktif</div><h2><?= (int)$activeSubs ?></h2></div>
  <div class="card"><div class="meta">Pembayaran Menunggu Konfirmasi</div><h2><?= (int)$pendingPayments ?></h2>
    <a href="payments.php">Lihat &rarr;</a></div>
  <div class="card"><div class="meta">Judul Ditandai Membership</div><h2><?= (int)$mappedBiblio ?></h2>
    <a href="biblio_membership.php">Kelola &rarr;</a></div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
