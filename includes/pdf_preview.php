<?php

require_once __DIR__ . '/functions.php';


/**
 * ============================================================================
 * AMBIL FILE PDF DARI BIBLIOGRAFI
 * ============================================================================
 *
 * Relasi:
 *
 * biblio
 *   |
 *   | biblio_id
 *   v
 * biblio_attachment
 *   |
 *   | file_id
 *   v
 * files
 *
 */
function get_biblio_pdf_attachment(int $biblioId): ?array
{
    $row = slims_select_one(
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

        WHERE ba.biblio_id = ?

          AND (
                LOWER(f.file_name) LIKE '%.pdf'
                OR LOWER(f.mime_type) = 'application/pdf'
          )

        ORDER BY ba.file_id ASC

        LIMIT 1",
        [$biblioId]
    );

    if (!$row) {
        return null;
    }


    /**
     * =========================================================================
     * BANGUN PATH FILE PDF
     * =========================================================================
     */

    $repoPath = rtrim(
        SLIMS_REPO_PATH,
        '/\\'
    );

    $fileDir = trim(
        (string)($row['file_dir'] ?? ''),
        '/\\'
    );

    $fileName = basename(
        (string)($row['file_name'] ?? '')
    );


    if ($fileName === '') {
        return null;
    }


    /**
     * Jika file_dir tersedia:
     *
     * repository/file_dir/file.pdf
     */
    if ($fileDir !== '') {

        $fullPath =
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

        /**
         * Jika file_dir kosong:
         *
         * repository/file.pdf
         */
        $fullPath =
            $repoPath
            . DIRECTORY_SEPARATOR
            . $fileName;
    }


    /**
     * Simpan path absolut ke array attachment.
     */
    $row['full_path'] = $fullPath;


    return $row;
}


/**
 * ============================================================================
 * CEK IMAGICK
 * ============================================================================
 */
function pdf_is_previewable(): bool
{
    return extension_loaded('imagick');
}


/**
 * ============================================================================
 * RENDER PREVIEW PDF
 * ============================================================================
 *
 * Menghasilkan:
 *
 * page-0.png
 * page-1.png
 * page-2.png
 * dst.
 *
 */
function render_preview_images(
    array $attachment,
    int $pageCount
): array {

    $pageCount = max(1, $pageCount);


    /**
     * Pastikan file_id tersedia.
     */
    $fileId = (int)($attachment['file_id'] ?? 0);

    if ($fileId <= 0) {
        return [];
    }


    /**
     * =========================================================================
     * CEK CACHE DIRECTORY
     * =========================================================================
     */
    $cacheDir =
        rtrim(PREVIEW_CACHE_DIR, '/\\')
        . DIRECTORY_SEPARATOR
        . $fileId;


    if (!is_dir($cacheDir)) {

        if (
            !mkdir($cacheDir, 0755, true)
            && !is_dir($cacheDir)
        ) {
            return [];
        }
    }


    /**
     * =========================================================================
     * VALIDASI FILE PDF
     * =========================================================================
     */
    $pdfPath = $attachment['full_path'] ?? '';

    if (
        empty($pdfPath)
        || !is_file($pdfPath)
        || !is_readable($pdfPath)
    ) {
        return [];
    }


    /**
     * =========================================================================
     * HASIL PREVIEW
     * =========================================================================
     *
     * Gunakan indeks halaman asli.
     *
     * $images[0] = page-0.png
     * $images[1] = page-1.png
     *
     */
    $images = [];


    /**
     * =========================================================================
     * CEK CACHE
     * =========================================================================
     */
    $allCached = true;

    for ($i = 0; $i < $pageCount; $i++) {

        $target =
            $cacheDir
            . DIRECTORY_SEPARATOR
            . "page-{$i}.png";


        if (is_file($target)) {

            $images[$i] = $target;
        } else {

            $allCached = false;
        }
    }


    /**
     * Jika seluruh halaman sudah tersedia,
     * tidak perlu render ulang.
     */
    if ($allCached) {
        return $images;
    }


    /**
     * =========================================================================
     * CEK IMAGICK
     * =========================================================================
     */
    if (!pdf_is_previewable()) {
        return $images;
    }


    try {

        /**
         * Buat object Imagick.
         */
        $imagick = new Imagick();


        /**
         * Resolusi render.
         */
        $imagick->setResolution(
            120,
            120
        );


        /**
         * Baca halaman yang diperlukan.
         *
         * Contoh:
         *
         * 5 halaman
         * [0-4]
         */
        $imagick->readImage(
            $pdfPath
                . '[0-' . ($pageCount - 1) . ']'
        );


        /**
         * Render halaman satu per satu.
         */
        foreach ($imagick as $index => $page) {

            /**
             * Pastikan index integer.
             */
            $index = (int)$index;


            /**
             * Jangan render di luar jumlah halaman.
             */
            if ($index >= $pageCount) {
                break;
            }


            $target =
                $cacheDir
                . DIRECTORY_SEPARATOR
                . "page-{$index}.png";


            /**
             * Format PNG.
             */
            $page->setImageFormat('png');


            /**
             * Hilangkan metadata.
             */
            $page->stripImage();


            /**
             * Simpan gambar.
             */
            $page->writeImage($target);


            /**
             * Masukkan menggunakan index halaman.
             */
            if (is_file($target)) {
                $images[$index] = $target;
            }
        }


        /**
         * Bersihkan Imagick.
         */
        $imagick->clear();
        $imagick->destroy();
    } catch (Throwable $e) {

        /**
         * Untuk debugging sementara,
         * log error ke PHP error log.
         */
        error_log(
            '[membership_pdf] Preview PDF error: '
                . $e->getMessage()
        );
    }


    /**
     * =========================================================================
     * BACA ULANG HASIL CACHE
     * =========================================================================
     *
     * Ini memastikan hanya file yang benar-benar berhasil
     * yang dikembalikan.
     */
    $result = [];


    for ($i = 0; $i < $pageCount; $i++) {

        $target =
            $cacheDir
            . DIRECTORY_SEPARATOR
            . "page-{$i}.png";


        if (is_file($target)) {

            /**
             * Tetap gunakan index halaman.
             */
            $result[$i] = $target;
        }
    }


    return $result;
}


/**
 * ============================================================================
 * STREAM PDF FULL
 * ============================================================================
 */
function stream_full_pdf(array $attachment): void
{
    $fullPath = $attachment['full_path'] ?? '';


    /**
     * File tidak ditemukan.
     */
    if (
        empty($fullPath)
        || !file_exists($fullPath)
    ) {

        http_response_code(404);

        die('File PDF tidak ditemukan.');
    }


    /**
     * Pastikan file valid.
     */
    if (!is_file($fullPath)) {

        http_response_code(404);

        die('File PDF tidak valid.');
    }


    /**
     * Bersihkan output buffer.
     */
    while (ob_get_level() > 0) {
        ob_end_clean();
    }


    /**
     * Nama file.
     */
    $fileName = basename(
        $attachment['file_name']
            ?? 'document.pdf'
    );


    /**
     * Header PDF.
     */
    header(
        'Content-Type: application/pdf'
    );

    header(
        'Content-Disposition: inline; filename="'
            . str_replace('"', '', $fileName)
            . '"'
    );

    header(
        'Content-Length: '
            . filesize($fullPath)
    );

    header(
        'X-Content-Type-Options: nosniff'
    );

    header(
        'Accept-Ranges: bytes'
    );


    /**
     * Kirim PDF.
     */
    readfile($fullPath);

    exit;
}
