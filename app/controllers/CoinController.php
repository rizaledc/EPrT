<?php
declare(strict_types=1);

final class CoinController
{
    public function index(): void
    {
        $u = require_login();
        if (Mayar::configured()) {
            Payments::reconcileUser((int) $u['id']);
        }
        render('coins/index', [
            'title'    => t('coins.title'),
            'balance'  => Coins::balance((int) $u['id']),
            'packages' => Coins::packages(),
            'orders'   => Payments::forUser((int) $u['id'], 15),
            'ledger'   => Coins::history((int) $u['id'], 20),
            'canBuy'   => Mayar::configured() && setting('coins_sales_open', true) !== false,
            'noindex'  => true,
        ]);
    }

    public function buy(): void
    {
        $u = require_login();
        $pkg = Coins::package((string) input('package'));
        if (!$pkg || !Mayar::configured() || setting('coins_sales_open', true) === false) {
            flash('error', t('coins.err.unavailable'));
            redirect('/koin');
        }
        if (!rate_hit('buy:' . $u['id'], 6, 600)) {
            flash('error', t('coins.err.too_many'));
            redirect('/koin');
        }
        // Pesanan pending yang masih berlaku untuk paket yang sama dipakai ulang
        $open = q_one("SELECT * FROM coin_orders WHERE user_id = ? AND package = ? AND amount = ? AND status = 'pending' AND pay_link IS NOT NULL
                       AND expires_at > NOW() + INTERVAL 1 HOUR ORDER BY id DESC LIMIT 1", [$u['id'], $pkg['id'], $pkg['price']]);
        try {
            $order = $open ?: Payments::createOrder($u, $pkg);
        } catch (Throwable $e) {
            flash('error', t('coins.err.gateway'));
            redirect('/koin');
        }
        // Tidak langsung dialihkan ke Mayar: CSP form-action 'self' memblokir redirect form ke domain lain.
        // Halaman pesanan membuka tautan bayar (navigasi biasa, bukan kiriman form).
        redirect('/koin/pesanan/' . $order['code'] . '?bayar=1', 303);
    }

    public function order(string $code): void
    {
        $u = require_login();
        $o = Payments::byCode($code);
        if (!$o || (int) $o['user_id'] !== (int) $u['id']) {
            abort(404);
        }
        if ($o['status'] === 'pending' && Mayar::configured() && (!$o['checked_at'] || strtotime((string) $o['checked_at']) < time() - 10)) {
            $o = Payments::reconcile($o);
        }
        render('coins/order', [
            'title'   => t('coins.order.title'),
            'o'       => $o,
            'autoPay' => isset($_GET['bayar']) && $o['status'] === 'pending' && !empty($o['pay_link']),
            'balance' => Coins::balance((int) $u['id']),
            'noindex' => true,
        ]);
    }

    /** POST /api/mayar/webhook?token=... (tanpa CSRF, lihat Router::CSRF_EXEMPT) */
    public function webhook(): void
    {
        $token = (string) config('mayar.webhook_token', '');
        $raw = (string) file_get_contents('php://input');
        $ctype = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if ($token !== '' && !hash_equals($token, (string) ($_GET['token'] ?? ''))) {
            app_log('warning', 'Webhook Mayar ditolak: token tidak cocok (' . strlen((string) ($_GET['token'] ?? '')) . ' karakter)');
            json_response(['ok' => false, 'error' => 'token'], 403);
        }
        if (!rate_hit('mayar-hook', 60, 60)) {
            json_response(['ok' => false, 'error' => 'rate'], 429);
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            // Cadangan: kiriman berbentuk form, atau JSON di dalam satu field
            $payload = $_POST;
            if (count($payload) === 1 && is_string(reset($payload)) && is_array($j = json_decode((string) reset($payload), true))) {
                $payload = $j;
            }
        }
        $event = is_array($payload) ? (string) ($payload['event'] ?? '') : '';
        app_log('info', 'Webhook Mayar diterima: event ' . ($event !== '' ? $event : '-') . ', content-type ' . ($ctype !== '' ? $ctype : '-') . ', ' . strlen($raw) . ' byte');
        // Tes dari dashboard Mayar: cukup dijawab berhasil
        if (!$payload || in_array(strtolower($event), ['testing', 'test'], true)) {
            json_response(['ok' => true, 'test' => true]);
        }
        $n = Mayar::configured() ? Payments::handleWebhook($payload) : 0;
        json_response(['ok' => true, 'checked' => $n]);
    }
}
