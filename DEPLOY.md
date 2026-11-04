# Panduan Deploy ke Hostinger

Setiap push ke branch `claude/vibrant-curie-qer2sp` otomatis di-deploy ke **https://perumahaninggris.my.id** lewat GitHub Actions (`.github/workflows/deploy.yml`).

## Cara kerjanya

```
~/apps/perumahaninggris/                ← clone repo (di LUAR folder domain)
├── app/                                  kode, vendor Dompdf
│   ├── config.php                        dibuat sekali, tidak di-git, tidak pernah tertimpa
│   ├── installed.lock                    tidak di-git
│   └── storage/                          logs, sessions, pdf-cache (isinya tidak di-git)
├── deploy/deploy.sh                      skrip deploy di server
└── public_html/                          sumber yang disalin (rsync) ke folder domain

~/domains/perumahaninggris.my.id/
├── DO_NOT_UPLOAD_HERE                    penanda Hostinger, tidak disentuh
└── public_html/                          ← disajikan web server
    ├── index.php, .htaccess, assets/     disalin dari repo setiap deploy (rsync --delete)
    ├── app-path.php                      dibuat sekali, menunjuk ke ~/apps/…/app (tidak di-rsync)
    ├── audio/                            MP3: dilindungi dari rsync --delete
    └── assets/img/sponsors/              logo upload admin: dilindungi
```

Urutan setiap deploy (`deploy/deploy.sh`):

1. `git fetch` + `git reset --hard origin/<branch>` di `~/apps/perumahaninggris`. File yang di-`.gitignore` (`config.php`, `installed.lock`, isi `storage/`) tidak disentuh `reset --hard`.
2. `rsync -a --delete public_html/ ~/domains/perumahaninggris.my.id/public_html/`, dengan `audio/`, `assets/img/sponsors/`, `app-path.php`, dan `.well-known/` dilindungi dari penghapusan.
3. `php app/database/seed.php`, yang aman dijalankan berulang:
   - Tabel memakai `CREATE TABLE IF NOT EXISTS`; pengaturan dan Blok memakai `INSERT IGNORE`; sponsor bawaan hanya dimasukkan sekali.
   - Soal di-upsert per (Rumah, seksi, nomor), jadi ID soal tetap dan jawaban, progres, serta Buku Kesalahan pengguna tidak tersentuh. Tidak ada `DELETE`.
   - File JSON yang tidak berubah sejak impor terakhir dilewati, sehingga edit dari panel admin tidak tertimpa. Jika sebuah file JSON diubah di repo, isi file itu yang berlaku untuk soal-soal di dalamnya.
   - `php app/database/seed.php --force` mengimpor ulang semuanya bila diperlukan.

Sebelum SSH, workflow memeriksa sintaks semua file PHP dan JSON. Jika gagal, server tidak disentuh.

## Setup awal (sekali jalan lewat SSH)

Ganti `u123456789` dengan username Hostinger Anda (lihat `echo $HOME`).

### 1. Beri server akses baca ke repo (repo privat)

```bash
ssh-keygen -t ed25519 -N "" -C "hostinger-deploy" -f ~/.ssh/github_deploy
cat ~/.ssh/github_deploy.pub
```

Salin hasilnya ke GitHub: repo **rizaledc/EPrT** → Settings → Deploy keys → Add deploy key (biarkan *Allow write access* tidak dicentang). Lalu:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
  IdentityFile ~/.ssh/github_deploy
  IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
```

```bash
ssh -T git@github.com
```

Jawab `yes` saat ditanya fingerprint. Pesan "successfully authenticated" berarti berhasil. Jika repo publik, langkah ini boleh dilewati dan clone memakai URL `https://github.com/rizaledc/EPrT.git`.

### 2. Clone repo di luar folder domain

```bash
mkdir -p ~/apps
git clone --branch claude/vibrant-curie-qer2sp git@github.com:rizaledc/EPrT.git ~/apps/perumahaninggris
```

### 3. Buat config dan penunjuk lokasi app

```bash
cp ~/apps/perumahaninggris/app/config.example.php ~/apps/perumahaninggris/app/config.php
chmod 600 ~/apps/perumahaninggris/app/config.php
```

```bash
printf "<?php\nreturn '%s/apps/perumahaninggris/app';\n" "$HOME" > ~/domains/perumahaninggris.my.id/public_html/app-path.php
```

```bash
rm -f ~/domains/perumahaninggris.my.id/public_html/default.php
```

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Hasil perintah terakhir dipakai untuk `app_secret`. Lalu edit config (`nano ~/apps/perumahaninggris/app/config.php`, atau lewat File Manager):

