# Panduan Deploy ke Hostinger

Panduan ini membawa PerumahanInggris dari repo ini ke hosting Hostinger (shared/cloud, PHP 8.2+, MySQL/MariaDB). Tidak ada build step dan tidak perlu Node.

## 0. Yang perlu disiapkan

- Domain yang sudah terhubung ke Hostinger.
- Akses hPanel.
- Password admin awal (kuat: minimal 10 karakter, huruf besar, huruf kecil, angka, simbol).
- (Opsional, untuk audio) komputer dengan Python 3.10+ dan ffmpeg.

## 1. Struktur di server

Di Hostinger, setiap domain punya folder `domains/<domain>/`. Kode aplikasi diletakkan **di luar** `public_html` supaya tidak bisa diakses dari internet:

```
domains/<domain>/
├── app/            ← dari repo (kode, konfigurasi, vendor Dompdf)
├── tools/          ← opsional (skrip audio, tidak dipakai server)
└── public_html/    ← dari repo (index.php, .htaccess, assets, audio)
```

`public_html/index.php` memuat `../app/bootstrap.php`. Document root domain tetap `public_html` (bawaan Hostinger), jadi tidak perlu diubah.

## 2. Upload file

**Pilihan A, Git (disarankan).** hPanel → Advanced → Git → isi URL repo dan branch, lalu deploy ke folder `domains/<domain>` (bukan `public_html`). Jika hPanel hanya mengizinkan deploy ke `public_html`, gunakan pilihan B.

**Pilihan B, File Manager / FTP.**
1. Unduh repo sebagai ZIP.
2. Upload isi folder `app/` ke `domains/<domain>/app/`.
3. Upload isi folder `public_html/` ke `domains/<domain>/public_html/` (timpa `default.php` bawaan jika ada; hapus `default.php`).
4. Pastikan file tersembunyi `.htaccess` ikut ter-upload (`public_html/.htaccess`, `public_html/audio/.htaccess`, `app/.htaccess`).

Folder `app/vendor/` (Dompdf) sudah ada di repo, jadi tidak perlu menjalankan Composer.

## 3. Buat database

hPanel → Databases → **MySQL Databases** → buat database baru, user, dan password. Catat ketiganya (contoh: `u123456789_perumahan`).

## 4. Isi `app/config.php`

Salin `app/config.example.php` menjadi `app/config.php` (lewat File Manager: klik kanan → Copy, lalu Rename), kemudian isi:

| Kunci | Isi |
| --- | --- |
| `env` | `production` |
| `base_url` | `https://domainanda.com` (tanpa `/` di akhir) |
| `app_secret` | string acak 64 karakter. Buat di terminal: `php -r "echo bin2hex(random_bytes(32));"` atau generator password apa pun |
| `db.name`, `db.user`, `db.pass` | dari langkah 3. `db.host` biasanya `localhost` |
| `ADMIN_INITIAL_PASSWORD` | password admin awal (sementara) |
| `hsts` | biarkan `false` dulu; ubah ke `true` setelah langkah 7 berhasil |

`app/config.php` tidak pernah di-commit (sudah ada di `.gitignore`).

## 5. Buat tabel, impor konten, buat admin

**Dengan SSH** (hPanel → Advanced → SSH Access):

```bash
cd ~/domains/<domain>
php app/database/seed.php          # membuat tabel (schema.sql) + mengimpor soal, Kamus, sponsor
php app/database/create_admin.php  # membuat akun admin MiminEPrT2026 dari config
```

**Tanpa SSH:** buka `https://domainanda.com/install`, ketik ulang `ADMIN_INITIAL_PASSWORD`, lalu klik **Jalankan instalasi**. Halaman ini menjalankan ketiga langkah di atas lalu mengunci dirinya sendiri (`app/installed.lock`).

Alternatif manual: impor `app/database/schema.sql` lewat phpMyAdmin, lalu tetap jalankan `/install` untuk konten dan admin.

**Setelah berhasil: hapus nilai `ADMIN_INITIAL_PASSWORD` dari `app/config.php`** (biarkan string kosong `''`).

## 6. Izin folder

Folder berikut harus bisa ditulis oleh PHP (biasanya sudah otomatis di Hostinger; jika tidak, set 755):

- `app/storage/logs`, `app/storage/sessions`, `app/storage/pdf-cache`
- `public_html/audio` (untuk upload MP3 dari admin)
- `public_html/assets/img/sponsors` (dibuat otomatis saat upload logo)

## 7. Aktifkan SSL

hPanel → Security → **SSL** → pasang SSL gratis untuk domain. Setelah `https://` berfungsi:

1. `.htaccess` sudah mengalihkan semua `http` ke `https` secara otomatis.
2. Ubah `'hsts' => true` di `app/config.php`.

## 8. Audio Listening

Hosting tidak menjalankan TTS. Di komputer sendiri:

```bash
pip install -r tools/requirements.txt
python tools/make_audio.py           # membuat MP3 di public_html/audio + app/database/seed/audio-manifest.json
```

Lalu upload folder `public_html/audio/` dan file `app/database/seed/audio-manifest.json` ke server, dan jalankan ulang `php app/database/seed.php` (atau `/admin/impor`). Selama MP3 belum ada, pemutar memakai **suara browser** sebagai cadangan (tetap sekali putar di Simulasi) dan soal ditandai `audio_missing` di dashboard admin. Admin juga bisa mengganti MP3 satu per satu di **Admin → Grup & Teks**.

## 9. Setelah live

- Login di `/masuk` dengan `MiminEPrT2026`, lalu buka `/admin`.
- **Admin → Sponsor:** ganti deskripsi sementara orbitani.com dan kodinginaja.biz.id, upload logo bila ada.
- **Admin → Pengaturan:** cek tabel konversi skor, target default, dan pesan pengumuman.
- hPanel → Files → **Backups:** aktifkan backup harian. Admin → Impor/Ekspor juga menyediakan **Ekspor semua data (SQL)**.
- `display_errors` sudah mati di mode `production`; error dicatat di `app/storage/logs/`.

## 10. Menambah konten

Setiap Rumah adalah satu file `app/database/seed/<slug>.json` (format: `app/database/seed/README.md`). Alur:

```bash
php app/database/seed.php --validate --only r02   # cek dulu
php app/database/seed.php --only r02              # impor
python tools/make_audio.py --only r02             # audio
```

Atau tempel/unggah JSON di **Admin → Impor / Ekspor** (selalu divalidasi sebelum disimpan).

## Uji di komputer sendiri

```bash
cp app/config.example.php app/config.php   # isi env=development, base_url=http://localhost:8000, data DB lokal
php app/database/seed.php && php app/database/create_admin.php
php -S localhost:8000 -t public_html public_html/index.php
```

Argumen terakhir (`public_html/index.php`) diperlukan agar `/robots.txt`, `/sitemap.xml`, dan `/rapor/a.pdf` ikut diarahkan ke router.

## Pemecahan masalah

| Gejala | Solusi |
| --- | --- |
| Halaman "Konfigurasi belum lengkap" | `app/config.php` belum dibuat |
| Error 500 putih | Cek `app/storage/logs/app-YYYY-MM-DD.log`; sementara ubah `env` ke `development` untuk melihat detail |
| Semua halaman selain beranda 404 | `.htaccess` tidak ter-upload atau mod_rewrite mati |
| Font PDF tidak muncul | Pastikan `app/storage/fonts/` ter-upload dan `app/storage/pdf-cache` bisa ditulis |
| "Terlalu banyak percobaan" saat login | Tunggu 15 menit, atau hapus isi tabel `login_attempts` lewat phpMyAdmin |
