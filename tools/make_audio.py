#!/usr/bin/env python3
"""
make_audio.py — membuat MP3 Listening dari naskah di app/database/seed/*.json.

Dijalankan SEKALI di komputer developer (bukan di hosting):
    pip install -r tools/requirements.txt      # edge-tts (dan azure SDK bila perlu)
    python tools/make_audio.py                 # semua Rumah
    python tools/make_audio.py --only r05      # satu Rumah
    python tools/make_audio.py --force         # buat ulang semua (abaikan cache)
    python tools/make_audio.py --dry-run       # tampilkan rencana tanpa membuat file

Hasil:
    public_html/audio/<slug>/L-<slug>-gNN-talk.mp3   (percakapan / lecture)
    public_html/audio/<slug>/L-<slug>-gNN-qK.mp3     (pertanyaan narator)
    public_html/audio/common/dir-part-a|b|c.mp3     (directions)
    public_html/audio/common/num-01..50.mp3         (nomor soal simulasi)
    app/database/seed/audio-manifest.json            (path, durasi ms, hash) → diimpor seed.php

Format: MP3 mono 24 kHz 64 kbps, loudness -16 LUFS. Jeda: 300 ms awal file,
450 ms antar-giliran. Jeda 900 ms sebelum pertanyaan diatur oleh pemutar di browser.

Cadangan: set TTS_PROVIDER=azure (+ AZURE_SPEECH_KEY, AZURE_SPEECH_REGION) bila Edge TTS
berhenti bekerja. Voice yang dipakai sama.
"""
import argparse
import asyncio
import hashlib
import json
import os
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SEED = ROOT / "app" / "database" / "seed"
OUT = ROOT / "public_html"
CACHE = Path(__file__).resolve().parent / ".cache"
VOICES = json.loads((Path(__file__).resolve().parent / "voices.json").read_text(encoding="utf-8"))
TTS_PROVIDER = os.environ.get("TTS_PROVIDER", "edge").lower()

GAP_TURN_MS = 450
LEAD_MS = 300
NUMBERS = ["one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twelve", "thirteen",
           "fourteen", "fifteen", "sixteen", "seventeen", "eighteen", "nineteen", "twenty"]


def number_words(n: int) -> str:
    if n <= 20:
        return NUMBERS[n - 1]
    tens = ["", "", "twenty", "thirty", "forty", "fifty"][n // 10]
    return tens if n % 10 == 0 else f"{tens}-{NUMBERS[n % 10 - 1]}"


def voice_for(speaker: str, slug: str) -> str:
    """Petakan kode speaker ke voice. Pembicara bergiliran agar telinga terbiasa banyak suara."""
    if speaker == "N":
        return VOICES["narrator"]
    if speaker.startswith("LEC"):
        pool = VOICES["lecturer"]
        return pool[int(hashlib.md5(slug.encode()).hexdigest(), 16) % len(pool)]
    gender = "male" if speaker[0] == "M" else "female"
    idx = int(speaker[1:] or "1") - 1
    pool = VOICES[gender]
    # Rumah dengan aksen UK lebih banyak (mis. r13) memakai daftar khusus
    if slug in VOICES.get("uk_heavy_rumah", []):
        pool = VOICES.get(f"{gender}_uk_heavy", pool)
    offset = int(hashlib.md5(slug.encode()).hexdigest(), 16)
    return pool[(idx + offset) % len(pool)]


async def synth_edge(text: str, voice: str, dest: Path) -> None:
    import edge_tts  # noqa: WPS433
    comm = edge_tts.Communicate(text, voice, rate="+0%")
    await comm.save(str(dest))


def synth_azure(text: str, voice: str, dest: Path) -> None:
    import azure.cognitiveservices.speech as speechsdk  # noqa: WPS433
    cfg = speechsdk.SpeechConfig(subscription=os.environ["AZURE_SPEECH_KEY"], region=os.environ["AZURE_SPEECH_REGION"])
    cfg.speech_synthesis_voice_name = voice
    cfg.set_speech_synthesis_output_format(speechsdk.SpeechSynthesisOutputFormat.Audio24Khz48KBitRateMonoMp3)
    synth = speechsdk.SpeechSynthesizer(speech_config=cfg, audio_config=speechsdk.audio.AudioOutputConfig(filename=str(dest)))
    res = synth.speak_text_async(text).get()
    if res.reason != speechsdk.ResultReason.SynthesizingAudioCompleted:
        raise RuntimeError(f"Azure TTS gagal: {res.reason}")


async def tts_piece(text: str, voice: str) -> Path:
    """Satu potongan MP3 per giliran, dengan cache berdasarkan hash (teks + voice)."""
    key = hashlib.sha256(f"{voice}|{text}".encode()).hexdigest()[:24]
    CACHE.mkdir(exist_ok=True)
    dest = CACHE / f"{key}.mp3"
    if dest.exists() and dest.stat().st_size > 0:
        return dest
    for attempt in range(4):
        try:
            if TTS_PROVIDER == "azure":
                synth_azure(text, voice, dest)
            else:
                await synth_edge(text, voice, dest)
            return dest
        except Exception as exc:  # noqa: BLE001
            if attempt == 3:
                raise
            print(f"   ulang ({exc})", file=sys.stderr)
            await asyncio.sleep(2 ** attempt)
    return dest


def ffmpeg_concat(pieces: list, dest: Path) -> int:
    """Gabungkan potongan + jeda, normalisasi -16 LUFS, MP3 mono 24 kHz 64 kbps. Mengembalikan durasi ms."""
    dest.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory() as tmp:
        inputs = []
        filters = []
        for i, (path, gap_after) in enumerate(pieces):
            inputs += ["-i", str(path)]
            filters.append(f"[{i}:a]aresample=24000,aformat=channel_layouts=mono,apad=pad_dur={gap_after / 1000:.3f}[a{i}]")
        concat = "".join(f"[a{i}]" for i in range(len(pieces)))
        graph = ";".join(filters) + f";{concat}concat=n={len(pieces)}:v=0:a=1[c];[c]adelay={LEAD_MS}|{LEAD_MS},loudnorm=I=-16:TP=-1.5:LRA=11[out]"
        tmp_out = Path(tmp) / "out.mp3"
        cmd = ["ffmpeg", "-y", "-loglevel", "error", *inputs, "-filter_complex", graph, "-map", "[out]",
               "-ac", "1", "-ar", "24000", "-b:a", "64k", str(tmp_out)]
        subprocess.run(cmd, check=True)
        shutil.move(str(tmp_out), dest)
    probe = subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", str(dest)],
                           capture_output=True, text=True, check=True)
    return int(float(probe.stdout.strip() or 0) * 1000)


