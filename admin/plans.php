<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $durationType = $_POST['duration_type'] ?? 'months';
        $durationValue = (int)($_POST['duration_value'] ?? 1);
        $price = (float)($_POST['price'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($action === 'create') {
            $stmt = db_app()->prepare(
                'INSERT INTO membership_plans (name, description, duration_type, duration_value, price, is_active)
                 VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([$name, $desc, $durationType, $durationValue, $price, $isActive]);
            flash_set('success', 'Paket membership ditambahkan.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = db_app()->prepare(
                'UPDATE membership_plans SET name=?, description=?, duration_type=?, duration_value=?, price=?, is_active=? WHERE id=?'
            );
            $stmt->execute([$name, $desc, $durationType, $durationValue, $price, $isActive, $id]);
            flash_set('success', 'Paket membership diperbarui.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_app()->prepare('DELETE FROM membership_plans WHERE id=?')->execute([$id]);
        flash_set('success', 'Paket dihapus.');
    }
    redirect('plans.php');
}

$plans = db_app()->query('SELECT * FROM membership_plans ORDER BY price ASC')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$editPlan = null;
if ($editId) {
    $stmt = db_app()->prepare('SELECT * FROM membership_plans WHERE id = ?');
    $stmt->execute([$editId]);
    $editPlan = $stmt->fetch();
}

$pageTitle = 'Kelola Paket Membership';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Kelola Paket Membership</h1>

<div class="card" style="max-width:520px;margin-bottom:26px;">
  <h3><?= $editPlan ? 'Edit Paket' : 'Tambah Paket Baru' ?></h3>
  <form class="stacked" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $editPlan ? 'update' : 'create' ?>">
    <?php if ($editPlan): ?><input type="hidden" name="id" value="<?= (int)$editPlan['id'] ?>"><?php endif; ?>

    <label>Nama Paket</label>
    <input type="text" name="name" value="<?= e($editPlan['name'] ?? '') ?>" required>

    <label>Deskripsi</label>
    <textarea name="description"><?= e($editPlan['description'] ?? '') ?></textarea>

    <label>Tipe Durasi</label>
    <select name="duration_type">
      <?php foreach (['days'=>'Hari','months'=>'Bulan','years'=>'Tahun','lifetime'=>'Selamanya'] as $val=>$label): ?>
        <option value="<?= $val ?>" <?= ($editPlan['duration_type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>

    <label>Nilai Durasi (diabaikan jika "Selamanya")</label>
    <input type="text" name="duration_value" value="<?= e($editPlan['duration_value'] ?? '1') ?>">

    <label>Harga (Rp)</label>
    <input type="text" name="price" value="<?= e($editPlan['price'] ?? '0') ?>">

    <label><input type="checkbox" name="is_active" <?= ($editPlan['is_active'] ?? 1) ? 'checked' : '' ?> style="width:auto;"> Aktif</label>

    <button class="btn" type="submit" style="margin-top:16px;"><?= $editPlan ? 'Simpan Perubahan' : 'Tambah Paket' ?></button>
    <?php if ($editPlan): ?><a class="btn btn-outline" href="plans.php">Batal</a><?php endif; ?>
  </form>
</div>

<table class="simple">
  <thead><tr><th>Nama</th><th>Durasi</th><th>Harga</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($plans as $p): ?>
    <tr>
      <td><?= e($p['name']) ?></td>
      <td><?= e(duration_label($p['duration_type'], (int)$p['duration_value'])) ?></td>
      <td><?= rupiah($p['price']) ?></td>
      <td><?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
      <td>
        <a href="?edit=<?= (int)$p['id'] ?>">Edit</a> &middot;
        <form method="post" style="display:inline;" onsubmit="return confirm('Hapus paket ini?');">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;padding:0;">Hapus</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php require __DIR__ . '/_footer.php'; ?>
