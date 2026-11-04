<?php
declare(strict_types=1);

/**
 * Validasi & impor konten JSON (seed dan fitur Impor admin).
 * Format: lihat PRD bagian 16 dan app/database/seed/README.md.
 */
final class ContentImporter
{
    public array $errors = [];
    public array $warnings = [];
    private array $tagCodes = [];
    private array $manifest = [];
    private array $simPrompts = [];
    private array $practicePrompts = [];
    /** true = impor ulang walau file tidak berubah */
    public bool $force = false;
    /** Ringkasan per file: imported | unchanged | invalid */
    public array $report = [];
    private array $hashes = [];

    public const TYPES = [
        'listening' => ['listening_short', 'listening_long', 'listening_lecture'],
        'structure' => ['structure_completion', 'structure_error'],
        'reading'   => ['reading'],
    ];
    public const READING_SUBTYPES = ['main_idea', 'detail', 'vocabulary', 'reference', 'inference', 'not_except', 'purpose', 'organization'];

    /* ------------------------------------------------------------ */

    public function importDirectory(string $dir, bool $validateOnly, ?string $only = null): array
    {
        $this->errors = [];
        $this->warnings = [];
        $count = 0;
        $meta = ['rule_tags.json', 'rumahs.json', 'glossary.json'];
        foreach ($meta as $m) {
            if (!is_file("$dir/$m")) {
                $this->errors[] = "$m tidak ditemukan";
            }
        }
        if ($this->errors) {
            return $this->result(0);
        }
        $ruleTags = $this->readJson("$dir/rule_tags.json");
        $rumahs = $this->readJson("$dir/rumahs.json");
        $glossary = $this->readJson("$dir/glossary.json");
        $this->tagCodes = array_column((array) $ruleTags, 'code');
        if (is_file("$dir/audio-manifest.json")) {
            foreach ((array) $this->readJson("$dir/audio-manifest.json") as $m) {
                if (isset($m['path'])) {
                    $this->manifest[$m['path']] = $m;
                }
            }
        }
        $this->validateMeta((array) $rumahs, (array) $glossary);
        if (!$validateOnly) {
            $this->hashes = json_col(q_val("SELECT value FROM settings WHERE `key` = 'seed_hashes'"), []);
        }
        $metaHash = sha1(implode('|', array_map(fn($f) => (string) @file_get_contents("$dir/$f"), $meta)));
        if (!$validateOnly && !$this->errors) {
            if ($this->force || ($this->hashes['_meta'] ?? '') !== $metaHash) {
                $this->importMeta((array) $ruleTags, (array) $rumahs, (array) $glossary);
                $this->hashes['_meta'] = $metaHash;
                $this->report['rumahs/rule_tags/glossary'] = 'imported';
            } else {
                $this->report['rumahs/rule_tags/glossary'] = 'unchanged';
            }
        }
        $manifestRaw = is_file("$dir/audio-manifest.json") ? (string) file_get_contents("$dir/audio-manifest.json") : '';
        $slugs = array_column((array) $rumahs, 'slug');
        $files = [];
        foreach ($slugs as $slug) {
            if ($only && $slug !== $only) {
                continue;
            }
            if (is_file("$dir/$slug.json")) {
                $files[$slug] = "$dir/$slug.json";
            }
        }
        // Simulasi divalidasi terakhir agar duplikasi dengan Rumah terdeteksi
        uksort($files, fn($a, $b) => (str_starts_with($a, 'sim') <=> str_starts_with($b, 'sim')) ?: strcmp($a, $b));
        foreach ($files as $slug => $file) {
            $data = $this->readJson($file);
            if ($data === null) {
                continue;
            }
            $hash = sha1((string) file_get_contents($file) . '|' . $manifestRaw);
            $skip = !$validateOnly && !$this->force && ($this->hashes[$slug] ?? '') === $hash;
            $errBefore = count($this->errors);
            $count += $this->importRumahData($slug, $data, $validateOnly || $skip, basename($file));
            if (!$validateOnly) {
                if (count($this->errors) > $errBefore) {
                    $this->report[$slug] = 'invalid';
                } elseif ($skip) {
                    $this->report[$slug] = 'unchanged';
                } else {
                    $this->hashes[$slug] = $hash;
                    $this->report[$slug] = 'imported';
                }
            }
        }
        if (!$validateOnly) {
            setting_save('seed_hashes', $this->hashes);
        }
        return $this->result($count);
    }

