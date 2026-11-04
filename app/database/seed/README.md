# Format konten (seed)

| File | Isi |
| --- | --- |
| `rumahs.json` | 14 Rumah + `sim1`, `sim2`, `contoh` (judul ID/EN, blok, urutan) |
| `rule_tags.json` | Daftar tetap tag aturan (≈ 48 kode) |
| `glossary.json` | Kamus Istilah (≥ 40 istilah, 5 grup: `pelaku`, `kelas`, `bagian`, `waktu`, `aturan`) |
| `directions.json` | Teks directions orisinal Part A/B/C (dibacakan narator) |
| `<slug>.json` | Satu file per Rumah: materi + soal |
| `audio-manifest.json` | Dibuat oleh `tools/make_audio.py` (durasi & hash MP3) |

## File Rumah

```json
{
  "rumah": "r02",
  "material_id": "Pembuka...\n\n=== listening\n## Judul\nAturan... [[verb]]...\n> rumus\n✓ contoh benar\n✕ contoh salah\n\n=== structure\n...\n\n=== reading\n...",
  "material_en": "...",
  "items": [ { listening group }, { structure question }, { reading group } ]
}
```

Materi: `=== listening|structure|reading` memisahkan kartu konsep. `[[istilah]]` → komponen istilah (mode ID otomatis menampilkan arti dalam kurung pada kemunculan pertama + tooltip Kamus). `**tebal**`, `*miring*`, `` `kode` ``, `> rumus`, `✓`/`✕` contoh, `- ` daftar, `## ` subjudul. HTML mentah selalu dibuang.

### Structure
```json
{ "section": "structure", "type": "structure_error", "number": 5, "difficulty": 1,
  "prompt": "The professor spoke {A}so{/A} {B}quick{/B} that {C}many{/C} students could not {D}follow{/D} the lecture.",
  "options": ["so", "quick", "many", "follow"], "correct_index": 1, "rule_tags": ["S-ADJ-ADV"],
  "why_correct_id": "...", "why_correct_en": "...",
  "why_wrong_id": {"0": "...", "2": "...", "3": "..."}, "why_wrong_en": {"0": "...", "2": "...", "3": "..."},
  "analogy_id": "...", "analogy_en": "...", "tip_id": "...", "tip_en": "..." }
```
`structure_completion` memakai rumpang `______` (6 garis bawah).

### Listening (grup = satu percakapan/lecture + pertanyaannya)
```json
{ "section": "listening", "part": "A",
  "script": [ {"speaker": "W1", "text": "..."}, {"speaker": "M2", "text": "..."} ],
  "questions": [ { "type": "listening_short", "prompt": "What does the man mean?", "options": [...], "correct_index": 0,
                   "key_sentence": "kalimat persis dari script", "key_translation_id": "...", "liaison_note": "opsional", ...pembahasan } ] }
```
Speaker: `N` narator, `M1–M4` pria, `W1–W4` wanita, `LEC` dosen. Part B = `listening_long` (6–12 giliran), Part C = `listening_lecture` (150–250 kata).

### Reading (grup = satu teks + soal)
```json
{ "section": "reading", "title": "...", "body": "Paragraf 1...\n\nParagraf 2 dengan **kata** yang ditanyakan...",
  "questions": [ { "type": "reading", "subtype": "vocabulary", "prompt": "The word **considerable** in paragraph 4 is closest in meaning to",
                   "evidence_quote": "kalimat persis dari teks", ...pembahasan } ] }
```
Subtype: `main_idea`, `detail`, `vocabulary`, `reference`, `inference`, `not_except`, `purpose`, `organization`. Teks 250–450 kata.

## Validasi

`php app/database/seed.php --validate [--only r02]` menolak soal dengan field pembahasan kosong (ID dan EN), opsi ≠ 4, tag tidak dikenal, prompt error recognition tanpa `{A}…{D}`, soal simulasi yang sama dengan soal Rumah, dll. Peringatan (tidak memblokir): panjang teks, sebaran jawaban A–D di luar 20–30%, `key_sentence`/`evidence_quote` tidak ditemukan persis.

Nomor soal (`number`) unik per Rumah + seksi; bila dikosongkan, diberi otomatis sesuai urutan file. Path audio dibuat otomatis: `audio/<slug>/L-<slug>-gNN-talk.mp3` dan `...-qK.mp3`.
