<?php
declare(strict_types=1);

/**
 * Sesi login per perangkat (tabel user_sessions), untuk membatasi akun yang dibagi-bagi.
 *
 * - Setiap login membuat satu baris; PHP session menyimpan id baris + token acak (hanya hash
 *   token yang disimpan di database).
 * - Setiap request mengecek baris itu masih aktif. Bila sudah dicabut, session PHP dan cookie
 *   Ingat saya dihapus dan pengguna diarahkan ke /masuk (API: 401 session_revoked).
 * - Batas sesi aktif per akun (member) diatur di Admin → Pengaturan (setting max_sessions,
 *   default 2). Login baru yang melebihi batas mencabut sesi paling lama beserta token Ingat saya
 *   (token itu ditolak saat dipakai karena sesinya sudah dicabut).
 */
final class UserSession
{
    public const DEFAULT_LIMIT = 2;
    private const TOUCH_EVERY_S = 60;

    public static function limit(): int
    {
        return max(1, min(10, (int) (setting('max_sessions', self::DEFAULT_LIMIT) ?: self::DEFAULT_LIMIT)));
    }

    /** Buat sesi baru untuk login ini dan simpan penandanya di session PHP. */
    public static function start(array $user, bool $remember): int
    {
        $token = bin2hex(random_bytes(32));
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        q('INSERT INTO user_sessions (user_id, session_hash, device_label, ua_hash, ip_hash, remember) VALUES (?, ?, ?, ?, ?, ?)', [
            $user['id'], hash('sha256', $token), self::deviceLabel($ua), hash('sha256', $ua), ip_hash(), $remember ? 1 : 0,
        ]);
        $id = (int) db()->lastInsertId();
        $_SESSION['us_id'] = $id;
        $_SESSION['us_token'] = $token;
        if (($user['role'] ?? 'member') !== 'admin') {
            self::enforceLimit((int) $user['id'], $id);
        }
        return $id;
    }

    /** Pakai ulang baris sesi yang sudah ada (login dari cookie Ingat saya): token dirotasi. */
    public static function resume(int $sessionId): void
    {
        $token = bin2hex(random_bytes(32));
        q('UPDATE user_sessions SET session_hash = ?, last_seen_at = NOW(), ip_hash = ? WHERE id = ?', [hash('sha256', $token), ip_hash(), $sessionId]);
        $_SESSION['us_id'] = $sessionId;
        $_SESSION['us_token'] = $token;
    }

    public static function currentId(): ?int
    {
        return isset($_SESSION['us_id']) ? (int) $_SESSION['us_id'] : null;
    }

    /** true bila sesi ini masih aktif; false bila sudah dicabut (atau tidak cocok). */
    public static function check(array $user): bool
    {
        $id = self::currentId();
        $token = (string) ($_SESSION['us_token'] ?? '');
        if (!$id || $token === '') {
            // Login dari versi sebelum fitur ini: daftarkan sebagai sesi baru
            self::start($user, false);
            return true;
        }
        $row = q_one('SELECT * FROM user_sessions WHERE id = ?', [$id]);
        if (!$row || (int) $row['user_id'] !== (int) $user['id'] || $row['revoked_at'] !== null || !hash_equals((string) $row['session_hash'], hash('sha256', $token))) {
            return false;
        }
        if (strtotime((string) $row['last_seen_at']) < time() - self::TOUCH_EVERY_S) {
            q('UPDATE user_sessions SET last_seen_at = NOW(), ip_hash = ? WHERE id = ?', [ip_hash(), $id]);
        }
        return true;
    }

    /** Sesi tak aktif dianggap berakhir: tanpa Ingat saya > 1 hari, dengan Ingat saya > 30 hari. */
    public static function expireStale(int $userId): void
    {
        q("UPDATE user_sessions SET revoked_at = NOW(), revoke_reason = 'expired'
           WHERE user_id = ? AND revoked_at IS NULL
             AND ((remember = 0 AND last_seen_at < NOW() - INTERVAL 1 DAY) OR last_seen_at < NOW() - INTERVAL 30 DAY)", [$userId]);
    }

    public static function enforceLimit(int $userId, int $keepId): void
    {
        self::expireStale($userId);
        $active = q_all('SELECT id FROM user_sessions WHERE user_id = ? AND revoked_at IS NULL AND id <> ? ORDER BY created_at DESC, id DESC', [$userId, $keepId]);
        $allowedOthers = self::limit() - 1;
        $over = array_slice(array_map(fn($r) => (int) $r['id'], $active), max(0, $allowedOthers));
        if ($over) {
            self::revokeIds($over, 'limit');
        }
    }

    /** @param int[] $ids */
    public static function revokeIds(array $ids, string $reason): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        q("UPDATE user_sessions SET revoked_at = NOW(), revoke_reason = ? WHERE id IN ($in) AND revoked_at IS NULL", array_merge([$reason], $ids));
        // Token Ingat saya milik sesi ini ikut tidak berlaku: Auth::loginFromRememberCookie menolak token
        // yang sesinya dicabut (lalu menghapusnya dan menampilkan pesan). Baris token dibiarkan sampai
        // dipakai sekali atau kedaluwarsa (30 hari) agar browser itu tetap mendapat pesan yang jelas.
        q("UPDATE remember_tokens SET expires_at = LEAST(expires_at, NOW() + INTERVAL 30 DAY) WHERE user_session_id IN ($in)", $ids);
    }