| Kunci | Isi |
| --- | --- |
| `env` | `production` |
| `base_url` | `https://perumahaninggris.my.id` |
| `app_secret` | string 64 karakter dari perintah di atas |
| `db` → `host` | `localhost` |
| `db` → `name`, `user`, `pass` | dari hPanel → Databases → MySQL Databases (buat dulu jika belum) |
| `ADMIN_INITIAL_PASSWORD` | password admin awal yang kuat (min. 10 karakter, huruf besar, huruf kecil, angka, simbol). Dikosongkan lagi setelah langkah 4 |
| `hsts` | `false` dulu; `true` setelah https berjalan normal |
| `noindex` | `true` selama belum rilis; `false` saat siap diindeks |
| `public_path` | `/home/u123456789/domains/perumahaninggris.my.id/public_html` |
| `timezone`, `admin_username` | biarkan (`Asia/Jakarta`, `MiminEPrT2026`) |

### 4. Deploy pertama + akun admin

```bash
bash ~/apps/perumahaninggris/deploy/deploy.sh claude/vibrant-curie-qer2sp
```

```bash
php ~/apps/perumahaninggris/app/database/create_admin.php
```

Setelah muncul "Akun admin … berhasil dibuat", kosongkan lagi `ADMIN_INITIAL_PASSWORD` di `config.php` (`''`).

### 5. Periksa

- PHP web di hPanel → Advanced → PHP Configuration minimal **8.2** (CLI di server sudah 8.3).
- hPanel → Security → SSL: pastikan SSL aktif. `.htaccess` mengalihkan http ke https.
- Buka https://perumahaninggris.my.id dan https://perumahaninggris.my.id/masuk (login `MiminEPrT2026`).
- `https://perumahaninggris.my.id/robots.txt` berisi `Disallow: /` selama `noindex` = `true`.

### 6. Aktifkan GitHub Actions

Secret `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_KEY` sudah ada. Pastikan public key pasangan `SSH_KEY` ada di `~/.ssh/authorized_keys` server (hPanel → Advanced → SSH Access → SSH keys). Setelah itu setiap push ke branch ini menjalankan deploy; deploy manual tersedia di tab Actions → *Deploy ke Hostinger* → *Run workflow*.

## Audio Listening

Hosting tidak menjalankan TTS. Di komputer sendiri:

```bash
pip install -r tools/requirements.txt
python tools/make_audio.py
```

Hasilnya: MP3 di `public_html/audio/` dan `app/database/seed/audio-manifest.json`. **Commit dan push `audio-manifest.json`** (seed mengimpor durasinya). MP3 tidak di-commit; upload folder `public_html/audio/` ke `~/domains/perumahaninggris.my.id/public_html/audio/` lewat File Manager atau:

```bash
rsync -av public_html/audio/ u123456789@HOST:domains/perumahaninggris.my.id/public_html/audio/
```

Folder ini dilindungi dari `rsync --delete` saat deploy. Selama MP3 belum ada, pemutar memakai suara browser dan soal ditandai `audio_missing` di admin. MP3 juga bisa diunggah satu per satu dari Admin → Grup & Teks.

## Menambah konten

```bash
php app/database/seed.php --validate --only r02
```

Commit `app/database/seed/r02.json`, push, dan deploy otomatis mengimpornya. Format: `app/database/seed/README.md`.

## Setelah rilis

- `noindex` → `false` di `config.php` (di server, tidak lewat git).
- `hsts` → `true`.
- Admin → Sponsor: ganti deskripsi sementara, upload logo.
- hPanel → Files → Backups: aktifkan backup harian. Admin → Impor/Ekspor juga menyediakan ekspor SQL.

## Uji di komputer sendiri

```bash
cp app/config.example.php app/config.php
```

Isi `env` = `development`, `base_url` = `http://localhost:8000`, data DB lokal, `noindex` = `false`, lalu:

```bash
php app/database/seed.php && php app/database/create_admin.php
```

```bash
php -S localhost:8000 -t public_html public_html/index.php
```

## Pemecahan masalah

| Gejala | Solusi |
| --- | --- |
| Workflow gagal "app/config.php belum ada" | Setup awal langkah 3 belum dijalankan |
| Workflow gagal `Permission denied (publickey)` saat `git fetch` | Deploy key (langkah 1) belum ditambahkan di GitHub |
| Workflow gagal di langkah SSH | Public key pasangan `SSH_KEY` belum ada di `authorized_keys`, atau `SSH_PORT` salah (Hostinger biasanya 65002) |
| `rsync: command not found` | Hubungi support Hostinger; sementara deploy manual lewat File Manager |
| Situs menampilkan "Folder app/ tidak ditemukan" | `public_html/app-path.php` belum ada atau path di dalamnya salah |
| Error 500 | Lihat `~/apps/perumahaninggris/app/storage/logs/app-YYYY-MM-DD.log` |
| Seed melaporkan "tidak valid" | File JSON itu tidak diimpor; perbaiki lalu push lagi (`seed.php --validate --only <slug>`) |
| "Terlalu banyak percobaan" saat login | Tunggu 15 menit, atau kosongkan tabel `login_attempts` di phpMyAdmin |
