<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

$member = require_login();
$subscriptionId = (int)($_GET['subscription_id'] ?? $_POST['subscription_id'] ?? 0);

$stmt = db_app()->prepare(
    "SELECT s.*, p.name AS plan_name, p.price, p.duration_type, p.duration_value
     FROM member_subscriptions s JOIN membership_plans p ON p.id = s.plan_id
     WHERE s.id = ? AND s.member_id = ?"
);
$stmt->execute([$subscriptionId, $member['id']]);
$subscription = $stmt->fetch();
if (!$subscription) {
    redirect('dashboard.php');
}

$payStmt = db_app()->prepare('SELECT * FROM payments WHERE subscription_id = ? ORDER BY id DESC LIMIT 1');
$payStmt->execute([$subscriptionId]);
$payment = $payStmt->fetch();

$gatewayInfo = payment_gateway()->createPayment($subscription, $subscription, $member);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['proof'])) {
    csrf_check();
    $bankRef = trim($_POST['bank_reference'] ?? '');
    $file = $_FILES['proof'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'Gagal mengunggah file. Coba lagi.');
    } else {
        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            flash_set('error', 'Format file harus jpg, png, atau pdf.');
        } else {
            if (!is_dir(PAYMENT_PROOF_DIR)) {
                mkdir(PAYMENT_PROOF_DIR, 0755, true);
            }
            $newName = 'proof_' . $subscriptionId . '_' . time() . '.' . $ext;
            $dest = rtrim(PAYMENT_PROOF_DIR, '/') . '/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $upd = db_app()->prepare(
                    'UPDATE payments SET proof_file = ?, bank_reference = ?, status = "pending" WHERE id = ?'
                );
                $upd->execute([$newName, $bankRef, $payment['id']]);
                flash_set('success', 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.');
                redirect('payment_upload.php?subscription_id=' . $subscriptionId);
            }
            flash_set('error', 'Gagal menyimpan file di server.');
        }
    }
}

$pageTitle = 'Pembayaran Membership';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Pembayaran Membership</h1>

<div class="card" style="max-width:560px;">
  <p><strong>Paket:</strong> <?= e($subscription['plan_name']) ?></p>
  <p><strong>Jumlah:</strong> <?= rupiah($subscription['price']) ?></p>
  <p><strong>Status Langganan:</strong>
    <span class="status-pill status-<?= $subscription['status'] === 'active' ? 'active' : 'pending' ?>"><?= e($subscription['status']) ?></span>
  </p>

  <?php if ($payment && $payment['status'] === 'confirmed'): ?>
    <div class="alert alert-success">Pembayaran sudah dikonfirmasi. Membership Anda aktif.</div>
  <?php else: ?>
    <div class="alert alert-success" style="white-space:pre-line;"><?= e($gatewayInfo['instructions']) ?></div>

    <form class="stacked" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="subscription_id" value="<?= (int)$subscriptionId ?>">

      <label>No. Referensi Transfer (opsional)</label>
      <input type="text" name="bank_reference" value="<?= e($payment['bank_reference'] ?? '') ?>">

      <label>Unggah Bukti Transfer (jpg/png/pdf)</label>
      <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required>

      <button class="btn" type="submit" style="margin-top:18px;">Kirim Bukti Pembayaran</button>
    </form>

    <?php if ($payment && $payment['proof_file']): ?>
      <p style="margin-top:16px;font-size:.85rem;color:var(--muted);">
        Bukti terakhir diunggah: <?= e($payment['proof_file']) ?> &mdash; status:
        <span class="status-pill status-pending"><?= e($payment['status']) ?></span>
      </p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
