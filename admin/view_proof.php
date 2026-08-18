<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$paymentId = (int)($_GET['payment_id'] ?? 0);
$stmt = db_app()->prepare('SELECT proof_file FROM payments WHERE id = ?');
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment || !$payment['proof_file']) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$path = rtrim(PAYMENT_PROOF_DIR, '/') . '/' . basename($payment['proof_file']);
if (!file_exists($path)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = $ext === 'pdf' ? 'application/pdf' : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($path) . '"');
readfile($path);
