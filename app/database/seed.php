<?php
declare(strict_types=1);

/**
 * Impor konten JSON dari app/database/seed ke database.
 *
 *   php app/database/seed.php              # buat tabel (bila belum) + impor semua
 *   php app/database/seed.php --validate   # hanya validasi, tidak menulis DB
 *   php app/database/seed.php --only r01   # hanya satu Rumah
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
$r = $imp->importDirectory(__DIR__ . '/seed', $validate, $only);

foreach ($r['warnings'] as $w) {
    echo "  ! $w\n";
}
foreach ($r['errors'] as $e) {
    echo "  ✕ $e\n";
}
echo ($validate ? 'Validasi' : 'Impor') . ": {$r['questions']} soal, " . count($r['errors']) . ' error, ' . count($r['warnings']) . " peringatan\n";
exit($r['errors'] ? 1 : 0);
