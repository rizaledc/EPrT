<?php
declare(strict_types=1);

/**
 * Cek ulang pesanan koin yang masih pending ke API Mayar (cadangan bila webhook tidak sampai).
 * Jalankan lewat Cron Job hPanel, misalnya tiap 5 menit:
 *   php ~/apps/perumahaninggris/app/database/reconcile_orders.php
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require __DIR__ . '/../bootstrap.php';

if (!Mayar::configured()) {
    exit(0);
}
$rows = q_all("SELECT * FROM coin_orders WHERE status = 'pending' AND mayar_id IS NOT NULL AND created_at > NOW() - INTERVAL 3 DAY ORDER BY id DESC LIMIT 30");
$paid = 0;
foreach ($rows as $o) {
    if (Payments::reconcile($o)['status'] === 'paid') {
        $paid++;
    }
}
if ($rows) {
    echo date('c') . ' dicek ' . count($rows) . ', lunas ' . $paid . "\n";
}
