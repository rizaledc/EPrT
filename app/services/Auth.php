<?php
declare(strict_types=1);

final class Auth
{
    public const RESERVED = ['admin', 'mimin', 'root', 'perumahaninggris'];
    public const EXAMPLE_PASSWORD = 'Langit!Biru7Kopi';

    public static function hash(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /** @return string[] daftar key error (kosong = valid) */
    public static function validateUsername(string $username, bool $checkUnique = true): array
    {
        $errors = [];
        $len = strlen($username);
        if ($len < 4 || $len > 20) {
            $errors[] = 'auth.err.username_length';
        }
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $username)) {
            $errors[] = 'auth.err.username_chars';
        }
        $lower = strtolower($username);
        foreach (self::RESERVED as $r) {
            if ($lower === $r) {
                $errors[] = 'auth.err.username_reserved';
                break;
            }
        }
        if (!$errors && $checkUnique && self::usernameTaken($username)) {
            $errors[] = 'auth.err.username_taken';
        }
        return $errors;
    }

    public static function usernameTaken(string $username): bool
    {
        return (bool) q_val('SELECT 1 FROM users WHERE username_lower = ?', [strtolower($username)]);
    }

    public static function suggestUsernames(string $username): array
    {
        $base = preg_replace('/[^A-Za-z0-9_]/', '', $username) ?: 'pelajar';
        $base = substr($base, 0, 14);
        $candidates = [$base . '_belajar', $base . date('Y'), $base . '_' . random_int(10, 99), $base . random_int(100, 999)];
        $out = [];
        foreach ($candidates as $c) {
            $c = substr($c, 0, 20);
            if (!self::usernameTaken($c)) {
                $out[] = $c;
            }
            if (count($out) === 2) {
                break;
            }
        }
        return $out;
    }

    /** @return string[] daftar key error */
    public static function validatePassword(string $password, string $username = ''): array
    {
        $errors = [];
        $len = strlen($password);
        if ($len < 10) {
            $errors[] = 'auth.err.pw_length';
        }
        if ($len > 72) {
            $errors[] = 'auth.err.pw_too_long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'auth.err.pw_upper';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'auth.err.pw_lower';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'auth.err.pw_digit';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'auth.err.pw_symbol';
        }
        if ($username !== '' && stripos($password, $username) !== false) {
            $errors[] = 'auth.err.pw_contains_username';
        }
        if ($password === self::EXAMPLE_PASSWORD) {
            $errors[] = 'auth.err.pw_example';
        }
        if (self::isCommon($password)) {
            $errors[] = 'auth.err.pw_common';
        }
        return $errors;
    }

    public static function isCommon(string $password): bool
    {
        static $list = null;
        if ($list === null) {
            $list = [];
            $file = APP_PATH . '/data/common-passwords.txt';
            if (is_file($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
                    $list[strtolower(trim($l))] = true;
                }
            }
        }
        return isset($list[strtolower($password)]);
    }

    public static function newRecoveryCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i = 0; $i < 12; $i++) {
            $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return substr($s, 0, 4) . '-' . substr($s, 4, 4) . '-' . substr($s, 8, 4);
    }

    public static function normalizeRecovery(string $code): string
    {
        $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
        return strlen($c) === 12 ? substr($c, 0, 4) . '-' . substr($c, 4, 4) . '-' . substr($c, 8, 4) : $c;
    }

    public static function setRecovery(int $userId): string
    {
        $code = self::newRecoveryCode();
        q('UPDATE users SET recovery_hash = ? WHERE id = ?', [password_hash($code, PASSWORD_BCRYPT), $userId]);
        return $code;
    }

    /* ---------------- Rate limit login ---------------- */

    /** Mengembalikan timestamp kapan kunci berakhir, atau null jika tidak terkunci. */
    public static function lockedUntil(string $username): ?int
    {
        $ipHash = ip_hash();
        $u = strtolower($username);
        $rows = [
            q_all('SELECT created_at FROM login_attempts WHERE username = ? AND success = 0 AND created_at > NOW() - INTERVAL 15 MINUTE ORDER BY created_at DESC LIMIT 5', [$u]),
            q_all('SELECT created_at FROM login_attempts WHERE ip_hash = ? AND success = 0 AND created_at > NOW() - INTERVAL 15 MINUTE ORDER BY created_at DESC LIMIT 5', [$ipHash]),
        ];
        $until = null;
        foreach ($rows as $r) {
            if (count($r) >= 5) {
                // Kunci 15 menit sejak percobaan gagal ke-5 terbaru
                $t = strtotime((string) $r[0]['created_at']) + 900;
                $until = max($until ?? 0, $t);
            }
        }
        return ($until && $until > time()) ? $until : null;
    }

    public static function recordAttempt(string $username, bool $success): void
    {
        q('INSERT INTO login_attempts (username, ip_hash, success) VALUES (?, ?, ?)', [strtolower(substr($username, 0, 40)), ip_hash(), $success ? 1 : 0]);
        if (random_int(1, 40) === 1) {
            q('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 180 DAY');
        }
    }

    public static function attempt(string $username, string $password): ?array
    {
        $user = q_one('SELECT * FROM users WHERE username_lower = ?', [strtolower($username)]);
        // Selalu jalankan verifikasi agar waktu respons serupa
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $ok = password_verify($password, $hash);
        if (!$user || !$ok || $user['status'] !== 'active') {
            return null;
        }
        if (password_needs_rehash($user['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [self::hash($password), $user['id']]);
        }
        return $user;
    }

    public static function login(array $user, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['last_seen'] = time();
        unset($_SESSION['csrf']);
        q('UPDATE users SET last_active_at = NOW() WHERE id = ?', [$user['id']]);
        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        }
        reload_user();
    }

    public static function issueRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        q('INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 30 DAY)', [$userId, hash('sha256', $token)]);
        setcookie('pi_remember', $userId . ':' . $token, [
            'expires'  => time() + 86400 * 30,
            'path'     => '/',
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function loginFromRememberCookie(): ?int
    {
        $raw = (string) ($_COOKIE['pi_remember'] ?? '');
        if (!preg_match('/^(\d+):([a-f0-9]{64})$/', $raw, $m)) {
            return null;
        }
        try {
            $row = q_one('SELECT rt.id, rt.user_id, u.role FROM remember_tokens rt JOIN users u ON u.id = rt.user_id
                          WHERE rt.token_hash = ? AND rt.user_id = ? AND rt.expires_at > NOW() AND u.status = ?', [hash('sha256', $m[2]), (int) $m[1], 'active']);
        } catch (Throwable $e) {
            return null;
        }
        if (!$row) {
            self::clearRememberCookie();
            return null;
        }
        // Admin tidak memakai "ingat saya" (sesi 2 jam)
        if ($row['role'] === 'admin') {
            return null;
        }
        // Rotasi token
        q('DELETE FROM remember_tokens WHERE id = ?', [$row['id']]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['user_id'];
        self::issueRememberToken((int) $row['user_id']);
        return (int) $row['user_id'];
    }

    public static function clearRememberCookie(): void
    {
        setcookie('pi_remember', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
    }

    public static function logout(): void
    {
        $raw = (string) ($_COOKIE['pi_remember'] ?? '');
        if (preg_match('/^(\d+):([a-f0-9]{64})$/', $raw, $m)) {
            q('DELETE FROM remember_tokens WHERE token_hash = ?', [hash('sha256', $m[2])]);
        }
        self::clearRememberCookie();
        $_SESSION = [];
        session_regenerate_id(true);
        reload_user();
    }

    public static function createUser(string $username, string $password, string $lang = 'id', string $role = 'member'): int
    {
        q('INSERT INTO users (username, username_lower, password_hash, role, lang, target_score) VALUES (?, ?, ?, ?, ?, ?)', [
            $username, strtolower($username), self::hash($password), $role, in_array($lang, ['id', 'en'], true) ? $lang : 'id',
            (int) (setting('default_target', 500) ?: 500),
        ]);
        return (int) db()->lastInsertId();
    }

    /** Membuat akun admin dari config. Dipakai oleh create_admin.php dan /install. */
    public static function createAdminFromConfig(): array
    {
        $username = (string) config('admin_username', 'MiminEPrT2026');
        $password = (string) config('ADMIN_INITIAL_PASSWORD', '');
        if ($password === '') {
            return [false, 'install.err.no_password'];
        }
        $errs = array_diff(self::validatePassword($password), ['auth.err.pw_contains_username']);
        if ($errs) {
            return [false, 'install.err.weak_password'];
        }
        $existing = q_one('SELECT id FROM users WHERE username_lower = ?', [strtolower($username)]);
        if ($existing) {
            return [false, 'install.err.exists'];
        }
        q('INSERT INTO users (username, username_lower, password_hash, role, lang) VALUES (?, ?, ?, ?, ?)', [
            $username, strtolower($username), self::hash($password), 'admin', 'id',
        ]);
        return [true, 'install.ok'];
    }
}
