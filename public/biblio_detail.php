<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/pdf_preview.php';

$biblioId = (int)($_GET['id'] ?? 0);

if (!$biblioId) {
  redirect('biblio_list.php');
}

/*
|--------------------------------------------------------------------------
| Ambil data bibliografi + file PDF
|--------------------------------------------------------------------------
*/
$biblio = slims_select_one(
  'SELECT 
        a.biblio_id,
        a.title,
        a.publish_year,
        a.edition,
        c.file_id,
        c.file_name,
        c.file_url
    FROM biblio a
    JOIN biblio_attachment b 
        ON b.biblio_id = a.biblio_id
    JOIN files c 
        ON c.file_id = b.file_id
    WHERE a.biblio_id = ?
      AND c.file_name LIKE \'%.pdf\'
    LIMIT 1',
  [$biblioId]
);

if (!$biblio) {
  redirect('biblio_list.php');
}


/*
|--------------------------------------------------------------------------
| Ambil attachment PDF
|--------------------------------------------------------------------------
*/
$attachment = get_biblio_pdf_attachment($biblioId);


/*
|--------------------------------------------------------------------------
| Aturan membership
|--------------------------------------------------------------------------
*/
$rule = get_biblio_membership_rule($biblioId);


/*
|--------------------------------------------------------------------------
| Member saat ini
|--------------------------------------------------------------------------
*/
$member = current_member();


/*
|--------------------------------------------------------------------------
| Cek akses baca penuh
|--------------------------------------------------------------------------
*/
$allowFull = can_read_full($member, $biblioId);


/*
|--------------------------------------------------------------------------
| Jumlah halaman preview
|--------------------------------------------------------------------------
*/
$previewPages = $rule['preview_pages'] ?? DEFAULT_PREVIEW_PAGES;


/*
|--------------------------------------------------------------------------
| Mode streaming PDF penuh
|--------------------------------------------------------------------------
*/
if (isset($_GET['stream']) && $attachment) {

  if (!$allowFull) {
    http_response_code(403);
    die('Anda belum memiliki akses membership untuk membaca dokumen ini secara penuh.');
  }

  /*
    | Catat aktivitas membaca
    */
  if ($member) {
    $log = db_app()->prepare(
      'INSERT INTO read_logs 
                (member_id, biblio_id, mode) 
             VALUES 
                (?, ?, "full")'
    );

    $log->execute([
      $member['id'],
      $biblioId
    ]);
  }

  /*
    | Stream PDF
    */
  stream_full_pdf($attachment);

  exit;
}


/*
|--------------------------------------------------------------------------
| Render preview
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Preview PDF
|--------------------------------------------------------------------------
|
| Preview image akan dirender oleh preview_image.php
| ketika browser meminta gambar.
|
*/
$previewImages = [];

if ($attachment && !$allowFull) {

  /*
     * Kita hanya membuat daftar nomor halaman.
     * Tidak perlu render PDF di halaman ini.
     */
  for ($i = 0; $i < $previewPages; $i++) {
    $previewImages[] = $i;
  }
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/
$pageTitle = $biblio['title'];

require __DIR__ . '/_header.php';

?>

<a
  href="biblio_list.php"
  style="font-size:.85rem;color:var(--muted);">
  &larr; Kembali ke Koleksi
</a>

<h1 style="margin-top:8px;">
  <?= e($biblio['title']) ?>
</h1>

<p style="color:var(--muted);">

  <?php if (!empty($biblio['publish_year'])): ?>
    <?= e($biblio['publish_year']) ?>
  <?php endif; ?>

  <?php if (!empty($biblio['edition'])): ?>
    &middot; <?= e($biblio['edition']) ?>
  <?php endif; ?>

</p>


<?php if (!$attachment): ?>

  <div class="alert alert-error">
    Judul ini belum memiliki file PDF di SLiMS.
  </div>


<?php elseif ($allowFull): ?>

  <div class="alert alert-success">
    Anda memiliki akses penuh untuk membaca dokumen ini.
  </div>

  <a
    class="btn"
    target="_blank"
    href="biblio_detail.php?id=<?= $biblioId ?>&stream=1">
    Buka / Unduh PDF Lengkap
  </a>


<?php else: ?>

  <p style="color:var(--muted);">

    Pratinjau <?= (int)$previewPages ?> halaman pertama tersedia gratis.

    Untuk membaca seluruh dokumen, Anda perlu memiliki membership aktif.

  </p>


  <?php if ($previewImages): ?>

    <div class="preview-pages">

      <?php foreach ($previewImages as $page): ?>

        <div class="preview-page">

          <img
            src="preview_image.php?attach=<?= (int)$attachment['file_id'] ?>&page=<?= (int)$page ?>"
            alt="Halaman <?= (int)$page + 1 ?>"
            loading="lazy">

        </div>

      <?php endforeach; ?>

    </div>

  <?php else: ?>

    <div class="alert alert-error">

      Preview PDF tidak dapat ditampilkan.

    </div>

  <?php endif; ?>


  <div class="locked-banner">

    <p style="margin:0 0 10px;">
      🔒
      <span class="amber">
        Konten selebihnya terkunci
      </span>
    </p>

    <p
      style="
                margin:0 0 16px;
                color:var(--paper-dim);
                font-size:.9rem;
            ">
      Berlangganan membership untuk membaca dokumen ini sampai selesai.
    </p>


    <?php if ($member): ?>

      <a
        class="btn"
        href="subscribe.php">
        Pilih Paket Membership
      </a>

    <?php else: ?>

      <a
        class="btn"
        href="register.php">
        Daftar / Masuk untuk Berlangganan
      </a>

    <?php endif; ?>

  </div>

<?php endif; ?>


<?php require __DIR__ . '/_footer.php'; ?>