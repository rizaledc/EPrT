<?php
declare(strict_types=1);

/* ============================================================
 * Logging
 * ============================================================ */

function app_log(string $level, string $message): void
{
    $dir = STORAGE_PATH . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . "] $level: $message\n";
    @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
}

/* ============================================================
 * Database
 * ============================================================ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'],
            (int) ($c['port'] ?? 3306),
            $c['name'],
            $c['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        $offset = (new DateTime())->format('P');
        $pdo->exec("SET time_zone = '$offset'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function q_val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function json_col($value, $default = [])
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_array($value)) {
        return $value;
    }
    $d = json_decode((string) $value, true);
    return $d === null ? $default : $d;
}

/* ============================================================
 * Settings (tabel settings, nilai JSON)
 * ============================================================ */

function setting(string $key, $default = null)
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        try {
            $v = q_val('SELECT value FROM settings WHERE `key` = ?', [$key]);
            $cache[$key] = $v === null ? null : json_decode((string) $v, true);
        } catch (Throwable $e) {
            $cache[$key] = null;
        }
    }
    return $cache[$key] ?? $default;
}

function setting_save(string $key, $value): void
{
    q('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$key, json_encode($value, JSON_UNESCAPED_UNICODE)]);
}

/* ============================================================
 * Output & URL
 * ============================================================ */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    return rtrim((string) config('base_url', ''), '/');
}

/** URL relatif dari root situs (aman untuk subfolder bila base_url berisi path). */
function url(string $path = '/'): string
{
    $basePath = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');
    return $basePath . '/' . ltrim($path, '/');
}

function abs_url(string $path = '/'): string
{
    $scheme = parse_url(base_url(), PHP_URL_SCHEME) ?: 'https';
    $host = parse_url(base_url(), PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $port = parse_url(base_url(), PHP_URL_PORT);
    return $scheme . '://' . $host . ($port ? ":$port" : '') . url($path);
}

function asset(string $path): string
{
    $file = PUBLIC_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr(md5((string) filemtime($file)), 0, 8) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $to, int $code = 302): never
{
    if (!preg_match('#^https?://#', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to, true, $code);
    exit;
}

/** Hanya izinkan redirect ke path lokal (cegah open redirect). */
function safe_next(?string $next, string $fallback = '/latihan'): string
{
    $next = (string) $next;
    if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '\\')) {
        return $fallback;
    }
    $basePath = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');
    if ($basePath !== '' && str_starts_with($next, $basePath . '/')) {
        $next = substr($next, strlen($basePath));
    }
    return $next;
}

function request_path(): string
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $basePath = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');
    if ($basePath !== '' && str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath));
    }
    $path = '/' . trim(rawurldecode($path), '/');
    return $path;
}

