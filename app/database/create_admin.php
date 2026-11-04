<?php
declare(strict_types=1);

/**
 * Membuat akun admin sekali jalan dari config.php (ADMIN_INITIAL_PASSWORD).
 *   php app/database/create_admin.php
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require __DIR__ . '/../bootstrap.php';

Installer::runSchema();
[$ok, $msg] = Auth::createAdminFromConfig();
$messages = [
    'install.ok'                 => 'Akun admin ' . config('admin_username') . ' berhasil dibuat.',
    'install.err.no_password'    => 'ADMIN_INITIAL_PASSWORD di app/config.php masih kosong.',
    'install.err.weak_password'  => 'ADMIN_INITIAL_PASSWORD belum memenuhi aturan password kuat (min. 10 karakter, huruf besar, huruf kecil, angka, simbol).',
    'install.err.exists'         => 'Akun admin sudah ada. Tidak ada yang diubah.',
];
echo ($ok ? '✓ ' : '✕ ') . ($messages[$msg] ?? $msg) . "\n";
if ($ok || $msg === 'install.err.exists') {
    @file_put_contents(APP_PATH . '/installed.lock', date('c'));
    echo "\n>>> Hapus ADMIN_INITIAL_PASSWORD dari config.php sekarang. <<<\n";
}
exit($ok ? 0 : 1);
