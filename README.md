# Aplikasi Membership Baca PDF Bibliografi (Terintegrasi SLiMS 9.6.1)

Aplikasi **berdiri sendiri** (standalone), PHP native, yang membaca data dari
database SLiMS v9.6.1 secara **read-only** dan menyimpan seluruh data
membership/pembayaran di **database sendiri** (`membership_app`). Aplikasi ini
tidak mengubah/menambah tabel apa pun di database SLiMS, sehingga tidak
mengganggu aturan/instalasi SLiMS yang sudah berjalan.

## Konsep

1. **Member existing SLiMS** → saat registrasi, sistem mencocokkan
   email/No. Anggota (`member.member_id` atau `email`) dengan tabel
   `member` di database SLiMS. Jika cocok, akun langsung diberi status
   `full_access` (tidak perlu bayar/berlangganan) sesuai ketentuan #1.
2. **User baru (bukan anggota SLiMS)** → mendaftar online lewat
   `public/register.php`, lalu harus memilih **paket membership**
   (`membership_plans`) dan melakukan pembayaran manual.
3. **Menu Bibliografi Membership** (`public/biblio_list.php` &
   `admin/biblio_membership.php`) → admin menandai judul (biblio) mana yang
   perlu membership, dan apakah aksesnya dibatasi waktu (mis. 30/90/365 hari)
   atau **selamanya** (lifetime), lewat tabel `biblio_membership`.
4. **Preview terbatas** (`includes/pdf_preview.php`) → biblio yang punya
   file PDF akan dirender N halaman pertama saja (default 5, bisa diatur per
   judul) menjadi gambar pratinjau. Untuk baca penuh, user harus punya
   membership aktif.
5. **Pembayaran manual** (`public/payment_upload.php`, `admin/payments.php`)
   → member upload bukti transfer, admin konfirmasi manual. Struktur kode
   sudah disiapkan dengan `PaymentGatewayInterface` supaya nanti tinggal
   ditambah driver pembayaran bank (VA/QRIS/dsb.) tanpa mengubah alur utama.
6. Semua kode **PHP native** (tanpa framework), memakai PDO, session-based
   auth sederhana — sesuai keahlian Anda.

## Struktur Folder

```
membership-app/
├── admin/                # Panel admin (kelola plan, mapping biblio, approve pembayaran)
├── config/                # Koneksi DB (SLiMS & App) + konfigurasi umum
├── database/schema.sql    # Skema database aplikasi (terpisah dari SLiMS)
├── includes/              # Fungsi helper, auth, logika preview PDF
├── public/                # Halaman untuk member (daftar, login, baca biblio, dsb.)
├── storage/
│   ├── preview_cache/     # Cache hasil render preview PDF (gambar per halaman)
│   └── payment_proof/     # Upload bukti transfer
└── assets/                # CSS/JS
```

## Instalasi Singkat

1. Buat database baru, mis. `membership_app`, lalu import `database/schema.sql`.
2. Salin `config/config.example.php` → `config/config.php`, isi kredensial:
   - koneksi ke DB **SLiMS** (host, nama db, user, pass) — **read only**
   - koneksi ke DB **membership_app** (punya sendiri)
   - path folder repository file SLiMS (tempat file PDF asli disimpan,
     biasanya `slims/files/repository/...` sesuai isi kolom
     `biblio_attachment.file_dir` + `file_name`).
3. Pastikan ekstensi PHP `imagick` + Ghostscript terpasang di server untuk
   render preview PDF ke gambar. Jika tidak tersedia, sistem otomatis
   fallback ke mode "PDF viewer terbatas" (lihat `includes/pdf_preview.php`).
4. Arahkan document root web server ke folder `public/` (untuk member) dan
   buat virtual path `/admin` untuk `admin/` (atau subdomain terpisah).
5. Buat akun admin pertama langsung lewat SQL (lihat komentar di
   `database/schema.sql` bagian bawah) karena belum ada UI super-admin awal.

## Catatan Keamanan & Pengembangan Lanjutan

- Password memakai `password_hash()`/`password_verify()`.
- Semua query ke DB SLiMS memakai prepared statement dan **hanya SELECT**
  (user DB SLiMS sebaiknya dibuat dengan privilege read-only saja).
- File PDF asli **tidak pernah** diekspos lewat URL langsung; selalu lewat
  `public/biblio_detail.php?stream=1` yang mengecek status membership dulu.
- Untuk integrasi bank nanti: implementasikan class baru yang mengikuti
  `PaymentGatewayInterface` di `includes/payment_gateway.php`, lalu daftarkan
  di `config/config.php` (`PAYMENT_GATEWAY_DRIVER`).
