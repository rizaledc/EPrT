<?php
declare(strict_types=1);

/**
 * Pembelian koin lewat Mayar.
 * Koin hanya dikreditkan setelah status dicek langsung ke API Mayar (webhook Mayar tidak bertanda tangan),
 * dan hanya sekali per pesanan: UPDATE ... WHERE status = 'pending' menjadi kunci.
 */
final class Payments
{
    public const ORDER_TTL_HOURS = 24;

    /** Buat pesanan koin atau Premium dan payment request Mayar. @throws RuntimeException */
    public static function createOrder(array $user, array $pkg): array
    {
        $product = ($pkg['product'] ?? 'coins') === 'premium' ? 'premium' : 'coins';
        $code = 'PI' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
        $expires = new DateTimeImmutable('+' . self::ORDER_TTL_HOURS . ' hours');
        q('INSERT INTO coin_orders (user_id, code, product, package, coins, amount, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$user['id'], $code, $product, $pkg['id'], $pkg['coins'], $pkg['price'], 'pending', $expires->format('Y-m-d H:i:s')]);
        $id = (int) db()->lastInsertId();
        $desc = $product === 'premium'
            ? 'Premium PerumahanInggris 1 tahun untuk ' . $user['username'] . ' (pesanan ' . $code . ')'
            : $pkg['coins'] . ' koin PerumahanInggris untuk ' . $user['username'] . ' (pesanan ' . $code . ')';
        try {
            $p = Mayar::createPayment((string) $user['username'], (int) $pkg['price'], $desc, $expires, ['order' => $code]);
        } catch (Throwable $e) {
            q("UPDATE coin_orders SET status = 'failed' WHERE id = ?", [$id]);
            app_log('error', 'Mayar createPayment ' . $code . ': ' . $e->getMessage());
            throw new RuntimeException('create_failed');
        }
        q('UPDATE coin_orders SET mayar_id = ?, mayar_tx_id = ?, pay_link = ? WHERE id = ?', [$p['id'], $p['transactionId'] ?: null, $p['link'], $id]);
        return self::find($id);
    }

    public static function find(int $id): ?array
    {
        return q_one('SELECT * FROM coin_orders WHERE id = ?', [$id]);
    }

    public static function byCode(string $code): ?array
    {
        return q_one('SELECT * FROM coin_orders WHERE code = ?', [$code]);
    }

    /**
     * Cek ulang status pesanan ke Mayar. Mengembalikan pesanan terbaru.
     * Kalau API gagal, pesanan dibiarkan pending (dicek lagi nanti).
     */
    public static function reconcile(array $order): array
    {
        if ($order['status'] !== 'pending') {
            return $order;
        }
        if (empty($order['mayar_id'])) {
            if (strtotime((string) $order['created_at']) < time() - 3600) {
                q("UPDATE coin_orders SET status = 'failed' WHERE id = ? AND status = 'pending'", [$order['id']]);
            }
            return self::find((int) $order['id']);
        }
        try {
            $p = Mayar::getPayment((string) $order['mayar_id']);
        } catch (Throwable $e) {
            app_log('warning', 'Mayar getPayment ' . $order['code'] . ': ' . $e->getMessage());
            return $order;
        }
        q('UPDATE coin_orders SET checked_at = NOW() WHERE id = ?', [$order['id']]);
        if ($p['status'] === 'paid') {
            if ($p['amount'] !== (int) $order['amount']) {
                app_log('error', 'Mayar nominal tidak cocok untuk ' . $order['code'] . ': ' . $p['amount'] . ' vs ' . $order['amount']);
                return self::find((int) $order['id']);
            }
            self::markPaid($order);
        } elseif ($p['status'] === 'closed' || ($order['expires_at'] && strtotime((string) $order['expires_at']) < time() - 3600)) {
            q("UPDATE coin_orders SET status = 'expired' WHERE id = ? AND status = 'pending'", [$order['id']]);
        }
        return self::find((int) $order['id']);
    }

    /** Tandai lunas dan kreditkan koin, tepat sekali. */
    public static function markPaid(array $order): bool
    {
        return Coins::tx(function () use ($order) {
            $st = q("UPDATE coin_orders SET status = 'paid', paid_at = NOW() WHERE id = ? AND status = 'pending'", [$order['id']]);
            if ($st->rowCount() !== 1) {
                return false;
            }
            if (($order['product'] ?? 'coins') === 'premium') {
                Premium::activate((int) $order['user_id']);
                if (($bonus = Premium::bonusCoins()) > 0) {
                    Coins::credit((int) $order['user_id'], $bonus, 'purchase', (string) $order['code'], 'Bonus Premium');
                }
            } else {
                Coins::credit((int) $order['user_id'], (int) $order['coins'], 'purchase', (string) $order['code'], Coins::rupiah((int) $order['amount']));
            }
            return true;
        });
    }

    /** Cek pesanan pending milik pengguna (dipanggil saat membuka halaman Koin). */
    public static function reconcileUser(int $userId): void
    {
        $rows = q_all("SELECT * FROM coin_orders WHERE user_id = ? AND status = 'pending' AND created_at > NOW() - INTERVAL 3 DAY
                       AND (checked_at IS NULL OR checked_at < NOW() - INTERVAL 20 SECOND) ORDER BY id DESC LIMIT 5", [$userId]);
        foreach ($rows as $o) {
            self::reconcile($o);
        }
    }

    /**
     * Webhook Mayar (event payment.received). Isi webhook hanya dipakai untuk mencari pesanan;
     * status tetap diverifikasi ke API. @return int jumlah pesanan yang dicek
     */
    public static function handleWebhook(array $payload): int
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $ids = [];
        foreach (['id', 'transactionId', 'transaction_id', 'paymentLinkId', 'productId'] as $k) {
            if (isset($data[$k]) && is_scalar($data[$k]) && (string) $data[$k] !== '') {
                $ids[] = substr((string) $data[$k], 0, 64);
            }
        }
        $code = $data['extraData']['order'] ?? null;
        $orders = [];
        if (is_string($code) && preg_match('/^PI[0-9A-F]{14}$/', $code) && ($o = self::byCode($code))) {
            $orders[] = $o;
        }
        if (!$orders && $ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $orders = q_all("SELECT * FROM coin_orders WHERE mayar_id IN ($in) OR mayar_tx_id IN ($in) LIMIT 5", array_merge($ids, $ids));
        }
        if (!$orders) {
            // Pesanan tidak dikenali dari isi webhook: cek pesanan pending terbaru
            $orders = q_all("SELECT * FROM coin_orders WHERE status = 'pending' AND mayar_id IS NOT NULL AND created_at > NOW() - INTERVAL 2 DAY ORDER BY id DESC LIMIT 10");
        }
        foreach ($orders as $o) {
            self::reconcile($o);
        }
        return count($orders);
    }

    public static function forUser(int $userId, int $limit = 20): array
    {
        return q_all('SELECT * FROM coin_orders WHERE user_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit), [$userId]);
    }
}
