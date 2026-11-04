#!/usr/bin/env bash
# Deploy PerumahanInggris di server Hostinger (dijalankan oleh GitHub Actions lewat SSH,
# atau manual: bash ~/apps/perumahaninggris/deploy/deploy.sh <branch>).
#
#   APP_DIR : clone repo (berisi app/, config.php, storage/) — di luar folder domain
#   WEB_DIR : public_html domain yang disajikan web server
#
# Tidak pernah menyentuh: app/config.php, app/installed.lock, isi app/storage/ (semuanya
# di-.gitignore dan git reset --hard tidak menghapus file untracked), serta di WEB_DIR:
# audio/ (MP3), assets/img/sponsors/ (logo), app-path.php, .well-known/.
# MP3 di APP_DIR/public_html/audio, tools/.cache, dan app/database/seed/audio-manifest.json juga
# di-.gitignore; manifest dicadangkan sebelum git reset dan dikembalikan sesudahnya.
set -euo pipefail

BRANCH="${1:?Pemakaian: deploy.sh <branch>}"
APP_DIR="${APP_DIR:-$HOME/apps/perumahaninggris}"
WEB_DIR="${WEB_DIR:-$HOME/domains/perumahaninggris.my.id/public_html}"

cd "$APP_DIR"
MANIFEST="app/database/seed/audio-manifest.json"
MANIFEST_BAK="app/storage/audio-manifest.backup.json"
mkdir -p app/storage
# Salinan resmi ditulis deploy/audio.sh setiap selesai; di sini hanya dibuat bila belum ada
if [ -s "$MANIFEST" ] && [ ! -s "$MANIFEST_BAK" ]; then cp -p "$MANIFEST" "$MANIFEST_BAK"; fi

echo "==> Kode: $BRANCH → $APP_DIR"
git fetch --prune origin "$BRANCH"
git reset --hard "origin/$BRANCH"
# Manifest audio di server harus tetap milik server (MP3 dibuat dari manifest itu). Normalnya file
# ini di-.gitignore sehingga reset tidak menyentuhnya; cadangan hanya dipakai bila file hilang atau
# ternyata ikut ter-commit dan tertimpa versi repo.
if [ -s "$MANIFEST_BAK" ] && { [ ! -s "$MANIFEST" ] || git ls-files --error-unmatch "$MANIFEST" >/dev/null 2>&1; }; then
  cp -p "$MANIFEST_BAK" "$MANIFEST"
  echo "    audio-manifest.json dipulihkan dari cadangan server"
fi
echo "    commit $(git rev-parse --short HEAD): $(git log -1 --pretty=%s)"

if [ ! -f app/config.php ]; then
  echo "!! app/config.php belum ada di $APP_DIR/app. Jalankan setup awal (DEPLOY.md)." >&2
  exit 1
fi
mkdir -p app/storage/logs app/storage/sessions app/storage/pdf-cache

echo "==> public_html → $WEB_DIR"
# --filter 'P …' = lindungi dari --delete. File repo di audio/ (.htaccess) tetap disalin.
rsync -a --delete \
  --filter='P /audio/***' \
  --filter='P /assets/img/sponsors/***' \
  --filter='P /app-path.php' \
  --filter='P /.well-known/***' \
  --exclude='.gitkeep' \
  public_html/ "$WEB_DIR/"

if [ ! -f "$WEB_DIR/app-path.php" ]; then
  echo "!! $WEB_DIR/app-path.php belum ada. Jalankan setup awal (DEPLOY.md)." >&2
  exit 1
fi

echo "==> Database & konten (aman diulang)"
php app/database/seed.php

echo "==> Audio Listening (latar belakang)"
AUDIO_LOG="app/storage/logs/audio-$(date +%F).log"
if bash deploy/audio.sh --check; then
  # setsid + redirect semua fd: sesi SSH GitHub Actions selesai tanpa menunggu proses ini
  if command -v setsid >/dev/null 2>&1; then
    APP_DIR="$APP_DIR" WEB_DIR="$WEB_DIR" setsid nohup bash deploy/audio.sh >> "$AUDIO_LOG" 2>&1 < /dev/null &
  else
    APP_DIR="$APP_DIR" WEB_DIR="$WEB_DIR" nohup bash deploy/audio.sh >> "$AUDIO_LOG" 2>&1 < /dev/null &
  fi
  echo "    berjalan di latar belakang, log: $APP_DIR/$AUDIO_LOG"
fi

echo "==> Selesai"
