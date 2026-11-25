<?php
declare(strict_types=1);

final class PremiumController
{
    /** POST /premium/beli: buat tagihan Premium 1 tahun lalu ke halaman pesanan (pembayaran dibuka di tab baru). */
    public function buy(): void
    {
        $u = require_login();
        if (!Premium::salesOpen()) {
            flash('error', t('premium.err.closed'));
            redirect('/harga');
        }
        if (!rate_hit('buy:' . $u['id'], 6, 600)) {
            flash('error', t('coins.err.too_many'));
            redirect('/harga');
        }
        $price = Premium::price();
        $open = q_one("SELECT * FROM coin_orders WHERE user_id = ? AND product = 'premium' AND amount = ? AND status = 'pending' AND pay_link IS NOT NULL
                       AND expires_at > NOW() + INTERVAL 1 HOUR ORDER BY id DESC LIMIT 1", [$u['id'], $price]);
        try {
            $order = $open ?: Payments::createOrder($u, ['id' => 'premium', 'coins' => 0, 'price' => $price, 'product' => 'premium']);
        } catch (Throwable $e) {
            flash('error', t('coins.err.gateway'));
            redirect('/harga');
        }
        redirect('/koin/pesanan/' . $order['code'], 303);
    }
}
