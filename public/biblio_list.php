<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/membership.php';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Hanya tampilkan biblio yang punya file PDF terlampir (biblio_attachment)
$where = "WHERE c.file_name LIKE '%.pdf'";
$params = [];
if ($q !== '') {
  $where .= ' AND b.title LIKE ?';
  $params[] = '%' . $q . '%';
}

$sql = "SELECT DISTINCT b.biblio_id, b.title, b.publish_year, c.file_name
        FROM biblio b
        JOIN biblio_attachment a ON a.biblio_id = b.biblio_id
        JOIN files c ON c.file_id = a.file_id
        $where
        ORDER BY b.title ASC
        LIMIT $perPage OFFSET $offset";
$biblios = slims_select($sql, $params);

// Ambil aturan membership untuk semua biblio_id yang tampil di halaman ini
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

$pageTitle = 'Koleksi';
require __DIR__ . '/_header.php';
?>
<h1 style="margin-top:0;">Koleksi Bibliografi</h1>

<form method="get" style="margin-bottom:24px;display:flex;gap:8px;max-width:420px;">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul..."
    style="flex:1;padding:10px 12px;border:1px solid var(--line);border-radius:3px;">
  <button class="btn" type="submit">Cari</button>
</form>

<?php if (!$biblios): ?>
  <p style="color:var(--muted);">Tidak ada judul yang cocok, atau belum ada koleksi berfile PDF di SLiMS.</p>
<?php endif; ?>

<div class="grid grid-3">
  <?php foreach ($biblios as $b): ?>
    <?php
    $rule = $rules[$b['biblio_id']] ?? null;
    $needsMembership = $rule && $rule['requires_membership'];
    ?>
    <div class="biblio-card">
      <h3><?= e($b['title']) ?></h3>
      <div class="meta"><?= $b['publish_year'] ? e($b['publish_year']) : '&mdash;' ?></div>
      <div>
        <?php if ($needsMembership): ?>
          <span class="badge badge-membership">Membership</span>
        <?php else: ?>
          <span class="badge badge-free">Bebas Baca</span>
        <?php endif; ?>
      </div>
      <a class="btn btn-outline" style="margin-top:auto;" href="biblio_detail.php?id=<?= (int)$b['biblio_id'] ?>">Lihat &amp; Baca</a>
    </div>
  <?php endforeach; ?>
</div>

<div style="margin-top:26px;display:flex;gap:10px;">
  <?php if ($page > 1): ?>
    <a class="btn btn-outline" href="?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>">&larr; Sebelumnya</a>
  <?php endif; ?>
  <?php if (count($biblios) === $perPage): ?>
    <a class="btn btn-outline" href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>">Berikutnya &rarr;</a>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>