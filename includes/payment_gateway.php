<?php
/**
 * Abstraksi metode pembayaran. Saat ini hanya driver "manual" yang aktif
 * (member upload bukti transfer, admin konfirmasi manual). Untuk integrasi
 * bank nanti (VA/QRIS/dsb.), buat class baru yang implement interface ini,
 * lalu ubah PAYMENT_GATEWAY_DRIVER di config.php.
 */

interface PaymentGatewayInterface
{
    /**
     * Memulai transaksi pembayaran. Return array info yang dibutuhkan
     * halaman pembayaran (mis. instruksi transfer, atau redirect_url untuk
     * gateway online).
     */
    public function createPayment(array $subscription, array $plan, array $member): array;
}

class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(array $subscription, array $plan, array $member): array
    {
        // Untuk pembayaran manual, kita hanya perlu menampilkan info rekening
        // dan form upload bukti. Baris `payments` sudah dibuat terpisah saat
        // subscription dibuat (lihat public/subscribe.php).
        return [
            'mode' => 'manual',
            'instructions' => MANUAL_PAYMENT_INFO,
            'amount' => $plan['price'],
        ];
    }
}

/**
 * Placeholder untuk pengembangan berikutnya. Jangan diaktifkan dulu sampai
 * kredensial bank/payment gateway tersedia.
 *
 * class BankVAPaymentGateway implements PaymentGatewayInterface
 * {
 *     public function createPayment(array $subscription, array $plan, array $member): array
 *     {
 *         // panggil API bank, buat Virtual Account, dsb.
 *         // return ['mode' => 'bank_va', 'va_number' => '...', 'redirect_url' => null];
 *     }
 * }
 */

function payment_gateway(): PaymentGatewayInterface
{
    switch (PAYMENT_GATEWAY_DRIVER) {
        case 'manual':
        default:
            return new ManualPaymentGateway();
    }
}
