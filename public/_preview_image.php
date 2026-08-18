<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/pdf_preview.php';

header('Content-Type: text/plain; charset=UTF-8');


/*
|--------------------------------------------------------------------------
| Ambil attachment
|--------------------------------------------------------------------------
*/

$attachId = (int)($_GET['attach'] ?? 0);

$attachment = slims_select_one(
    "SELECT
        ba.biblio_id,
        ba.file_id,
        f.file_name,
        f.file_dir,
        f.mime_type
    FROM biblio_attachment ba
    INNER JOIN files f
        ON f.file_id = ba.file_id
    WHERE ba.file_id = ?
    LIMIT 1",
    [$attachId]
);

if (!$attachment) {
    die("Attachment tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| Bangun path PDF
|--------------------------------------------------------------------------
*/

$repoPath = rtrim(SLIMS_REPO_PATH, '/\\');

$fileDir = trim(
    (string)$attachment['file_dir'],
    '/\\'
);

$fileName = basename(
    (string)$attachment['file_name']
);

if ($fileDir !== '') {

    $pdfPath =
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

    $pdfPath =
        $repoPath
        . DIRECTORY_SEPARATOR
        . $fileName;
}


/*
|--------------------------------------------------------------------------
| Informasi dasar
|--------------------------------------------------------------------------
*/

echo "=== TEST IMAGICK PDF ===\n\n";

echo "PDF:\n";
echo $pdfPath . "\n\n";

echo "Imagick extension:\n";
echo extension_loaded('imagick')
    ? "ACTIVE"
    : "NOT ACTIVE";

echo "\n\n";


/*
|--------------------------------------------------------------------------
| Versi Imagick
|--------------------------------------------------------------------------
*/

echo "Imagick version:\n";

if (class_exists('Imagick')) {

    $version = Imagick::getVersion();

    print_r($version);
} else {

    echo "Class Imagick tidak tersedia.";
}

echo "\n";


/*
|--------------------------------------------------------------------------
| Apakah PDF didukung?
|--------------------------------------------------------------------------
*/

echo "\nPDF FORMAT SUPPORT:\n";

$formats = Imagick::queryFormats('PDF');

if (empty($formats)) {

    echo "PDF TIDAK DIDUKUNG\n";
} else {

    echo "PDF DIDUKUNG\n";

    print_r($formats);
}


/*
|--------------------------------------------------------------------------
| Coba membaca halaman pertama PDF
|--------------------------------------------------------------------------
*/

echo "\n\n=== READ PDF TEST ===\n\n";

try {

    $imagick = new Imagick();

    /*
     * Resolusi.
     */
    $imagick->setResolution(
        120,
        120
    );

    echo "Mencoba membaca PDF...\n";

    $imagick->readImage(
        $pdfPath . '[0]'
    );

    echo "BERHASIL membaca PDF.\n\n";

    echo "Jumlah image/page yang dibaca:\n";
    echo $imagick->getNumberImages();

    echo "\n\n";


    /*
     * Ambil halaman pertama.
     */
    $imagick->setIteratorIndex(0);

    $page = $imagick->getImage();


    echo "Format:\n";
    echo $page->getImageFormat();

    echo "\n\n";


    echo "Width:\n";
    echo $page->getImageWidth();

    echo "\n\n";


    echo "Height:\n";
    echo $page->getImageHeight();

    echo "\n\n";


    /*
     * Tes konversi ke PNG.
     */
    echo "Mencoba convert ke PNG...\n";

    $page->setImageFormat('png');

    /*
     * Simpan sementara.
     */
    $testFile =
        __DIR__
        . DIRECTORY_SEPARATOR
        . 'test_preview.png';


    $page->writeImage(
        $testFile
    );


    echo "\nHASIL CONVERT:\n";
    echo $testFile;

    echo "\n\n";

    echo "FILE EXISTS:\n";

    echo is_file($testFile)
        ? "YES"
        : "NO";


    /*
     * Bersihkan.
     */
    $imagick->clear();
    $imagick->destroy();
} catch (Throwable $e) {

    echo "\n\n====================================\n";

    echo "ERROR IMAGICK:\n";

    echo "====================================\n\n";

    echo get_class($e);

    echo "\n\n";

    echo $e->getMessage();

    echo "\n\n";

    echo "FILE:\n";
    echo $e->getFile();

    echo "\n\n";

    echo "LINE:\n";
    echo $e->getLine();

    echo "\n";
}