function current_uri(): string
{
    return safe_next($_SERVER['REQUEST_URI'] ?? '/', '/');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_api_request(): bool
{
    return str_starts_with(request_path(), '/api/');
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function json_body(): array
{
    static $body = null;
    if ($body === null) {
        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);
        if (!is_array($body)) {
            $body = [];
        }
    }
    return $body;
}

function json_response($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function ip_hash(?string $ip = null): string
{
    return hash('sha256', ($ip ?? client_ip()) . config('app_secret'));
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

/* ============================================================
 * Session, header keamanan, CSRF
 * ============================================================ */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $savePath = STORAGE_PATH . '/sessions';
    if (!is_dir($savePath)) {
        @mkdir($savePath, 0770, true);
    }
    if (is_dir($savePath) && is_writable($savePath)) {
        session_save_path($savePath);
    }
    session_name('pi_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '86400');
    session_start();
}

function send_security_headers(): void
{
    $csp = "default-src 'self'; "
         . "script-src 'self'; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data:; media-src 'self' blob:; connect-src 'self'; "
         . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'";
    header('Content-Security-Policy: ' . $csp);
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (NOINDEX) {
        header('X-Robots-Tag: noindex, nofollow');
    }
    if (config('hsts') && is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (is_api_request()) {
            json_response(['ok' => false, 'error' => 'csrf'], 419);
        }
        http_response_code(419);
        echo view('errors/generic', ['title' => t('error.csrf.title'), 'code' => 419, 'message' => t('error.csrf.body'), 'noindex' => true], 'layout');
        exit;
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

/* ============================================================
 * Rate limit sederhana berbasis DB
 * ============================================================ */

function rate_hit(string $bucket, int $limit, int $windowSeconds): bool
{
    if (random_int(1, 50) === 1) {
        q('DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL 1 DAY');
    }
    $count = (int) q_val(
        'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND created_at > NOW() - INTERVAL ? SECOND',
        [$bucket, $windowSeconds]
    );
    if ($count >= $limit) {
        return false;
    }
    q('INSERT INTO rate_limits (bucket) VALUES (?)', [$bucket]);
    return true;
}

/* ============================================================
 * Bahasa (i18n)
 * ============================================================ */

function lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $q = $_GET['lang'] ?? null;
    if (is_string($q) && in_array($q, ['id', 'en'], true)) {
        return $lang = $q;
    }
    $u = current_user();
    if ($u && in_array($u['lang'], ['id', 'en'], true)) {
        return $lang = $u['lang'];
    }
    $c = $_COOKIE['lang'] ?? 'id';
    return $lang = in_array($c, ['id', 'en'], true) ? $c : 'id';
}

function set_lang_runtime(string $l): void
{
    // Dipakai untuk PDF/CLI. Menimpa cache statis lang() melalui global.
    $GLOBALS['__lang_override'] = $l;
}

function active_lang(): string
{
    return $GLOBALS['__lang_override'] ?? lang();
}

function lang_strings(string $l): array
{
    static $cache = [];
    if (!isset($cache[$l])) {
        $file = APP_PATH . "/lang/$l.php";
        $cache[$l] = is_file($file) ? require $file : [];
    }
    return $cache[$l];
}

function t(string $key, array $vars = []): string
{
    $l = active_lang();
    $strings = lang_strings($l);
    if (array_key_exists($key, $strings)) {
        $s = $strings[$key];
    } else {
        $id = lang_strings('id');
        if ($l !== 'id') {
            app_log('i18n', "missing key [$l] $key");
        }
        $s = $id[$key] ?? $key;
    }
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return $s;
}

/** Ambil kolom _id / _en sesuai bahasa; fallback ke _id + chip "ID". */
function tr(array $row, string $field): string
{
    $l = active_lang();
    $v = (string) ($row[$field . '_' . $l] ?? '');
    if ($v === '' && $l !== 'id') {
        $v = (string) ($row[$field . '_id'] ?? '');
    }
    return $v;
}

/** Chip kecil "ID" bila konten EN kosong dan memakai versi Indonesia. */
function fallback_chip(array $row, string $field): string
{
    if (active_lang() === 'en' && trim((string) ($row[$field . '_en'] ?? '')) === '' && trim((string) ($row[$field . '_id'] ?? '')) !== '') {
        return ' <span class="chip chip-xs" title="Indonesian version">ID</span>';
    }
    return '';
}

/** Sama seperti tr() tetapi mengembalikan [teks, fallback?]. */
function tr_pair(array $row, string $field): array
{
    $l = active_lang();
    $v = $row[$field . '_' . $l] ?? null;
    if (($v === null || $v === '' || $v === []) && $l !== 'id') {
        return [$row[$field . '_id'] ?? '', true];
    }
    return [$v ?? '', false];
}

function fmt_date($dt, bool $withTime = true): string
{
    if (!$dt) {
        return '–';
    }
    $ts = is_numeric($dt) ? (int) $dt : strtotime((string) $dt);
    if (!$ts) {
        return '–';
    }
    if (active_lang() === 'en') {
        return date($withTime ? 'M j, Y, g:i A' : 'M j, Y', $ts);
    }
    $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $s = date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $s . ', ' . date('H.i', $ts) : $s;
}

function fmt_time(int $ts): string
{
    return active_lang() === 'en' ? date('g:i A', $ts) : date('H.i', $ts);
}

function fmt_num($n, int $dec = 0): string
{
    return active_lang() === 'en'
        ? number_format((float) $n, $dec, '.', ',')
        : number_format((float) $n, $dec, ',', '.');
}

function fmt_duration(int $seconds): string
{
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    return sprintf('%d:%02d', $m, $s);
}

/* ============================================================
 * Auth
 * ============================================================ */

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded && !isset($GLOBALS['__user_reload'])) {
        return $user;
    }
    unset($GLOBALS['__user_reload']);
    $loaded = true;
    if (PHP_SAPI === 'cli') {
        return $user = null;
    }
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid && !empty($_COOKIE['pi_remember'])) {
        $uid = Auth::loginFromRememberCookie();
    }
    if (!$uid) {
        return $user = null;
    }
    try {
        $user = q_one('SELECT * FROM users WHERE id = ? AND status = ?', [$uid, 'active']);
    } catch (Throwable $e) {
        $user = null;
    }
    if (!$user) {
        unset($_SESSION['user_id']);
        return $user = null;
    }
    // Sesi admin kedaluwarsa setelah 2 jam tanpa aktivitas
    $now = time();
    if ($user['role'] === 'admin' && isset($_SESSION['last_seen']) && $now - (int) $_SESSION['last_seen'] > 7200) {
        Auth::logout();
        flash('info', t('auth.admin_expired'));
        return $user = null;
    }
    $_SESSION['last_seen'] = $now;
    if (empty($user['last_active_at']) || strtotime((string) $user['last_active_at']) < $now - 300) {
        q('UPDATE users SET last_active_at = NOW() WHERE id = ?', [$user['id']]);
    }
    return $user;
}

function reload_user(): void
{
    $GLOBALS['__user_reload'] = true;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (is_api_request()) {
            json_response(['ok' => false, 'error' => 'auth'], 401);
        }
        redirect('/masuk?next=' . rawurlencode(current_uri()));
    }
    if ((int) $u['must_change_password'] === 1 && !in_array(request_path(), ['/akun', '/keluar'], true)) {
        if (is_api_request()) {
            json_response(['ok' => false, 'error' => 'must_change_password'], 403);
        }
        flash('info', t('account.must_change'));
        redirect('/akun');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        abort(404);
    }
    return $u;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

/* ============================================================
 * Views
 * ============================================================ */

function view(string $__view, array $__data = [], ?string $__layout = null): string
{
    $__file = APP_PATH . '/views/' . $__view . '.php';
    if (!is_file($__file)) {
        throw new RuntimeException("View tidak ditemukan: $__view");
    }
    extract($__data, EXTR_SKIP);
    ob_start();
    require $__file;
    $__content = ob_get_clean();
    if ($__layout !== null) {
        $__data['content'] = $__content;
        return view($__layout, $__data);
    }
    return $__content;
}

function render(string $name, array $data = [], string $layout = 'layout'): never
{
    echo view($name, $data, $layout);
    unset($_SESSION['old']);
    exit;
}

function partial(string $name, array $data = []): string
{
    return view('partials/' . $name, $data);
}

function abort(int $code = 404, ?string $message = null): never
{
    http_response_code($code);
    if (is_api_request()) {
        json_response(['ok' => false, 'error' => $message ?? 'not_found'], $code);
    }
    if ($code === 404) {
        echo view('errors/404', ['title' => t('error.404.title'), 'noindex' => true], 'layout');
    } else {
        echo view('errors/generic', ['title' => (string) $code, 'code' => $code, 'message' => $message ?? ''], 'layout');
    }
    exit;
}

/* ============================================================
 * Ikon SVG inline (outline, stroke 1.75, sudut membulat)
 * ============================================================ */

function icon(string $name, int $size = 20, string $class = ''): string
{
    $paths = [
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
        'check'       => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x'           => '<path d="M6 6l12 12M18 6 6 18"/>',
        'circle'      => '<circle cx="12" cy="12" r="7.5"/>',
        'menu'        => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'eye'         => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'     => '<path d="M3 3l18 18M10.6 6.1A9.6 9.6 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-3 3.6M6.4 6.9C3.9 8.6 2.5 12 2.5 12S6 18.5 12 18.5c1.7 0 3.2-.5 4.4-1.2M9.9 10a3 3 0 0 0 4.2 4.1"/>',
        'headphones'  => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="3" y="14" width="4.5" height="6.5" rx="1.5"/><rect x="16.5" y="14" width="4.5" height="6.5" rx="1.5"/>',
        'pen'         => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'book'        => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/>',
        'bulb'        => '<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3Z"/>',
        'tag'         => '<path d="M3 12V4.5A1.5 1.5 0 0 1 4.5 3H12l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'save'        => '<path d="M5 3h11l3 3v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M7 3v5h8V3M7 21v-7h10v7"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'flag'        => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'play'        => '<path d="M7 4.5v15l12-7.5-12-7.5Z"/>',
        'replay'      => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'download'    => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'lock'        => '<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
        'home'        => '<path d="M4 10.5 12 4l8 6.5V19a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19v-8.5Z"/><path d="M9.5 20.5v-6h5v6"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'       => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
        'grid'        => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>',
        'list'        => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'chart'       => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'upload'      => '<path d="M12 20V9M7 14l5-5 5 5M5 4h14"/>',
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'logout'      => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/>',
        'copy'        => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
        'trash'       => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'sparkle'     => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
        'target'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'speed'       => '<path d="M12 14l4-4"/><path d="M3.5 18a9.5 9.5 0 1 1 17 0"/>',
        'file'        => '<path d="M14 3H6.5A1.5 1.5 0 0 0 5 4.5v15A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5V8l-5-5Z"/><path d="M14 3v5h5"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'volume'      => '<path d="M4 9.5h3.5L12 5.5v13l-4.5-4H4z"/><path d="M15.5 9a4 4 0 0 1 0 6M18 6.5a7.5 7.5 0 0 1 0 11"/>',
        'refresh'     => '<path d="M20 11a8 8 0 0 0-14.6-4.5L3 9M4 13a8 8 0 0 0 14.6 4.5L21 15"/><path d="M3 4v5h5M21 20v-5h-5"/>',
        'shield'      => '<path d="M12 3 4.5 6v6c0 4.5 3.2 7.8 7.5 9 4.3-1.2 7.5-4.5 7.5-9V6L12 3Z"/>',
        'pin'         => '<path d="M12 21v-6M8 4h8l-1 6 3 3H6l3-3-1-6Z"/>',
    ];
    $p = $paths[$name] ?? $paths['circle'];
    return '<svg class="icon ' . e($class) . '" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $p . '</svg>';
}

/** Tombol pill primer dengan lingkaran panah. */
function btn_primary(string $label, string $href, string $extraClass = '', string $attrs = ''): string
{
    return '<a class="btn btn-primary ' . e($extraClass) . '" href="' . e($href) . '" ' . $attrs . '><span>' . e($label)
        . '</span><span class="btn-arrow" aria-hidden="true">' . icon('arrow-right', 18) . '</span></a>';
}

/* ============================================================
 * Konten kaya (markdown kecil yang aman)
 * ============================================================ */

/**
 * Merender teks konten admin / seed:
 *  - Semua HTML mentah di-escape (dibuang)
 *  - **tebal**, *miring*, `kode`, __garis bawah__
 *  - [[term]] atau [[term|slug]] → komponen <term> dengan tooltip Kamus
 *  - "- " daftar, "✓ " / "✕ " contoh benar/salah, "## " subjudul
 */
function rich(string $text, ?array &$seenTerms = null): string
{
    if ($seenTerms === null) {
        $seenTerms = [];
    }
    $lines = preg_split("/\r\n|\n|\r/", trim($text));
    $html = '';
    $inList = false;
    $para = [];
    $flushPara = function () use (&$para, &$html, &$seenTerms) {
        if ($para) {
            $html .= '<p>' . rich_inline(implode(' ', $para), $seenTerms) . '</p>';
            $para = [];
        }
    };
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '') {
            $flushPara();
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            continue;
        }
        if (preg_match('/^(-|✓|✕)\s+(.*)$/u', $trim, $m)) {
            $flushPara();
            if (!$inList) {
                $html .= '<ul class="rich-list">';
                $inList = true;
            }
            $cls = $m[1] === '✓' ? 'ex-good' : ($m[1] === '✕' ? 'ex-bad' : '');
            $mark = $m[1] === '✓' ? '<span class="ex-mark">' . icon('check', 16) . '<span class="sr-only">' . e(t('common.correct')) . '</span></span>'
                : ($m[1] === '✕' ? '<span class="ex-mark">' . icon('x', 16) . '<span class="sr-only">' . e(t('common.wrong')) . '</span></span>' : '');
            $html .= '<li class="' . $cls . '">' . $mark . '<span>' . rich_inline($m[2], $seenTerms) . '</span></li>';
            continue;
        }
        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }
        if (preg_match('/^##\s+(.*)$/u', $trim, $m)) {
            $flushPara();
            $html .= '<h4>' . rich_inline($m[1], $seenTerms) . '</h4>';
            continue;
        }
        if (preg_match('/^>\s+(.*)$/u', $trim, $m)) {
            $flushPara();
            $html .= '<p class="formula">' . rich_inline($m[1], $seenTerms) . '</p>';
            continue;
        }
        $para[] = $trim;
    }
    $flushPara();
    if ($inList) {
        $html .= '</ul>';
    }
    return $html;
}

