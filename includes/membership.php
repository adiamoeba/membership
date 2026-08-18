<?php
require_once __DIR__ . '/functions.php';

/**
 * Cari apakah email/no. anggota yang didaftarkan sudah ada di database
 * SLiMS (member). Kolom disesuaikan dengan skema standar SLiMS 9.x:
 *   member.member_id  (No. Anggota)
 *   member.member_name
 *   member.member_email
 *   member.expire_date (masa berlaku keanggotaan SLiMS, opsional dicek)
 */
function find_slims_member(string $email, string $memberIdCard = ''): ?array
{
    if ($memberIdCard !== '') {
        $row = slims_select_one(
            'SELECT member_id, member_name, member_email, expire_date
             FROM member WHERE member_id = ? LIMIT 1',
            [$memberIdCard]
        );
        if ($row) {
            return $row;
        }
    }
    return slims_select_one(
        'SELECT member_id, member_name, member_email, expire_date
         FROM member WHERE member_email = ? LIMIT 1',
        [$email]
    );
}

/**
 * Ketentuan #1: anggota SLiMS yang sudah ada otomatis dapat full access,
 * tanpa perlu memilih plan / membayar. "Full access" berlaku selama
 * keanggotaan SLiMS mereka sendiri masih aktif (expire_date >= hari ini),
 * jika kolom itu tersedia dan diisi.
 */
function slims_membership_still_valid(array $slimsMember): bool
{
    if (empty($slimsMember['expire_date']) || $slimsMember['expire_date'] === '0000-00-00') {
        // Tidak ada tanggal kedaluwarsa yang tercatat -> anggap masih berlaku
        return true;
    }
    return strtotime($slimsMember['expire_date']) >= strtotime(date('Y-m-d'));
}

/**
 * Mengembalikan status akses member terhadap fitur membership secara umum:
 * 'full_access' | 'active_subscription' | 'none'
 */
function member_access_status(array $member): string
{
    if ($member['access_type'] === 'full_access') {
        return 'full_access';
    }

    $stmt = db_app()->prepare(
        "SELECT * FROM member_subscriptions
         WHERE member_id = ? AND status = 'active'
           AND (end_date IS NULL OR end_date >= CURDATE())
         ORDER BY (end_date IS NULL) DESC, end_date DESC LIMIT 1"
    );
    $stmt->execute([$member['id']]);
    $sub = $stmt->fetch();

    return $sub ? 'active_subscription' : 'none';
}

function member_active_subscription(array $member): ?array
{
    $stmt = db_app()->prepare(
        "SELECT s.*, p.name AS plan_name FROM member_subscriptions s
         JOIN membership_plans p ON p.id = s.plan_id
         WHERE s.member_id = ? AND s.status = 'active'
           AND (s.end_date IS NULL OR s.end_date >= CURDATE())
         ORDER BY (s.end_date IS NULL) DESC, s.end_date DESC LIMIT 1"
    );
    $stmt->execute([$member['id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Ambil pengaturan membership untuk 1 biblio. Jika tidak ada baris di
 * biblio_membership, berarti biblio itu bebas dibaca semua orang.
 */
function get_biblio_membership_rule(int $biblioId): ?array
{
    $stmt = db_app()->prepare('SELECT * FROM biblio_membership WHERE biblio_id = ?');
    $stmt->execute([$biblioId]);
    return $stmt->fetch() ?: null;
}

/**
 * Tentukan apakah $member boleh membaca versi PENUH dari sebuah biblio.
 */
function can_read_full(?array $member, int $biblioId): bool
{
    $rule = get_biblio_membership_rule($biblioId);

    // Tidak ada aturan / requires_membership = 0 -> bebas dibaca siapa saja
    if (!$rule || !$rule['requires_membership']) {
        return true;
    }

    if (!$member) {
        return false;
    }

    $status = member_access_status($member);
    if ($status === 'full_access') {
        return true;
    }
    if ($status === 'active_subscription') {
        $sub = member_active_subscription($member);
        // required_plan_id NULL -> plan aktif apa pun cukup
        if (!$rule['required_plan_id'] || (int)$rule['required_plan_id'] === (int)$sub['plan_id']) {
            return true;
        }
    }
    return false;
}
