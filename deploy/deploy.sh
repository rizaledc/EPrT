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
set -euo pipefail

BRANCH="${1:?Pemakaian: deploy.sh <branch>}"
APP_DIR="${APP_DIR:-$HOME/apps/perumahaninggris}"
WEB_DIR="${WEB_DIR:-$HOME/domains/perumahaninggris.my.id/public_html}"

cd "$APP_DIR"
echo "==> Kode: $BRANCH → $APP_DIR"
git fetch --prune origin "$BRANCH"
git reset --hard "origin/$BRANCH"
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

echo "==> Selesai"
