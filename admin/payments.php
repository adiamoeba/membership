<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $paymentId = (int)$_POST['payment_id'];
    $action = $_POST['action'] ?? '';

    $stmt = db_app()->prepare(
        'SELECT pay.*, s.plan_id, p.duration_type, p.duration_value
         FROM payments pay
         JOIN member_subscriptions s ON s.id = pay.subscription_id
         JOIN membership_plans p ON p.id = s.plan_id
         WHERE pay.id = ?'
    );
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if ($payment) {
        if ($action === 'confirm') {
            db_app()->beginTransaction();
            db_app()->prepare(
                'UPDATE payments SET status="confirmed", confirmed_by=?, confirmed_at=NOW() WHERE id=?'
            )->execute([current_admin()['username'], $paymentId]);

            $endDate = calculate_subscription_end($payment['duration_type'], (int)$payment['duration_value']);
            db_app()->prepare(
                'UPDATE member_subscriptions SET status="active", start_date=CURDATE(), end_date=? WHERE id=?'
            )->execute([$endDate, $payment['subscription_id']]);
            db_app()->commit();
            flash_set('success', 'Pembayaran dikonfirmasi, membership diaktifkan.');
        } elseif ($action === 'reject') {
            db_app()->prepare(
                'UPDATE payments SET status="rejected", confirmed_by=?, confirmed_at=NOW() WHERE id=?'
            )->execute([current_admin()['username'], $paymentId]);
            db_app()->prepare('UPDATE member_subscriptions SET status="rejected" WHERE id=?')
                ->execute([$payment['subscription_id']]);
            flash_set('success', 'Pembayaran ditolak.');
        }
    }
    redirect('payments.php');
}

$payments = db_app()->query(
    "SELECT pay.*, m.full_name, m.email, s.plan_id, mp.name AS plan_name
     FROM payments pay
     JOIN app_members m ON m.id = pay.member_id
     JOIN member_subscriptions s ON s.id = pay.subscription_id
     JOIN membership_plans mp ON mp.id = s.plan_id
     ORDER BY (pay.status='pending') DESC, pay.id DESC"
)->fetchAll();

$pageTitle = 'Konfirmasi Pembayaran';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Konfirmasi Pembayaran Manual</h1>

<table class="simple">
  <thead><tr><th>Member</th><th>Paket</th><th>Jumlah</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr></thead>
  <tbody>
  <?php foreach ($payments as $p): ?>
    <tr>
      <td><?= e($p['full_name']) ?><br><span class="meta" style="color:var(--muted);"><?= e($p['email']) ?></span></td>
      <td><?= e($p['plan_name']) ?></td>
      <td><?= rupiah($p['amount']) ?></td>
      <td>
        <?php if ($p['proof_file']): ?>
          <a href="view_proof.php?payment_id=<?= (int)$p['id'] ?>" target="_blank">Lihat File</a>
        <?php else: ?>
          <span style="color:var(--muted);">Belum diunggah</span>
        <?php endif; ?>
      </td>
      <td><span class="status-pill status-<?= $p['status'] === 'confirmed' ? 'active' : ($p['status'] === 'rejected' ? 'rejected' : 'pending') ?>"><?= e($p['status']) ?></span></td>
      <td>
        <?php if ($p['status'] === 'pending' && $p['proof_file']): ?>
          <form method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="action" value="confirm">
            <button class="btn" type="submit" style="padding:5px 10px;font-size:.8rem;">Konfirmasi</button>
          </form>
          <form method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="action" value="reject">
            <button type="submit" style="padding:5px 10px;font-size:.8rem;background:none;border:1px solid var(--danger);color:var(--danger);border-radius:3px;cursor:pointer;">Tolak</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php require __DIR__ . '/_footer.php'; ?>
