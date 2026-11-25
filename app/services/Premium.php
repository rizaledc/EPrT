<?php
declare(strict_types=1);

/**
 * Paket Premium: sekali bayar, aktif 1 tahun (diperpanjang dari tanggal berakhir bila masih aktif).
 * Penguncian Level B sampai D dan Simulasi baru berlaku bila Admin menyalakan setting premium_required;
 * selama mati, semua Level tetap terbuka (masa uji coba).
 */
final class Premium
{
    public const DURATION_DAYS = 365;

    public static function isActive(?array $u): bool
    {
        if (!$u) {
            return false;
        }
        if (($u['role'] ?? '') === 'admin') {
            return true;
        }
        return !empty($u['premium_until']) && strtotime((string) $u['premium_until']) > time();
    }

    /** true bila Level B sampai D dan Simulasi dikunci untuk pengguna tanpa Premium */
    public static function required(): bool
    {
        return setting('premium_required', false) === true;
    }

    public static function salesOpen(): bool
    {
        return Mayar::configured() && setting('premium_sales_open', true) !== false;
    }

    public static function needsPremium(array $rumah): bool
    {
        if ($rumah['kind'] === 'simulation') {
            return true;
        }
        return in_array($rumah['kind'], ['practice', 'adaptive'], true) && strtoupper((string) $rumah['blok_code']) !== 'A';
    }

    /**
     * Pengguna awal: akun yang dibuat sebelum penguncian dinyalakan tetap bisa membuka semua Level
     * (masa uji coba dihormati), selama setting premium_grandfather menyala.
     */
    public static function grandfathered(?array $u): bool
    {
        $cutoff = (string) setting('premium_cutoff', '');
        return $u && $cutoff !== '' && setting('premium_grandfather', true) !== false
            && strtotime((string) $u['created_at']) < strtotime($cutoff);
    }

    /** Boleh membuka bagian Premium: Premium aktif, admin, atau pengguna awal */
    public static function hasAccess(?array $u): bool
    {
        return self::isActive($u) || self::grandfathered($u);
    }

    public static function canAccess(array $u, array $rumah): bool
    {
        return !self::required() || !self::needsPremium($rumah) || self::hasAccess($u);
    }

    /** Kartu di peta terkunci untuk pengguna ini */
    public static function locked(?array $u, array $rumah): bool
    {
        return self::required() && self::needsPremium($rumah) && !self::hasAccess($u);
    }

    /** Sisa kuota harga awal: ['price', 'left', 'size'] atau null bila sudah tingkat terakhir */
    public static function quota(): ?array
    {
        $tier = LegalController::PRICE_TIERS[LegalController::currentTier()];
        if ($tier['to'] === null) {
            return null;
        }
        return ['price' => (int) $tier['price'], 'left' => max(1, (int) $tier['to'] - self::sold()), 'size' => (int) $tier['to'] - (int) $tier['from'] + 1];
    }

    public static function bonusCoins(): int
    {
        return max(0, (int) setting('premium_bonus_coins', 2));
    }

    /** Hentikan akses ke bagian Premium: arahkan ke halaman Harga. */
    public static function guard(array $u, array $rumah): void
    {
        if (!self::canAccess($u, $rumah)) {
            flash('info', t('premium.locked'));
            redirect('/harga');
        }
    }

    public static function guardLevel(array $u, string $code): void
    {
        if (self::required() && strtoupper($code) !== 'A' && !self::hasAccess($u)) {
            flash('info', t('premium.locked'));
            redirect('/harga');
        }
    }

    /** Jumlah pembeli: pesanan Premium lunas di situs + angka tambahan dari Admin (penjualan di luar situs). */
    public static function sold(): int
    {
        static $paid = null;
        if ($paid === null) {
            try {
                $paid = (int) q_val("SELECT COUNT(*) FROM coin_orders WHERE product = 'premium' AND status = 'paid'");
            } catch (Throwable $e) {
                $paid = 0;
            }
        }
        return $paid + (int) setting('premium_sold', 0);
    }

    /** Harga yang berlaku sekarang sesuai tingkat pembeli awal */
    public static function price(): int
    {
        return (int) LegalController::PRICE_TIERS[LegalController::currentTier()]['price'];
    }

    /** Aktifkan atau perpanjang Premium 1 tahun. Mengembalikan tanggal berakhir baru. */
    public static function activate(int $userId): string
    {
        q('UPDATE users SET plan = ?, premium_until = DATE_ADD(GREATEST(COALESCE(premium_until, NOW()), NOW()), INTERVAL ' . self::DURATION_DAYS . ' DAY) WHERE id = ?', ['premium', $userId]);
        return (string) q_val('SELECT premium_until FROM users WHERE id = ?', [$userId]);
    }

    public static function revoke(int $userId): void
    {
        q('UPDATE users SET plan = ?, premium_until = NULL WHERE id = ?', ['free', $userId]);
    }
}
