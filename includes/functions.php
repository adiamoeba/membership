<?php
require_once __DIR__ . '/../config/database.php';

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function rupiah($amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.');
    }
}

/**
 * Hitung end_date berdasarkan plan. Return null jika lifetime.
 */
function calculate_subscription_end(string $durationType, int $durationValue, ?string $startDate = null): ?string
{
    $start = $startDate ? new DateTime($startDate) : new DateTime();
    switch ($durationType) {
        case 'days':
            $start->modify("+{$durationValue} days");
            return $start->format('Y-m-d');
        case 'months':
            $start->modify("+{$durationValue} months");
            return $start->format('Y-m-d');
        case 'years':
            $start->modify("+{$durationValue} years");
            return $start->format('Y-m-d');
        case 'lifetime':
        default:
            return null;
    }
}

function duration_label(string $type, int $value): string
{
    switch ($type) {
        case 'days': return "{$value} hari";
        case 'months': return "{$value} bulan";
        case 'years': return "{$value} tahun";
        case 'lifetime': return 'Selamanya';
    }
    return '-';
}
