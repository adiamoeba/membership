<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/pdf_preview.php';


/**
 * ============================================================================
 * PARAMETER
 * ============================================================================
 */

$attachId = (int)($_GET['attach'] ?? 0);

$page = (int)($_GET['page'] ?? -1);


if ($attachId <= 0 || $page < 0) {

    http_response_code(400);

    exit;
}


/**
 * ============================================================================
 * AMBIL ATTACHMENT
 * ============================================================================
 *
 * Karena file_name/file_dir berada di tabel files,
 * gunakan JOIN melalui biblio_attachment.
 */
$attachment = slims_select_one(
    "SELECT
        ba.biblio_id,
        ba.file_id,
        ba.placement,
        ba.access_type,
        ba.access_limit,

        f.file_title,
        f.file_name,
        f.file_url,
        f.file_dir,
        f.mime_type,
        f.file_desc,
        f.file_key

    FROM biblio_attachment ba

    INNER JOIN files f
        ON f.file_id = ba.file_id

    WHERE ba.file_id = ?

      AND (
            LOWER(f.file_name) LIKE '%.pdf'
            OR LOWER(f.mime_type) = 'application/pdf'
      )

    LIMIT 1",
    [$attachId]
);


if (!$attachment) {

    http_response_code(404);

    exit;
}


/**
 * ============================================================================
 * BANGUN PATH PDF
 * ============================================================================
 */

$repoPath = rtrim(
    SLIMS_REPO_PATH,
    '/\\'
);

$fileDir = trim(
    (string)($attachment['file_dir'] ?? ''),
    '/\\'
);

$fileName = basename(
    (string)($attachment['file_name'] ?? '')
);


if ($fileName === '') {

    http_response_code(404);

    exit;
}


if ($fileDir !== '') {

    $attachment['full_path'] =
        $repoPath
        . DIRECTORY_SEPARATOR
        . str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $fileDir
        )
        . DIRECTORY_SEPARATOR
        . $fileName;
} else {

    $attachment['full_path'] =
        $repoPath
        . DIRECTORY_SEPARATOR
        . $fileName;
}


/**
 * ============================================================================
 * VALIDASI PDF
 * ============================================================================
 */

if (
    !is_file($attachment['full_path'])
    || !is_readable($attachment['full_path'])
) {

    error_log(
        '[membership_pdf] PDF tidak ditemukan: '
            . $attachment['full_path']
    );

    http_response_code(404);

    exit;
}


/**
 * ============================================================================
 * MEMBER
 * ============================================================================
 */

$member = current_member();


/**
 * ============================================================================
 * CEK AKSES FULL
 * ============================================================================
 */

$allowFull = can_read_full(
    $member,
    (int)$attachment['biblio_id']
);


/**
 * ============================================================================
 * ATURAN MEMBERSHIP
 * ============================================================================
 */

$rule = get_biblio_membership_rule(
    (int)$attachment['biblio_id']
);


$previewPages = (int)(
    $rule['preview_pages']
    ?? DEFAULT_PREVIEW_PAGES
);


$previewPages = max(
    1,
    $previewPages
);


/**
 * ============================================================================
 * BATAS AKSES HALAMAN
 * ============================================================================
 *
 * User tanpa membership:
 *
 * page 0 -> boleh
 * page 1 -> boleh
 * ...
 * page 4 -> boleh
 * page 5 -> 403
 *
 * Jika preview_pages = 5.
 */
if (
    !$allowFull
    && $page >= $previewPages
) {

    http_response_code(403);

    exit;
}


/**
 * ============================================================================
 * RENDER
 * ============================================================================
 *
 * Untuk user biasa render sesuai batas preview.
 *
 * Untuk member full cukup render sampai halaman
 * yang sedang diminta.
 */
$renderPages = $allowFull
    ? ($page + 1)
    : $previewPages;


$images = render_preview_images(
    $attachment,
    $renderPages
);


/**
 * ============================================================================
 * CEK HASIL
 * ============================================================================
 *
 * Penting:
 *
 * render_preview_images()
 * sekarang menggunakan index halaman asli.
 *
 * Jadi:
 *
 * $images[0] = page-0.png
 * $images[1] = page-1.png
 *
 */
if (
    !isset($images[$page])
    || !is_file($images[$page])
) {

    error_log(
        '[membership_pdf] Preview image tidak ditemukan. '
            . 'file_id=' . $attachId
            . ' page=' . $page
    );

    http_response_code(404);

    exit;
}


/**
 * ============================================================================
 * OUTPUT IMAGE
 * ============================================================================
 *
 * Bersihkan output buffer agar PNG tidak rusak.
 */
while (ob_get_level() > 0) {
    ob_end_clean();
}


header(
    'Content-Type: image/png'
);

header(
    'Content-Length: ' . filesize($images[$page])
);

header(
    'Cache-Control: private, max-age=3600'
);

header(
    'X-Content-Type-Options: nosniff'
);


readfile(
    $images[$page]
);

exit;
