<?php
declare(strict_types=1);

/** Saldo koin pengguna. Setiap perubahan saldo tercatat di coin_ledger. */
final class Coins
{
    public const DEFAULT_PACKAGES = [
        ['id' => 'p1', 'coins' => 1, 'price' => 3000],
        ['id' => 'p5', 'coins' => 5, 'price' => 14000],
        ['id' => 'p10', 'coins' => 10, 'price' => 27000],
    ];

    public static function balance(int $userId): int
    {
        return (int) q_val('SELECT coin_balance FROM users WHERE id = ?', [$userId]);
    }

    /** Paket koin dari pengaturan Admin (harga dan jumlah koin bisa diubah). */
    public static function packages(): array
    {
        $saved = setting('coin_packages');
        $out = [];
        foreach (self::DEFAULT_PACKAGES as $i => $def) {
            $p = is_array($saved) && isset($saved[$i]) && is_array($saved[$i]) ? $saved[$i] : [];
            $coins = max(1, (int) ($p['coins'] ?? $def['coins']));
            $price = max(1000, (int) ($p['price'] ?? $def['price']));
            $out[] = ['id' => $def['id'], 'coins' => $coins, 'price' => $price, 'active' => !isset($p['active']) || (bool) $p['active']];
        }
        return $out;
    }

    public static function package(string $id): ?array
    {
        foreach (self::packages() as $p) {
            if ($p['id'] === $id && $p['active']) {
                return $p;
            }
        }
        return null;
    }

    /** Tambah (atau kurangi, untuk admin) saldo tanpa syarat. Mengembalikan saldo baru. */
    public static function credit(int $userId, int $delta, string $reason, ?string $ref = null, ?string $note = null, ?int $adminId = null): int
    {
        return self::tx(function () use ($userId, $delta, $reason, $ref, $note, $adminId) {
            q('UPDATE users SET coin_balance = GREATEST(0, coin_balance + ?) WHERE id = ?', [$delta, $userId]);
            $bal = self::balance($userId);
            q('INSERT INTO coin_ledger (user_id, delta, balance_after, reason, ref, note, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$userId, $delta, $bal, $reason, $ref, $note !== null ? mb_substr($note, 0, 255) : null, $adminId]);
            return $bal;
        });
    }

    /** Pakai koin secara atomik. false bila saldo tidak cukup. */
    public static function spend(int $userId, int $n, string $reason, ?string $ref = null): bool
    {
        return self::tx(function () use ($userId, $n, $reason, $ref) {
            $st = q('UPDATE users SET coin_balance = coin_balance - ? WHERE id = ? AND coin_balance >= ?', [$n, $userId, $n]);
            if ($st->rowCount() !== 1) {
                return false;
            }
            q('INSERT INTO coin_ledger (user_id, delta, balance_after, reason, ref) VALUES (?, ?, ?, ?, ?)',
                [$userId, -$n, self::balance($userId), $reason, $ref]);
            return true;
        });
    }

    public static function history(int $userId, int $limit = 30): array
    {
        return q_all('SELECT * FROM coin_ledger WHERE user_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit), [$userId]);
    }

    /** Jalankan dalam transaksi; bila sudah ada transaksi aktif, ikut transaksi itu. */
    public static function tx(callable $fn)
    {
        $pdo = db();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $r = $fn();
            $pdo->commit();
            return $r;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function rupiah(int $n): string
    {
        return 'Rp' . number_format($n, 0, ',', '.');
    }
}
