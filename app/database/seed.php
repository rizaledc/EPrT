<?php
declare(strict_types=1);

/**
 * Impor konten JSON dari app/database/seed ke database.
 *
 *   php app/database/seed.php              # buat tabel (bila belum) + impor semua
 *   php app/database/seed.php --validate   # hanya validasi, tidak menulis DB
 *   php app/database/seed.php --only r01   # hanya satu Rumah
 *   php app/database/seed.php --force      # impor ulang walau file tidak berubah
 *
 * Aman dijalankan berulang (dipakai setiap deploy):
 *  - Tabel dibuat dengan CREATE TABLE IF NOT EXISTS; data dasar memakai INSERT IGNORE.
 *  - Soal di-upsert per (Rumah, seksi, nomor) sehingga ID soal tetap → jawaban, progres,
 *    Buku Kesalahan pengguna tidak tersentuh. Tidak ada DELETE.
 *  - File yang tidak berubah sejak impor terakhir dilewati (hash di settings.seed_hashes),
 *    jadi edit dari panel admin tidak tertimpa sampai file JSON-nya diubah di repo.
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require __DIR__ . '/../bootstrap.php';

$args = array_slice($argv, 1);
$validate = in_array('--validate', $args, true);
$only = null;
foreach ($args as $i => $a) {
    if ($a === '--only' && isset($args[$i + 1])) {
        $only = $args[$i + 1];
    }
}

if (!$validate) {
    Installer::runSchema();
    echo "✓ Skema database siap\n";
}
$imp = new ContentImporter();
$imp->force = in_array('--force', $args, true);
$r = $imp->importDirectory(__DIR__ . '/seed', $validate, $only);

$labels = ['imported' => '✓ diimpor', 'unchanged' => '· tidak berubah, dilewati', 'invalid' => '✕ tidak valid, tidak diimpor'];
foreach ($imp->report as $file => $state) {
    echo "  {$labels[$state]}: $file\n";
}

foreach ($r['warnings'] as $w) {
    echo "  ! $w\n";
}
foreach ($r['errors'] as $e) {
    echo "  ✕ $e\n";
}
echo ($validate ? 'Validasi' : 'Impor') . ": {$r['questions']} soal, " . count($r['errors']) . ' error, ' . count($r['warnings']) . " peringatan\n";
exit($r['errors'] ? 1 : 0);
