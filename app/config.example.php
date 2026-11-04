<?php
/**
 * Salin file ini menjadi app/config.php lalu isi nilainya.
 * app/config.php TIDAK boleh di-commit (sudah ada di .gitignore).
 */
return [
    // Lingkungan: 'production' (Hostinger) atau 'development' (localhost)
    'env' => 'production',

    // URL dasar website tanpa garis miring di akhir, mis. https://perumahaninggris.com
    'base_url' => 'https://domain-anda.com',

    // String acak panjang (min. 32 karakter). Buat dengan: php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => 'GANTI_DENGAN_STRING_ACAK_64_KARAKTER',

    // Database dari hPanel → Databases → MySQL Databases
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'u123456789_perumahan',
        'user'    => 'u123456789_perumahan',
        'pass'    => 'PASSWORD_DATABASE',
        'charset' => 'utf8mb4',
    ],

    // Zona waktu tampilan
    'timezone' => 'Asia/Jakarta',

    // Akun admin awal. Username ditetapkan PRD.
    'admin_username' => 'MiminEPrT2026',
    // Isi sendiri sebelum menjalankan create_admin.php atau /install,
    // lalu KOSONGKAN lagi setelah instalasi selesai.
    'ADMIN_INITIAL_PASSWORD' => '',

    // Aktifkan HSTS setelah SSL Hostinger aktif dan website sudah bisa dibuka lewat https
    'hsts' => false,

    // Folder publik (dipakai untuk upload audio & logo). Default: ../public_html dari folder app.
    'public_path' => null,
];