function rich_inline(string $text, array &$seenTerms): string
{
    $s = e($text);
    $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);
    $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/__(.+?)__/s', '<u>$1</u>', $s);
    $s = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '<em>$1</em>', $s);
    $s = preg_replace_callback('/\[\[([^\]|]+)(?:\|([a-z0-9\-]+))?\]\]/i', function ($m) use (&$seenTerms) {
        return term_html(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), $m[2] ?? null, $seenTerms);
    }, $s);
    return $s;
}

function glossary_index(): array
{
    static $idx = null;
    if ($idx === null) {
        $idx = [];
        try {
            foreach (q_all('SELECT slug, term_en, meaning_id, def_id, def_en FROM glossary') as $g) {
                $idx['slug:' . $g['slug']] = $g;
                $idx['term:' . mb_strtolower($g['term_en'])] = $g;
            }
        } catch (Throwable $e) {
        }
    }
    return $idx;
}

/** Komponen <term>: ID → "verb (kata kerja)" pada kemunculan pertama; EN → "verb". */
function term_html(string $term, ?string $slug, array &$seenTerms): string
{
    $idx = glossary_index();
    $g = $slug ? ($idx['slug:' . $slug] ?? null) : ($idx['term:' . mb_strtolower($term)] ?? null);
    $key = mb_strtolower($g['slug'] ?? $term);
    $first = !isset($seenTerms[$key]);
    $seenTerms[$key] = true;
    $label = e($term);
    if ($g && active_lang() === 'id' && $first) {
        $label .= ' <span class="term-mean">(' . e($g['meaning_id']) . ')</span>';
    }
    if (!$g) {
        return '<span class="term">' . $label . '</span>';
    }
    $def = active_lang() === 'en' ? ($g['def_en'] ?: $g['def_id']) : $g['def_id'];
    return '<button type="button" class="term" data-term-tip aria-describedby="tip-' . e($g['slug']) . '-' . count($seenTerms) . '">' . $label
        . '<span role="tooltip" class="term-tip" id="tip-' . e($g['slug']) . '-' . count($seenTerms) . '"><strong>' . e($g['term_en']) . '</strong> · ' . e($g['meaning_id'])
        . '<br>' . e((string) $def) . ' <a href="' . e(url('kamus#' . $g['slug'])) . '">' . e(t('glossary.open')) . '</a></span></button>';
}

/** Teks soal: {A}..{/A} → bagian bergaris bawah berlabel. **kata** → tebal. */
function render_question_text(string $prompt): string
{
    $s = e($prompt);
    $s = preg_replace_callback('/\{([A-D])\}(.+?)\{\/\1\}/s', function ($m) {
        return '<span class="ul-part"><u>' . $m[2] . '</u><span class="ul-label">' . $m[1] . '</span></span>';
    }, $s);
    $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
    $s = str_replace('______', '<span class="blank" aria-label="blank">_______</span>', $s);
    return $s;
}

function section_label(string $section): string
{
    return t('section.' . $section);
}

function section_color(string $section): string
{
    return ['listening' => 'pink', 'structure' => 'plum', 'reading' => 'teal'][$section] ?? 'pink';
}

function admin_log(string $action, ?string $target = null, array $meta = []): void
{
    $u = current_user();
    q('INSERT INTO admin_log (admin_id, action, target, meta) VALUES (?, ?, ?, ?)', [
        $u['id'] ?? null, $action, $target, $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
    ]);
}

function audio_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    return url($path);
}

function audio_exists(?string $path): bool
{
    return $path !== null && $path !== '' && is_file(PUBLIC_PATH . '/' . ltrim($path, '/'));
}