    public function importJsonString(string $json, bool $validateOnly): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->tagCodes = array_keys(RuleTag::map());
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->errors[] = 'JSON tidak valid: ' . json_last_error_msg();
            return $this->result(0);
        }
        // Bentuk 1: {rumah, items:[...]}; Bentuk 2: daftar soal/grup yang masing-masing punya "rumah"
        $bySlug = [];
        if (isset($data['rumah'])) {
            $bySlug[$data['rumah']] = $data;
        } elseif (array_is_list($data)) {
            foreach ($data as $item) {
                $slug = $item['rumah'] ?? null;
                if (!$slug) {
                    $this->errors[] = 'Setiap item wajib punya field "rumah".';
                    continue;
                }
                $bySlug[$slug]['items'][] = $item;
            }
        } else {
            $this->errors[] = 'Format tidak dikenali.';
        }
        $count = 0;
        foreach ($bySlug as $slug => $d) {
            $count += $this->importRumahData((string) $slug, $d, $validateOnly, 'impor');
        }
        return $this->result($count);
    }

    private function result(int $count): array
    {
        return ['questions' => $count, 'errors' => $this->errors, 'warnings' => $this->warnings];
    }

    private function readJson(string $file)
    {
        $d = json_decode((string) file_get_contents($file), true);
        if ($d === null) {
            $this->errors[] = basename($file) . ': JSON tidak valid (' . json_last_error_msg() . ')';
        }
        return $d;
    }

    /* ------------------------------------------------------------ */

    private function validateMeta(array $rumahs, array $glossary): void
    {
        foreach ($rumahs as $i => $r) {
            foreach (['slug', 'kind', 'title_id', 'title_en'] as $f) {
                if (empty($r[$f])) {
                    $this->errors[] = "rumahs.json #$i: field $f kosong";
                }
            }
        }
        foreach ($glossary as $i => $g) {
            foreach (['slug', 'grp', 'term_en', 'meaning_id', 'def_id', 'def_en'] as $f) {
                if (empty($g[$f])) {
                    $this->errors[] = "glossary.json #$i ({$g['slug']}): field $f kosong";
                }
            }
        }
    }

    private function importMeta(array $ruleTags, array $rumahs, array $glossary): void
    {
        foreach ($ruleTags as $t) {
            q('INSERT INTO rule_tags (code, section, name_id, name_en, glossary_slug) VALUES (?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE section = VALUES(section), name_id = VALUES(name_id), name_en = VALUES(name_en), glossary_slug = VALUES(glossary_slug)', [
                $t['code'], $t['section'], $t['name_id'], $t['name_en'], $t['glossary_slug'] ?? null,
            ]);
        }
        $bloks = [];
        foreach (q_all('SELECT id, code FROM bloks') as $b) {
            $bloks[$b['code']] = (int) $b['id'];
        }
        foreach ($rumahs as $r) {
            q('INSERT INTO rumahs (blok_id, number, slug, kind, title_id, title_en, sort) VALUES (?, ?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE blok_id = VALUES(blok_id), number = VALUES(number), kind = VALUES(kind), title_id = VALUES(title_id), title_en = VALUES(title_en), sort = VALUES(sort)', [
                isset($r['blok']) ? ($bloks[$r['blok']] ?? null) : null, (int) ($r['number'] ?? 0), $r['slug'], $r['kind'], $r['title_id'], $r['title_en'], (int) ($r['sort'] ?? 0),
            ]);
        }
        foreach ($glossary as $i => $g) {
            q('INSERT INTO glossary (slug, grp, term_en, meaning_id, def_id, def_en, example, analogy_id, analogy_en, sort) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE grp = VALUES(grp), term_en = VALUES(term_en), meaning_id = VALUES(meaning_id), def_id = VALUES(def_id), def_en = VALUES(def_en),
               example = VALUES(example), analogy_id = VALUES(analogy_id), analogy_en = VALUES(analogy_en), sort = VALUES(sort)', [
                $g['slug'], $g['grp'], $g['term_en'], $g['meaning_id'], $g['def_id'], $g['def_en'], $g['example'] ?? null, $g['analogy_id'] ?? null, $g['analogy_en'] ?? null, (int) ($g['sort'] ?? $i),
            ]);
        }
    }

    /* ------------------------------------------------------------ */

    private function importRumahData(string $slug, array $data, bool $validateOnly, string $label): int
    {
        $rumah = null;
        try {
            $rumah = q_one('SELECT * FROM rumahs WHERE slug = ?', [$slug]);
        } catch (Throwable $e) {
        }
        if (!$rumah && !$validateOnly) {
            $this->errors[] = "$label: Rumah '$slug' belum ada di tabel rumahs.";
            return 0;
        }
        $items = $data['items'] ?? [];
        $errBefore = count($this->errors);
        $flat = $this->normalize($slug, $items, $label);
        $this->validateRumah($slug, $flat, $label);
        if ($validateOnly || count($this->errors) > $errBefore) {
            return count($flat['questions']);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if (isset($data['material_id']) || isset($data['material_en'])) {
                q('UPDATE rumahs SET material_id = ?, material_en = ? WHERE id = ?', [$data['material_id'] ?? null, $data['material_en'] ?? null, $rumah['id']]);
            }
            $passageIds = [];
            foreach ($flat['passages'] as $key => $p) {
                $audioId = null;
                if ($p['section'] === 'listening') {
                    $audioId = $this->audioId($p['audio']);
                }
                $ex = q_one('SELECT id FROM passages WHERE rumah_id = ? AND section = ? AND sort = ?', [$rumah['id'], $p['section'], $p['sort']]);
                $script = $p['script'] ? json_encode($p['script'], JSON_UNESCAPED_UNICODE) : null;
                if ($ex) {
                    q('UPDATE passages SET part = ?, title = ?, body = ?, script = ?, audio_talk_id = ? WHERE id = ?', [$p['part'], $p['title'], $p['body'], $script, $audioId, $ex['id']]);
                    $passageIds[$key] = (int) $ex['id'];
                } else {
                    q('INSERT INTO passages (rumah_id, section, part, title, body, script, audio_talk_id, sort) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                        $rumah['id'], $p['section'], $p['part'], $p['title'], $p['body'], $script, $audioId, $p['sort'],
                    ]);
                    $passageIds[$key] = (int) $pdo->lastInsertId();
                }
            }
            foreach ($flat['questions'] as $q) {
                $j = fn($v) => $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE);
                $params = [
                    $rumah['id'], $q['passage_key'] !== null ? $passageIds[$q['passage_key']] : null, $q['section'], $q['type'], $q['subtype'] ?? null, (int) $q['number'],
                    $q['prompt'] ?? null, $j($q['options']), $j($q['underlines'] ?? null), (int) $q['correct_index'],
                    $q['why_correct_id'] ?? null, $q['why_correct_en'] ?? null, $j($q['why_wrong_id'] ?? null), $j($q['why_wrong_en'] ?? null),
                    $q['analogy_id'] ?? null, $q['analogy_en'] ?? null, $q['tip_id'] ?? null, $q['tip_en'] ?? null,
                    $q['key_sentence'] ?? null, $q['key_translation_id'] ?? null, $q['liaison_note'] ?? null, $q['evidence_quote'] ?? null,
                    $j($q['rule_tags'] ?? []), (int) ($q['difficulty'] ?? 1), isset($q['audio']) ? $this->audioId($q['audio']) : null, $q['status'] ?? 'published',
                ];
                q('INSERT INTO questions (rumah_id, passage_id, section, type, subtype, number, prompt, options, underlines, correct_index,
                     why_correct_id, why_correct_en, why_wrong_id, why_wrong_en, analogy_id, analogy_en, tip_id, tip_en,
                     key_sentence, key_translation_id, liaison_note, evidence_quote, rule_tags, difficulty, audio_question_id, status)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE passage_id = VALUES(passage_id), type = VALUES(type), subtype = VALUES(subtype), prompt = VALUES(prompt), options = VALUES(options),
                     underlines = VALUES(underlines), correct_index = VALUES(correct_index), why_correct_id = VALUES(why_correct_id), why_correct_en = VALUES(why_correct_en),
                     why_wrong_id = VALUES(why_wrong_id), why_wrong_en = VALUES(why_wrong_en), analogy_id = VALUES(analogy_id), analogy_en = VALUES(analogy_en),
                     tip_id = VALUES(tip_id), tip_en = VALUES(tip_en), key_sentence = VALUES(key_sentence), key_translation_id = VALUES(key_translation_id),
                     liaison_note = VALUES(liaison_note), evidence_quote = VALUES(evidence_quote), rule_tags = VALUES(rule_tags), difficulty = VALUES(difficulty),
                     audio_question_id = VALUES(audio_question_id), status = VALUES(status)', $params);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->errors[] = "$label: gagal impor — " . $e->getMessage();
            return 0;
        }
        return count($flat['questions']);
    }

    private function audioId(?string $path): ?int
    {
        if (!$path) {
            return null;
        }
        $m = $this->manifest[$path] ?? null;
        q('INSERT INTO audio_files (path, duration_ms, hash) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE duration_ms = COALESCE(VALUES(duration_ms), duration_ms), hash = COALESCE(VALUES(hash), hash)', [
            $path, $m['duration_ms'] ?? null, $m['hash'] ?? null,
        ]);
        return (int) q_val('SELECT id FROM audio_files WHERE path = ?', [$path]);
    }

    /** Ratakan item menjadi daftar passage + soal, beri nomor & path audio. */
    private function normalize(string $slug, array $items, string $label): array
    {
        $passages = [];
        $questions = [];
        $numbers = ['listening' => 0, 'structure' => 0, 'reading' => 0];
        $sorts = ['listening' => 0, 'reading' => 0];
        foreach ($items as $idx => $it) {
            $section = $it['section'] ?? null;
            if (!isset(self::TYPES[$section])) {
                $this->errors[] = "$label item #" . ($idx + 1) . ": section tidak valid";
                continue;
            }
            if (isset($it['questions'])) {
                if ($section === 'structure') {
                    $this->errors[] = "$label item #" . ($idx + 1) . ': structure tidak memakai grup';
                    continue;
                }
                $sorts[$section]++;
                $g = $sorts[$section];
                $key = $section . ':' . $g;
                $passages[$key] = [
                    'section' => $section,
                    'part'    => $section === 'listening' ? strtoupper((string) ($it['part'] ?? 'A')) : null,
                    'title'   => $it['title'] ?? null,
                    'body'    => $section === 'reading' ? ($it['body'] ?? null) : null,
                    'script'  => $section === 'listening' ? ($it['script'] ?? []) : null,
                    'sort'    => $g,
                    'audio'   => $section === 'listening' ? sprintf('audio/%s/L-%s-g%02d-talk.mp3', $slug, $slug, $g) : null,
                    'label'   => "$label grup $section #$g",
                ];
                foreach ($it['questions'] as $k => $q) {
                    $numbers[$section]++;
                    $q['section'] = $section;
                    $q['number'] = $q['number'] ?? $numbers[$section];
                    $numbers[$section] = max($numbers[$section], (int) $q['number']);
                    $q['passage_key'] = $key;
                    $q['type'] = $q['type'] ?? ($section === 'reading' ? 'reading' : 'listening_short');
                    if ($section === 'listening') {
                        $q['audio'] = sprintf('audio/%s/L-%s-g%02d-q%d.mp3', $slug, $slug, $g, $k + 1);
                    }
                    $questions[] = $q;
                }
            } else {
                $numbers[$section]++;
                $it['number'] = $it['number'] ?? $numbers[$section];
                $numbers[$section] = max($numbers[$section], (int) $it['number']);
                $it['passage_key'] = null;
                $questions[] = $it;
            }
        }
        return ['passages' => $passages, 'questions' => $questions];
    }

    private function validateRumah(string $slug, array $flat, string $label): void
    {
        $isSim = str_starts_with($slug, 'sim');
        $seenNum = [];
        $dist = [0, 0, 0, 0];
        $diff = [1 => 0, 2 => 0, 3 => 0];
        foreach ($flat['passages'] as $p) {
            if ($p['section'] === 'reading') {
                if (trim((string) $p['body']) === '') {
                    $this->errors[] = "{$p['label']}: body (teks reading) kosong";
                } else {
                    $wc = ReadingText::wordCount((string) $p['body']);
                    if ($wc < 250 || $wc > 450) {
                        $this->warnings[] = "{$p['label']}: panjang teks $wc kata (disarankan 250–450)";
                    }
                }
            } else {
                $script = $p['script'] ?? [];
                if (!$script) {
                    $this->errors[] = "{$p['label']}: script kosong";
                }
                foreach ($script as $li => $line) {
                    if (!isset($line['speaker'], $line['text']) || !preg_match('/^(N|LEC|[MW][1-4])$/', (string) $line['speaker']) || trim((string) $line['text']) === '') {
                        $this->errors[] = "{$p['label']}: baris script #" . ($li + 1) . ' tidak valid (speaker: N, M1–M4, W1–W4, LEC)';
                    }
                }
                $turns = count($script);
                $words = array_sum(array_map(fn($l) => str_word_count((string) ($l['text'] ?? '')), $script));
                if ($p['part'] === 'B' && ($turns < 6 || $turns > 12)) {
                    $this->warnings[] = "{$p['label']}: long dialogue $turns giliran (disarankan 6–12)";
                }
                if ($p['part'] === 'C' && ($words < 150 || $words > 250)) {
                    $this->warnings[] = "{$p['label']}: lecture $words kata (disarankan 150–250)";
                }
                if ($p['part'] === 'A' && $turns !== 2) {
                    $this->warnings[] = "{$p['label']}: short dialogue sebaiknya 2 baris";
                }
            }
        }
        foreach ($flat['questions'] as $q) {
            $sec = $q['section'];
            $n = (int) ($q['number'] ?? 0);
            $id = "$label {$sec} #$n";
            if (isset($seenNum[$sec][$n])) {
                $this->errors[] = "$id: nomor ganda";
            }
            $seenNum[$sec][$n] = true;
            if (!in_array($q['type'] ?? '', self::TYPES[$sec], true)) {
                $this->errors[] = "$id: type '" . ($q['type'] ?? '') . "' tidak cocok untuk seksi $sec";
            }
            if ($sec === 'reading' && !in_array($q['subtype'] ?? '', self::READING_SUBTYPES, true)) {
                $this->errors[] = "$id: subtype reading wajib salah satu dari " . implode(', ', self::READING_SUBTYPES);
            }
            $opts = $q['options'] ?? null;
            if (!is_array($opts) || count($opts) !== 4 || count(array_filter($opts, fn($o) => is_string($o) && trim($o) !== '')) !== 4) {
                $this->errors[] = "$id: options wajib 4 teks";
            }
            $ci = $q['correct_index'] ?? null;
            if (!is_int($ci) || $ci < 0 || $ci > 3) {
                $this->errors[] = "$id: correct_index wajib 0..3";
                $ci = 0;
            }
            $dist[$ci]++;
            $d = (int) ($q['difficulty'] ?? 0);
            if ($d < 1 || $d > 3) {
                $this->errors[] = "$id: difficulty wajib 1..3";
            } else {
                $diff[$d]++;
            }
            foreach (['why_correct_id', 'why_correct_en', 'analogy_id', 'analogy_en', 'tip_id', 'tip_en'] as $f) {
                if (trim((string) ($q[$f] ?? '')) === '' || trim((string) $q[$f]) === '...') {
                    $this->errors[] = "$id: field $f kosong";
                }
            }
            foreach (['why_wrong_id', 'why_wrong_en'] as $f) {
                $w = $q[$f] ?? [];
                for ($i = 0; $i < 4; $i++) {
                    if ($i === $ci) {
                        continue;
                    }
                    $v = trim((string) ($w[(string) $i] ?? ''));
                    if ($v === '' || $v === '...') {
                        $this->errors[] = "$id: $f untuk pilihan " . 'ABCD'[$i] . ' kosong';
                    }
                }
            }
            $tags = $q['rule_tags'] ?? [];
            if (!is_array($tags) || count($tags) < 1 || count($tags) > 2) {
                $this->errors[] = "$id: rule_tags wajib 1–2 kode";
            } else {
                foreach ($tags as $t) {
                    if ($this->tagCodes && !in_array($t, $this->tagCodes, true)) {
                        $this->errors[] = "$id: tag '$t' tidak ada di rule_tags.json";
                    }
                }
            }
            $prompt = (string) ($q['prompt'] ?? '');
            if ($q['type'] === 'structure_error') {
                foreach (['A', 'B', 'C', 'D'] as $i => $L) {
                    if (!preg_match('/\{' . $L . '\}(.+?)\{\/' . $L . '\}/', $prompt, $m)) {
                        $this->errors[] = "$id: prompt wajib memuat {{$L}}…{/{$L}}";
                    } elseif (is_array($opts) && isset($opts[$i]) && trim($m[1]) !== trim((string) $opts[$i])) {
                        $this->warnings[] = "$id: option $L ('{$opts[$i]}') berbeda dari bagian bergaris bawah ('{$m[1]}')";
                    }
                }
            }
            if ($q['type'] === 'structure_completion' && !str_contains($prompt, '______')) {
                $this->errors[] = "$id: prompt structure_completion wajib memuat rumpang ______";
            }
            if ($sec === 'listening') {
                if (trim($prompt) === '') {
                    $this->errors[] = "$id: prompt (pertanyaan narator) kosong";
                }
                if (($q['type'] ?? '') === 'listening_short' && empty($q['key_sentence'])) {
                    $this->warnings[] = "$id: key_sentence kosong";
                }
                if (!empty($q['key_sentence']) && isset($flat['passages'][$q['passage_key']])) {
                    $all = implode(' ', array_map(fn($l) => (string) ($l['text'] ?? ''), $flat['passages'][$q['passage_key']]['script'] ?? []));
                    if (!str_contains($all, (string) $q['key_sentence'])) {
                        $this->warnings[] = "$id: key_sentence tidak ditemukan persis di script";
                    }
                }
            }
            if ($sec === 'reading') {
                if (trim((string) ($q['evidence_quote'] ?? '')) === '') {
                    $this->errors[] = "$id: evidence_quote kosong";
                } elseif (isset($flat['passages'][$q['passage_key']])) {
                    $body = preg_replace('/\s+/', ' ', str_replace('**', '', (string) $flat['passages'][$q['passage_key']]['body']));
                    if (!str_contains($body, preg_replace('/\s+/', ' ', str_replace('**', '', (string) $q['evidence_quote'])))) {
                        $this->warnings[] = "$id: evidence_quote tidak ditemukan persis di teks";
                    }
                }
            }
            // Simulasi tidak boleh memakai soal yang sama dengan Rumah
            $key = mb_strtolower(preg_replace('/\W+/u', '', $prompt . implode('', (array) $opts)));
            if ($sec !== 'listening' && $key !== '') {
                if ($isSim && isset($this->practicePrompts[$key])) {
                    $this->errors[] = "$id: soal sama dengan " . $this->practicePrompts[$key];
                }
                if (!$isSim) {
                    $this->practicePrompts[$key] = $id;
                }
            }
        }
        $total = array_sum($dist);
        if ($total >= 20 && $slug !== 'contoh') {
            foreach ($dist as $i => $c) {
                $pct = $c / $total * 100;
                if ($pct < 20 || $pct > 30) {
                    $this->warnings[] = "$label: jawaban " . 'ABCD'[$i] . ' = ' . round($pct) . '% (disarankan 20–30%)';
                }
            }
        }
    }
}