    /** Cabut semua sesi pengguna, kecuali satu (mis. sesi yang sedang dipakai). */
    public static function revokeAll(int $userId, ?int $exceptId, string $reason): int
    {
        $rows = q_all('SELECT id FROM user_sessions WHERE user_id = ? AND revoked_at IS NULL', [$userId]);
        $ids = array_values(array_filter(array_map(fn($r) => (int) $r['id'], $rows), fn($id) => $id !== $exceptId));
        self::revokeIds($ids, $reason);
        // Token Ingat saya lama (tanpa sesi) ikut dihapus
        if ($exceptId) {
            q('DELETE FROM remember_tokens WHERE user_id = ? AND (user_session_id IS NULL OR user_session_id <> ?)', [$userId, $exceptId]);
        } else {
            q('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
        }
        return count($ids);
    }

    /** Sesi ini sudah dicabut: bersihkan session PHP dan cookie Ingat saya di browser ini. */
    public static function kick(): void
    {
        $raw = (string) ($_COOKIE['pi_remember'] ?? '');
        if (preg_match('/^(\d+):([a-f0-9]{64})$/', $raw, $m)) {
            q('DELETE FROM remember_tokens WHERE token_hash = ?', [hash('sha256', $m[2])]);
        }
        Auth::clearRememberCookie();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        // Penanda di session baru: request berikutnya dari browser ini (mis. API mesin soal)
        // tetap mendapat pesan yang sama sampai pengguna masuk lagi
        $_SESSION['session_revoked'] = true;
        $GLOBALS['__session_revoked'] = true;
    }

    public static function wasRevoked(): bool
    {
        return !empty($GLOBALS['__session_revoked']) || !empty($_SESSION['session_revoked']);
    }

    /** @return array<int,array> sesi aktif, terbaru dulu */
    public static function active(int $userId): array
    {
        self::expireStale($userId);
        return q_all('SELECT * FROM user_sessions WHERE user_id = ? AND revoked_at IS NULL ORDER BY last_seen_at DESC', [$userId]);
    }

    public static function recent(int $userId, int $limit = 30): array
    {
        return q_all('SELECT * FROM user_sessions WHERE user_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit), [$userId]);
    }

    /**
     * Pemakaian janggal: dalam 7 hari login dari > 4 perangkat berbeda, atau > 3 IP berbeda
     * dalam rentang 1 jam.
     * @return string[] alasan (kosong = wajar)
     */
    public static function suspicious(int $userId): array
    {
        $rows = q_all('SELECT ua_hash, ip_hash, created_at FROM user_sessions WHERE user_id = ? AND created_at > NOW() - INTERVAL 7 DAY ORDER BY created_at', [$userId]);
        $reasons = [];
        $devices = count(array_unique(array_column($rows, 'ua_hash')));
        if ($devices > 4) {
            $reasons[] = $devices . ' perangkat berbeda dalam 7 hari';
        }
        $maxIp = 0;
        $n = count($rows);
        for ($i = 0; $i < $n; $i++) {
            $start = strtotime((string) $rows[$i]['created_at']);
            $ips = [];
            for ($j = $i; $j < $n && strtotime((string) $rows[$j]['created_at']) - $start <= 3600; $j++) {
                $ips[$rows[$j]['ip_hash']] = true;
            }
            $maxIp = max($maxIp, count($ips));
        }
        if ($maxIp > 3) {
            $reasons[] = $maxIp . ' IP berbeda dalam 1 jam';
        }
        return $reasons;
    }

    /** Nama perangkat sederhana dari user agent, mis. Chrome · Android. */
    public static function deviceLabel(string $ua): string
    {
        $os = 'Perangkat lain';
        foreach ([
            'iPad' => '/iPad/', 'iPhone' => '/iPhone/', 'Android' => '/Android/', 'Windows' => '/Windows/',
            'macOS' => '/Mac OS X|Macintosh/', 'ChromeOS' => '/CrOS/', 'Linux' => '/Linux/',
        ] as $name => $re) {
            if (preg_match($re, $ua)) {
                $os = $name;
                break;
            }
        }
        $browser = 'Browser';
        foreach ([
            'Edge' => '/Edg\//', 'Opera' => '/OPR\/|Opera/', 'Samsung Internet' => '/SamsungBrowser/', 'Firefox' => '/Firefox\/|FxiOS/',
            'Chrome' => '/Chrome\/|CriOS/', 'Safari' => '/Safari\//',
        ] as $name => $re) {
            if (preg_match($re, $ua)) {
                $browser = $name;
                break;
            }
        }
        return $browser . ' · ' . $os;
    }
}
