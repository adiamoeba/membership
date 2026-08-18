<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? '';

  if ($action === 'save') {
    $biblioId = (int)$_POST['biblio_id'];
    $requires = isset($_POST['requires_membership']) ? 1 : 0;
    $planId = $_POST['required_plan_id'] !== '' ? (int)$_POST['required_plan_id'] : null;
    $previewPages = max(0, (int)$_POST['preview_pages']);

    $exists = db_app()->prepare('SELECT id FROM biblio_membership WHERE biblio_id = ?');
    $exists->execute([$biblioId]);
    if ($row = $exists->fetch()) {
      $stmt = db_app()->prepare(
        'UPDATE biblio_membership SET requires_membership=?, required_plan_id=?, preview_pages=? WHERE id=?'
      );
      $stmt->execute([$requires, $planId, $previewPages, $row['id']]);
    } else {
      $stmt = db_app()->prepare(
        'INSERT INTO biblio_membership (biblio_id, requires_membership, required_plan_id, preview_pages) VALUES (?,?,?,?)'
      );
      $stmt->execute([$biblioId, $requires, $planId, $previewPages]);
    }
    flash_set('success', 'Pengaturan membership biblio disimpan.');
  } elseif ($action === 'remove') {
    db_app()->prepare('DELETE FROM biblio_membership WHERE biblio_id = ?')->execute([(int)$_POST['biblio_id']]);
    flash_set('success', 'Judul dikembalikan menjadi bebas akses (tidak ada aturan membership).');
  }
  redirect('biblio_membership.php' . (!empty($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = "WHERE c.file_name LIKE '%.pdf'";
if ($q !== '') {
  $where .= ' AND b.title LIKE ?';
  $params[] = '%' . $q . '%';
}
$biblios = slims_select(
  "SELECT DISTINCT b.biblio_id, b.title, c.file_name FROM biblio b
     JOIN biblio_attachment a ON a.biblio_id = b.biblio_id
     JOIN files c ON c.file_id = a.file_id
     $where ORDER BY b.title ASC LIMIT 50",
  $params
);

$rules = [];
if ($biblios) {
  $ids = array_column($biblios, 'biblio_id');
  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = db_app()->prepare("SELECT * FROM biblio_membership WHERE biblio_id IN ($in)");
  $stmt->execute($ids);
  foreach ($stmt->fetchAll() as $r) {
    $rules[$r['biblio_id']] = $r;
  }
}
$plans = db_app()->query('SELECT id, name FROM membership_plans ORDER BY price ASC')->fetchAll();

$pageTitle = 'Mapping Biblio Membership';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Tandai Bibliografi sebagai Membership</h1>

<form method="get" style="margin-bottom:20px;display:flex;gap:8px;max-width:420px;">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul..."
    style="flex:1;padding:10px 12px;border:1px solid var(--line);border-radius:3px;">
  <button class="btn" type="submit">Cari</button>
</form>

<table class="simple">
  <thead>
    <tr>
      <th>Judul</th>
      <th>Wajib Membership?</th>
      <th>Paket Disyaratkan</th>
      <th>Halaman Preview</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($biblios as $b): $r = $rules[$b['biblio_id']] ?? null; ?>
      <tr>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="biblio_id" value="<?= (int)$b['biblio_id'] ?>">
          <td><?= e($b['title']) ?></td>
          <td><input type="checkbox" name="requires_membership" <?= (!$r || $r['requires_membership']) ? 'checked' : '' ?>></td>
          <td>
            <select name="required_plan_id">
              <option value="">(Plan aktif apa pun)</option>
              <?php foreach ($plans as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= (int)($r['required_plan_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="text" name="preview_pages" value="<?= e($r['preview_pages'] ?? DEFAULT_PREVIEW_PAGES) ?>" style="width:60px;"></td>
          <td><button class="btn btn-outline" type="submit">Simpan</button></td>
        </form>
      </tr>
    <?php endforeach; ?>
    <?php if (!$biblios): ?>
      <tr>
        <td colspan="5" style="color:var(--muted);">Tidak ada judul ditemukan (pastikan biblio memiliki lampiran PDF).</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/_footer.php'; ?>