# PerumahanInggris

Website latihan tes kemampuan bahasa Inggris format PBT (Listening, Structure & Written Expression, Reading) dengan kurikulum 14 hari: 4 **Blok**, 14 **Rumah**, 2 simulasi penuh, pembahasan dua bahasa, rapor PDF, dan panel admin. Spesifikasi lengkap: [`PRD.md`](PRD.md).

**Stack:** PHP 8.2+ native, MySQL/MariaDB (PDO), HTML/CSS/JS vanilla (ES modules), Dompdf. Tanpa framework dan tanpa build step; berjalan di shared hosting Hostinger.

## Mulai cepat (lokal)

```bash
cp app/config.example.php app/config.php      # isi env=development, base_url, DB, ADMIN_INITIAL_PASSWORD
php app/database/seed.php                      # tabel + konten
php app/database/create_admin.php              # akun admin MiminEPrT2026
php -S localhost:8000 -t public_html public_html/index.php
```

Deploy ke hosting: lihat [`DEPLOY.md`](DEPLOY.md). Setiap push ke branch `claude/vibrant-curie-qer2sp` otomatis di-deploy ke Hostinger (`.github/workflows/deploy.yml`). Format konten soal: [`app/database/seed/README.md`](app/database/seed/README.md). Audio: `tools/make_audio.py`.

## Struktur

```
app/                  kode (di luar public_html)
  bootstrap.php       config, session, error handler, autoload
  helpers.php         db(), e(), t(), csrf, auth, view, rich()
  routes.php          daftar rute
  controllers/        Home, Auth, Account, Practice, Api, Report, Admin, Install
  models/             Question, Rumah, RuleTag, Sponsor, Speaker
  services/           Auth, Practice (mesin latihan), ScoreEstimator, PdfReport, ContentImporter, Installer, …
  views/              layout, landing, auth, practice, report, admin, partials
  lang/               id.php, en.php
  database/           schema.sql, seed.php, create_admin.php, seed/*.json
  data/               common-passwords.txt
  storage/            logs, sessions, pdf-cache, fonts (PDF)
  vendor/             Dompdf
public_html/          index.php, .htaccess, assets/, audio/
tools/                make_audio.py (Edge TTS + ffmpeg), voices.json
```

## Status konten

Konten lengkap: **Latihan Contoh** (10 soal), **Hari 1-14** (Level A-D, masing-masing 30 soal + materi; Hari 14 adalah Hari Review Adaptif yang menyusun 30 soal dari Buku Kesalahan, dengan bank 30 soal Level D sebagai cadangan), **Simulasi 1 dan 2** (masing-masing 50 Listening / 40 Structure / 50 Reading), **Kamus** (49 istilah), 48 tag aturan. Total 710 soal. Audio MP3 dibuat otomatis di server setelah deploy (`deploy/audio.sh` → `tools/make_audio.py`).

## Pemeriksaan tanda kutip

Teks yang dilihat pengguna tidak boleh memakai tanda kutip (" ' “ ” ‘ ’) untuk mengutip atau menandai kata; pakai **tebal**. Apostrof di dalam kata (I'm, didn't) dan teks soal Inggris dikecualikan.

```bash
php tests/cek_teks.php
```

```bash
BASE=http://127.0.0.1:8000 MEMBER=user:pass ADMIN=user:pass node tests/cek_teks_tampil.js
```

Keduanya dijalankan otomatis oleh job `cek-kutip` di `.github/workflows/deploy.yml`; deploy hanya berjalan jika job ini lulus.
