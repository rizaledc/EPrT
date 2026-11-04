<?php
declare(strict_types=1);
/**
 * Menyiapkan database kosong untuk pemeriksaan di CI (bukan untuk server produksi).
 * Membuat tabel, satu admin, dan satu member uji, lalu menandai instalasi selesai.
 *   CI_ADMIN_PASS=... CI_MEMBER_PASS=... php tests/ci_setup.php
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require __DIR__ . '/../app/bootstrap.php';

$adminPass = getenv('CI_ADMIN_PASS') ?: '';
$memberPass = getenv('CI_MEMBER_PASS') ?: '';
if ($adminPass === '' || $memberPass === '') {
    fwrite(STDERR, "CI_ADMIN_PASS dan CI_MEMBER_PASS wajib diisi\n");
    exit(1);
}
Installer::runSchema();
Auth::createUser((string) config('admin_username'), $adminPass, 'id', 'admin');
Auth::createUser('uji_ci', $memberPass, 'id', 'member');
file_put_contents(APP_PATH . '/installed.lock', date('c'));
echo "Database CI siap.\n";
