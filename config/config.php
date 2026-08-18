<?php

/**
 * Salin file ini menjadi config.php lalu sesuaikan nilainya.
 * config.php TIDAK boleh di-commit ke repository publik.
 */

// ---------- Database SLiMS (READ ONLY) ----------
define('SLIMS_DB_HOST', '127.0.0.1');
define('SLIMS_DB_NAME', 'slims_biblio');   // nama database SLiMS Anda
define('SLIMS_DB_USER', 'root');  // sebaiknya user dengan hak SELECT saja
define('SLIMS_DB_PASS', '');
define('SLIMS_DB_CHARSET', 'utf8mb4');

// Folder fisik tempat SLiMS menyimpan file repository (attachment biblio).
// Biasanya: <slims_root>/files/repo/xx/ atau sesuai isi biblio_attachment.file_dir
//define('SLIMS_REPO_PATH', '/var/www/slims/files/repo');
define('SLIMS_REPO_PATH', 'C:/xampp/htdocs/slims_biblio/repository');

// ---------- Database Aplikasi Membership (milik sendiri) ----------
define('APP_DB_HOST', '127.0.0.1');
define('APP_DB_NAME', 'membership');
define('APP_DB_USER', 'root');
define('APP_DB_PASS', '');
define('APP_DB_CHARSET', 'utf8mb4');

// ---------- Pengaturan Umum Aplikasi ----------
define('APP_NAME', 'Perpustakaan Digital Membership');
//define('APP_BASE_URL', 'https://baca.perpustakaan-anda.id'); // tanpa trailing slash
define('APP_BASE_URL', 'http://localhost/membership'); // tanpa trailing slash
define('DEFAULT_PREVIEW_PAGES', 5);

// Folder penyimpanan lokal aplikasi (harus writable oleh web server)
define('PREVIEW_CACHE_DIR', __DIR__ . '/../storage/preview_cache');
define('PAYMENT_PROOF_DIR', __DIR__ . '/../storage/payment_proof');

// Driver pembayaran aktif saat ini. Nanti tinggal ganti/extend
// (mis. 'bank_va', 'qris') setelah driver-nya dibuat di includes/payment_gateway.php
define('PAYMENT_GATEWAY_DRIVER', 'manual');

// Info rekening untuk pembayaran manual (ditampilkan ke member)
define('MANUAL_PAYMENT_INFO', "Transfer ke:\nBank ABC - 1234567890 a.n. Perpustakaan Digital");

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Session
ini_set('session.cookie_httponly', 1);
session_start();
