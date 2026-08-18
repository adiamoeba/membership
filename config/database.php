<?php
/**
 * Menyediakan dua koneksi PDO terpisah:
 *  - db_slims(): koneksi ke database SLiMS, HANYA untuk SELECT.
 *  - db_app(): koneksi ke database aplikasi membership sendiri.
 */

require_once __DIR__ . '/config.php';

function db_slims(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            SLIMS_DB_HOST,
            SLIMS_DB_NAME,
            SLIMS_DB_CHARSET
        );
        $pdo = new PDO($dsn, SLIMS_DB_USER, SLIMS_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function db_app(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            APP_DB_HOST,
            APP_DB_NAME,
            APP_DB_CHARSET
        );
        $pdo = new PDO($dsn, APP_DB_USER, APP_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/**
 * Guard kecil: pastikan query ke DB SLiMS hanya SELECT.
 * Bukan pengaman mutlak (idealnya user DB memang dibuat read-only),
 * tapi mencegah kesalahan tak sengaja dari kode aplikasi ini sendiri.
 */
function slims_select(string $sql, array $params = []): array
{
    if (stripos(ltrim($sql), 'select') !== 0) {
        throw new RuntimeException('Hanya query SELECT yang diperbolehkan ke database SLiMS.');
    }
    $stmt = db_slims()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function slims_select_one(string $sql, array $params = []): ?array
{
    $rows = slims_select($sql, $params);
    return $rows[0] ?? null;
}
