<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

$member = require_login();

if (member_access_status($member) === 'full_access') {
    flash_set('success', 'Anda sudah memiliki akses penuh sebagai anggota perpustakaan, tidak perlu berlangganan.');
    redirect('dashboard.php');
}

$plans = db_app()->query('SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY price ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $planId = (int)($_POST['plan_id'] ?? 0);
    $stmt = db_app()->prepare('SELECT * FROM membership_plans WHERE id = ? AND is_active = 1');
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();

    if (!$plan) {
        flash_set('error', 'Paket tidak ditemukan.');
        redirect('subscribe.php');
    }

    db_app()->beginTransaction();
    $ins = db_app()->prepare(
        "INSERT INTO member_subscriptions (member_id, plan_id, status) VALUES (?, ?, 'pending_payment')"
    );
    $ins->execute([$member['id'], $plan['id']]);
    $subscriptionId = db_app()->lastInsertId();

    $insPay = db_app()->prepare(
        "INSERT INTO payments (subscription_id, member_id, amount, method, status) VALUES (?, ?, ?, 'manual', 'pending')"
    );
    $insPay->execute([$subscriptionId, $member['id'], $plan['price']]);
    db_app()->commit();

    redirect('payment_upload.php?subscription_id=' . $subscriptionId);
}

$pageTitle = 'Pilih Paket Membership';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Pilih Paket Membership</h1>
<p style="color:var(--muted);">Setelah memilih paket, Anda akan diminta melakukan pembayaran manual (transfer) dan mengunggah bukti transfer.</p>

<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <div class="plan-grid">
    <?php foreach ($plans as $p): ?>
      <div class="plan-card <?= $p['duration_type'] === 'lifetime' ? 'lifetime' : '' ?>">
        <h3><?= e($p['name']) ?></h3>
        <div class="price"><?= rupiah($p['price']) ?></div>
        <p style="color:var(--muted);font-size:.88rem;min-height:40px;"><?= e($p['description']) ?></p>
        <p style="font-size:.85rem;"><?= e(duration_label($p['duration_type'], (int)$p['duration_value'])) ?></p>
        <button class="btn" style="width:100%;margin-top:10px;" type="submit" name="plan_id" value="<?= (int)$p['id'] ?>">Pilih Paket Ini</button>
      </div>
    <?php endforeach; ?>
  </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
