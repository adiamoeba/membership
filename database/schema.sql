-- =====================================================================
-- SKEMA DATABASE APLIKASI MEMBERSHIP (TERPISAH DARI DATABASE SLiMS)
-- Nama database yang disarankan: membership_app
-- Semua tabel di sini murni milik aplikasi ini. TIDAK ADA perubahan
-- apa pun terhadap skema/tabel SLiMS.
-- =====================================================================

CREATE TABLE IF NOT EXISTS app_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Diisi otomatis jika email/no. anggota cocok dengan member di SLiMS
    slims_member_id VARCHAR(50) DEFAULT NULL,
    is_slims_member TINYINT(1) NOT NULL DEFAULT 0,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    -- full_access = anggota SLiMS lama, otomatis bebas baca semua biblio membership
    -- regular      = user baru, akses tergantung status langganan aktif
    access_type ENUM('full_access','regular') NOT NULL DEFAULT 'regular',
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS membership_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_type ENUM('days','months','years','lifetime') NOT NULL DEFAULT 'months',
    duration_value INT UNSIGNED NOT NULL DEFAULT 1, -- diabaikan jika duration_type = lifetime
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS member_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL, -- NULL berarti lifetime
    status ENUM('pending_payment','active','expired','rejected','cancelled') NOT NULL DEFAULT 'pending_payment',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES app_members(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    -- method 'manual' aktif sekarang; 'bank_transfer_va','qris', dst. untuk pengembangan nanti
    method VARCHAR(30) NOT NULL DEFAULT 'manual',
    proof_file VARCHAR(255) DEFAULT NULL,
    bank_reference VARCHAR(100) DEFAULT NULL,
    status ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
    confirmed_by VARCHAR(100) DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES member_subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES app_members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mapping: biblio (dari SLiMS, disimpan sebagai ID saja, bukan FK lintas-DB)
CREATE TABLE IF NOT EXISTS biblio_membership (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    biblio_id INT UNSIGNED NOT NULL UNIQUE, -- = biblio.biblio_id di SLiMS
    requires_membership TINYINT(1) NOT NULL DEFAULT 1,
    -- NULL = boleh diakses oleh member dengan plan aktif APAPUN
    required_plan_id INT UNSIGNED DEFAULT NULL,
    preview_pages INT UNSIGNED NOT NULL DEFAULT 5,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (required_plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Log akses baca (opsional, untuk statistik / audit)
CREATE TABLE IF NOT EXISTS read_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    biblio_id INT UNSIGNED NOT NULL,
    mode ENUM('preview','full') NOT NULL,
    accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES app_members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- Contoh data awal (opsional, silakan sesuaikan)
-- =====================================================================
INSERT INTO membership_plans (name, description, duration_type, duration_value, price, is_active) VALUES
('Bulanan', 'Akses semua biblio membership selama 30 hari', 'days', 30, 25000, 1),
('Tahunan', 'Akses semua biblio membership selama 1 tahun', 'years', 1, 200000, 1),
('Selamanya', 'Akses seumur hidup ke semua biblio membership', 'lifetime', 0, 500000, 1);

-- Buat akun admin pertama (ganti password_hash di bawah dengan hasil
-- password_hash('password_anda', PASSWORD_DEFAULT) lewat php -a atau skrip kecil)
-- INSERT INTO admins (username, password_hash, full_name)
-- VALUES ('admin', '$2y$10$GANTI_DENGAN_HASH_ASLI', 'Administrator');