def collect_jobs(only: str | None) -> list:
    """Bangun daftar file yang perlu dibuat (path, daftar giliran)."""
    rumahs = json.loads((SEED / "rumahs.json").read_text(encoding="utf-8"))
    directions = json.loads((SEED / "directions.json").read_text(encoding="utf-8"))
    jobs = []
    if not only:
        for part in ("a", "b", "c"):
            jobs.append((f"audio/common/dir-part-{part}.mp3", [("N", directions[f"part_{part}"])], "common"))
        for n in range(1, 51):
            jobs.append((f"audio/common/num-{n:02d}.mp3", [("N", f"Number {number_words(n)}.")], "common"))
    for r in rumahs:
        slug = r["slug"]
        if only and slug != only:
            continue
        f = SEED / f"{slug}.json"
        if not f.exists():
            continue
        data = json.loads(f.read_text(encoding="utf-8"))
        g = 0
        for item in data.get("items", []):
            if item.get("section") != "listening" or "questions" not in item:
                continue
            g += 1
            turns = [(line["speaker"], line["text"]) for line in item.get("script", [])]
            jobs.append((f"audio/{slug}/L-{slug}-g{g:02d}-talk.mp3", turns, slug))
            for k, q in enumerate(item["questions"], start=1):
                jobs.append((f"audio/{slug}/L-{slug}-g{g:02d}-q{k}.mp3", [("N", q["prompt"])], slug))
    return jobs


async def main() -> None:
    ap = argparse.ArgumentParser(description="Buat MP3 Listening dengan Edge TTS")
    ap.add_argument("--only", help="slug Rumah, mis. r05")
    ap.add_argument("--force", action="store_true", help="buat ulang walau tidak berubah")
    ap.add_argument("--dry-run", action="store_true", help="tampilkan rencana saja")
    args = ap.parse_args()

    if not args.dry_run and not shutil.which("ffmpeg"):
        sys.exit("ffmpeg tidak ditemukan. Pasang ffmpeg lalu ulangi.")

    manifest_file = SEED / "audio-manifest.json"
    manifest = {}
    if manifest_file.exists():
        for m in json.loads(manifest_file.read_text(encoding="utf-8")):
            manifest[m["path"]] = m

    jobs = collect_jobs(args.only)
    made = skipped = 0
    for path, turns, slug in jobs:
        voices = [(voice_for(sp, slug), text) for sp, text in turns]
        h = hashlib.sha256(json.dumps(voices, ensure_ascii=False).encode()).hexdigest()
        dest = OUT / path
        if not args.force and dest.exists() and manifest.get(path, {}).get("hash") == h:
            skipped += 1
            continue
        if args.dry_run:
            print(f"[rencana] {path}  ({len(turns)} giliran: {', '.join(v for v, _ in voices)})")
            continue
        print(f"→ {path}")
        pieces = []
        for i, (voice, text) in enumerate(voices):
            piece = await tts_piece(text, voice)
            pieces.append((piece, GAP_TURN_MS if i < len(voices) - 1 else 0))
        ms = ffmpeg_concat(pieces, dest)
        manifest[path] = {"path": path, "duration_ms": ms, "hash": h}
        made += 1

    if not args.dry_run:
        manifest_file.write_text(json.dumps(sorted(manifest.values(), key=lambda m: m["path"]), indent=1), encoding="utf-8")
    print(f"Selesai: {made} dibuat, {skipped} tidak berubah. Manifest: {manifest_file.relative_to(ROOT)}")
    print("Langkah berikut: upload folder public_html/audio ke hosting, lalu jalankan php app/database/seed.php (atau impor ulang).")


if __name__ == "__main__":
    asyncio.run(main())
