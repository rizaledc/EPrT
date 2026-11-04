#!/usr/bin/env bash
# Membuat MP3 Listening langsung di server, lalu menyalinnya ke folder web dan mengimpor durasinya.
# Dipanggil deploy.sh di latar belakang setelah seed; bisa juga dijalankan manual:
#   bash ~/apps/perumahaninggris/deploy/audio.sh            # jalankan (dengan lock)
#   bash ~/apps/perumahaninggris/deploy/audio.sh --check    # hanya cek python3, edge_tts, ffmpeg
#
# Hanya naskah yang berubah yang dibuat ulang (cache hash di tools/.cache dan audio-manifest.json).
# MP3 (public_html/audio/**/*.mp3), tools/.cache, dan audio-manifest.json di-.gitignore, jadi
# git reset --hard tidak menghapusnya; rsync ke folder web memakai tanpa --delete.
set -uo pipefail

APP_DIR="${APP_DIR:-$HOME/apps/perumahaninggris}"
WEB_DIR="${WEB_DIR:-$HOME/domains/perumahaninggris.my.id/public_html}"
export PATH="$HOME/bin:$HOME/.local/bin:$PATH"
PY="${PYTHON:-python3}"

check_deps() {
  local missing=()
  command -v "$PY" >/dev/null 2>&1 || missing+=("$PY")
  if command -v "$PY" >/dev/null 2>&1 && ! "$PY" -c 'import edge_tts' >/dev/null 2>&1; then missing+=("edge_tts (modul Python)"); fi
  command -v ffmpeg >/dev/null 2>&1 || missing+=("ffmpeg")
  command -v ffprobe >/dev/null 2>&1 || missing+=("ffprobe")
  if [ "${#missing[@]}" -gt 0 ]; then
    echo "!! Audio dilewati: tidak ditemukan ${missing[*]} (lihat DEPLOY.md bagian Audio). Deploy tetap lanjut."
    return 1
  fi
  return 0
}

cd "$APP_DIR" || exit 0
if [ "${1:-}" = "--check" ]; then
  check_deps
  exit $?
fi
check_deps || exit 0

# Lock: cegah dua proses audio berjalan bersamaan (mkdir bersifat atomik; lock basi dibersihkan)
LOCK_DIR="$APP_DIR/app/storage/audio.lock"
if ! mkdir "$LOCK_DIR" 2>/dev/null; then
  old_pid="$(cat "$LOCK_DIR/pid" 2>/dev/null || true)"
  if [ -n "$old_pid" ] && kill -0 "$old_pid" 2>/dev/null; then
    echo "== $(date '+%F %T') Proses audio lain masih berjalan (pid $old_pid). Lewati."
    exit 0
  fi
  echo "== $(date '+%F %T') Lock basi dibersihkan (pid ${old_pid:-?} sudah tidak ada)."
  rm -rf "$LOCK_DIR"
  mkdir "$LOCK_DIR" || exit 0
fi
echo $$ > "$LOCK_DIR/pid"
trap 'rm -rf "$LOCK_DIR"' EXIT

echo "== $(date '+%F %T') Mulai membuat audio ($("$PY" --version 2>&1))"
"$PY" -u tools/make_audio.py
rc=$?
echo "== $(date '+%F %T') make_audio.py selesai (kode $rc; kode 1 = sebagian file gagal, dicoba lagi pada deploy berikutnya)"

# Salinan resmi manifest di luar jalur git; deploy.sh mengembalikannya setelah git reset
if [ -s app/database/seed/audio-manifest.json ]; then
  cp -p app/database/seed/audio-manifest.json app/storage/audio-manifest.backup.json
fi

if [ -d public_html/audio ]; then
  echo "== Salin audio → $WEB_DIR/audio/"
  mkdir -p "$WEB_DIR/audio"
  rsync -a public_html/audio/ "$WEB_DIR/audio/"
fi

echo "== Seed ulang agar durasi dari audio-manifest.json masuk"
php app/database/seed.php
echo "== $(date '+%F %T') Audio selesai"
