<?php
declare(strict_types=1);

/**
 * Mesin latihan: membuat/melanjutkan percobaan, menyimpan jawaban,
 * menyelesaikan seksi, progres, dan Buku Kesalahan.
 */
final class Practice
{
    public const SECTIONS = ['listening', 'structure', 'reading'];
    /** Durasi simulasi (detik) */
    public const SIM_SECONDS = ['listening' => 45 * 60, 'structure' => 25 * 60, 'reading' => 55 * 60];
    /** Tempo latihan per soal (detik) */
    public const TEMPO = ['listening' => 0, 'structure' => 37, 'reading' => 66];
    /** Waktu menjawab tiap soal listening di Simulasi */
    public const SIM_ANSWER_SECONDS = 12;
    public const ADAPTIVE_PER_SECTION = 10;

    /* ------------------------------------------------------------
     * Daftar soal
     * ------------------------------------------------------------ */

    /** Soal terbit untuk satu Rumah + seksi, berurutan sesuai grup/nomor. */
    public static function sectionQuestionIds(int $rumahId, string $section): array
    {
        $rows = q_all(
            "SELECT q.id FROM questions q LEFT JOIN passages p ON p.id = q.passage_id
             WHERE q.rumah_id = ? AND q.section = ? AND q.status = 'published'
             ORDER BY q.number, COALESCE(p.sort, 0), q.id",
            [$rumahId, $section]
        );
        return array_map(fn($r) => (int) $r['id'], $rows);
    }

    /** Rumah adaptif: soal dari Buku Kesalahan + soal bertag sama + bank Blok D. */
    public static function adaptiveQuestionIds(int $userId, string $section, int $limit = self::ADAPTIVE_PER_SECTION): array
    {
        $ids = [];
        $mist = q_all(
            "SELECT m.question_id, q.rule_tags FROM mistakes m JOIN questions q ON q.id = m.question_id
             WHERE m.user_id = ? AND m.correct_streak < 2 AND q.section = ? AND q.status = 'published'
             ORDER BY m.wrong_count DESC, m.last_seen_at DESC LIMIT 60",
            [$userId, $section]
        );
        $tags = [];
        foreach ($mist as $m) {
            if (count($ids) < (int) ceil($limit * 0.6)) {
                $ids[] = (int) $m['question_id'];
            }
            foreach (json_col($m['rule_tags']) as $t) {
                $tags[$t] = true;
            }
        }
        // Soal baru dengan tag aturan yang sama, belum pernah dijawab (termasuk bank Hari adaptif)
        if ($tags && count($ids) < $limit) {
            $cands = q_all(
                "SELECT q.id, q.passage_id, q.rule_tags FROM questions q JOIN rumahs r ON r.id = q.rumah_id
                 WHERE q.section = ? AND q.status = 'published' AND r.kind IN ('practice','adaptive')
                   AND q.id NOT IN (SELECT question_id FROM answers WHERE user_id = ?)
                 ORDER BY RAND() LIMIT 300",
                [$section, $userId]
            );
            $cands = array_values(array_filter($cands, fn($c) => (bool) array_intersect(json_col($c['rule_tags']), array_keys($tags))));
            self::addWithSiblings($ids, $cands, $limit);
        }
        // Tambah dari bank Level D (Hari 12, 13, dan bank Hari 14); soal yang belum pernah dijawab didahulukan
        if (count($ids) < $limit) {
            $fill = q_all(
                "SELECT q.id, q.passage_id FROM questions q JOIN rumahs r ON r.id = q.rumah_id JOIN bloks b ON b.id = r.blok_id
                 WHERE b.code = 'D' AND r.kind IN ('practice','adaptive') AND q.section = ? AND q.status = 'published'
                 ORDER BY (q.id IN (SELECT question_id FROM answers WHERE user_id = ?)), RAND() LIMIT 120",
                [$section, $userId]
            );
            self::addWithSiblings($ids, $fill, $limit);
        }
        return self::groupOrder($ids);
    }

    /**
     * Tambahkan kandidat ke $ids sampai $limit. Soal bergrup (satu percakapan/teks) diambil bersama
     * saudara-saudaranya dalam daftar kandidat bila semuanya masih muat, agar audio/teks tidak dipakai
     * untuk satu soal saja; bila tidak muat, soal itu dilewati dan dicoba lagi di putaran kedua.
     *
     * @param array<int,int> $ids
     * @param array<int,array{id:mixed,passage_id:mixed}> $cands
     */
    private static function addWithSiblings(array &$ids, array $cands, int $limit): void
    {
        $byPassage = [];
        foreach ($cands as $c) {
            if ($c['passage_id']) {
                $byPassage[(int) $c['passage_id']][] = (int) $c['id'];
            }
        }
        foreach ([true, false] as $wholeGroups) {
            foreach ($cands as $c) {
                if (count($ids) >= $limit) {
                    return;
                }
                $id = (int) $c['id'];
                if (in_array($id, $ids, true)) {
                    continue;
                }
                $group = $c['passage_id'] ? array_values(array_diff($byPassage[(int) $c['passage_id']], $ids)) : [$id];
                if (count($ids) + count($group) <= $limit) {
                    array_push($ids, ...$group);
                } elseif (!$wholeGroups) {
                    $ids[] = $id;
                }
            }
        }
    }

    /** Urutkan agar soal dengan passage yang sama berdekatan (listening/reading). */
    public static function groupOrder(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $qs = Question::many($ids);
        $order = [];
        $seenPassage = [];
        foreach ($ids as $id) {
            if (!isset($qs[$id])) {
                continue;
            }
            $pid = $qs[$id]['passage_id'];
            if ($pid) {
                if (isset($seenPassage[$pid])) {
                    continue;
                }
                $seenPassage[$pid] = true;
                $group = array_filter($ids, fn($x) => isset($qs[$x]) && $qs[$x]['passage_id'] == $pid);
                usort($group, fn($a, $b) => $qs[$a]['number'] <=> $qs[$b]['number']);
                array_push($order, ...$group);
            } else {
                $order[] = $id;
            }
        }
        return array_values(array_unique($order));
    }

    /* ------------------------------------------------------------
     * Percobaan
     * ------------------------------------------------------------ */

    public static function attempt(int $id): ?array
    {
        $a = q_one('SELECT * FROM attempts WHERE id = ?', [$id]);
        if ($a) {
            $a['question_ids'] = array_map('intval', json_col($a['question_ids']));
        }
        return $a;
    }

    public static function ownedAttempt(int $id, array $user, bool $allowAdmin = false): array
    {
        $a = self::attempt($id);
        if (!$a || ((int) $a['user_id'] !== (int) $user['id'] && !($allowAdmin && $user['role'] === 'admin'))) {
            abort(404);
        }
        return $a;
    }

    /** Cari percobaan terbuka atau buat baru. */
    public static function openAttempt(array $user, array $rumah, string $section, string $mode): ?array
    {
        $open = q_one(
            'SELECT id FROM attempts WHERE user_id = ? AND rumah_id = ? AND section = ? AND mode = ? AND finished_at IS NULL ORDER BY id DESC LIMIT 1',
            [$user['id'], $rumah['id'], $section, $mode]
        );
        if ($open) {
            return self::attempt((int) $open['id']);
        }
        $ids = $rumah['kind'] === 'adaptive'
            ? self::adaptiveQuestionIds((int) $user['id'], $section)
            : self::sectionQuestionIds((int) $rumah['id'], $section);
        if (!$ids) {
            return null;
        }
        return self::createAttempt((int) $user['id'], (int) $rumah['id'], $section, $mode, $ids);
    }

    public static function createAttempt(int $userId, int $rumahId, string $section, string $mode, array $ids): array
    {
        $remaining = $mode === 'simulation' ? self::SIM_SECONDS[$section] : null;
        q('INSERT INTO attempts (user_id, rumah_id, section, mode, question_ids, total, remaining_s) VALUES (?, ?, ?, ?, ?, ?, ?)', [
            $userId, $rumahId, $section, $mode, json_encode(array_values($ids)), count($ids), $remaining,
        ]);
        return self::attempt((int) db()->lastInsertId());
    }

    /** Waktu tersisa simulasi; dihitung server dengan jeda maksimal 20 dtk per detak. */
    public static function simTick(array $a, bool $resume = false): int
    {
        if ($a['mode'] !== 'simulation') {
            return 0;
        }
        $remaining = (int) $a['remaining_s'];
        if (!$resume && $a['tick_at']) {
            $elapsed = time() - strtotime((string) $a['tick_at']);
            $remaining -= max(0, min($elapsed, 20));
        }
        $remaining = max(0, $remaining);
        q('UPDATE attempts SET remaining_s = ?, tick_at = NOW() WHERE id = ?', [$remaining, $a['id']]);
        return $remaining;
    }

    /* ------------------------------------------------------------
     * Payload untuk klien (TANPA kunci jawaban)
     * ------------------------------------------------------------ */

    public static function directions(): array
    {
        static $d = null;
        if ($d === null) {
            $file = APP_PATH . '/database/seed/directions.json';
            $d = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }
        return $d;
    }

    public static function clientQuestions(array $ids, string $mode): array
    {
        $qs = Question::many($ids);
        $passages = Question::passages(array_values($qs));
        $audioIds = array_filter(array_map(fn($q) => $q['audio_question_id'] ? (int) $q['audio_question_id'] : null, $qs));
        $audioPaths = [];
        if ($audioIds) {
            $in = implode(',', array_fill(0, count($audioIds), '?'));
            foreach (q_all("SELECT id, path FROM audio_files WHERE id IN ($in)", array_values($audioIds)) as $r) {
                $audioPaths[(int) $r['id']] = $r['path'];
            }
        }
        $dirs = self::directions();
        $out = [];
        $lastPassage = null;
        $seenPart = [];
        $groupPos = [];
        foreach (array_values($ids) as $idx => $id) {
            if (!isset($qs[$id])) {
                continue;
            }
            $q = $qs[$id];
            $p = $q['passage_id'] ? ($passages[(int) $q['passage_id']] ?? null) : null;
            $item = [
                'id'      => (int) $q['id'],
                'n'       => $idx + 1,
                'section' => $q['section'],
                'type'    => $q['type'],
                'subtype' => $q['subtype'],
                'options' => $q['options'],
                'passage' => $p ? (int) $p['id'] : null,
                'html'    => $q['section'] === 'listening' ? null : render_question_text((string) $q['prompt']),
            ];
            if ($q['section'] === 'listening') {
                $pid = $p ? (int) $p['id'] : 0;
                $groupPos[$pid] = ($groupPos[$pid] ?? 0) + 1;
                $first = $pid !== $lastPassage;
                $part = $p['part'] ?? 'A';
                $qPath = $audioPaths[(int) $q['audio_question_id']] ?? null;
                $item['listening'] = [
                    'first_in_group' => $first,
                    'part'           => $part,
                    'talk'           => $first && $p ? self::audioRef($p['audio_path'] ?? null) : null,
                    'question'       => self::audioRef($qPath),
                    'prompt'         => (string) $q['prompt'],
                    // Teks pertanyaan ditampilkan di layar untuk Part B/C; Part A hanya pilihan (seperti tes)
                    'show_prompt'    => $part !== 'A',
                ];
                if ($first && !isset($seenPart[$part])) {
                    $seenPart[$part] = true;
                    $dpath = 'audio/common/dir-part-' . strtolower($part) . '.mp3';
                    $item['listening']['directions'] = [
                        'url'  => audio_exists($dpath) ? url($dpath) : null,
                        'text' => $dirs['part_' . strtolower($part)] ?? '',
                    ];
                }
                if ($mode === 'simulation') {
                    $npath = sprintf('audio/common/num-%02d.mp3', $idx + 1);
                    $item['listening']['number'] = ['url' => audio_exists($npath) ? url($npath) : null, 'text' => 'Number ' . ($idx + 1) . '.'];
                }
                $lastPassage = $pid;
            }
            $out[] = $item;
        }
        $clientPassages = [];
        foreach ($passages as $p) {
            $cp = ['id' => (int) $p['id'], 'section' => $p['section'], 'part' => $p['part'], 'title' => $p['title']];
            if ($p['section'] === 'reading') {
                $cp['html'] = ReadingText::html((string) $p['body']);
            } else {
                // Naskah hanya dikirim bila dibutuhkan untuk suara cadangan browser (MP3 belum ada)
                $talkOk = audio_exists($p['audio_path'] ?? null);
                $cp['script'] = $talkOk ? null : array_map(fn($l) => ['speaker' => $l['speaker'], 'text' => $l['text'], 'g' => Speaker::gender($l['speaker'])], $p['script']);
            }
            $clientPassages[(string) $p['id']] = $cp;
        }
        return ['questions' => $out, 'passages' => $clientPassages];
    }

    private static function audioRef(?string $path): ?array
    {
        if (!$path) {
            return null;
        }
        return ['url' => audio_exists($path) ? url($path) : null, 'path' => $path];
    }

    /** Hasil + pembahasan sebuah jawaban (dipakai setelah pengguna menjawab). */
    public static function reveal(array $q, ?int $chosen): array
    {
        $passage = null;
        if ($q['passage_id']) {
            $passage = Question::passages([$q])[(int) $q['passage_id']] ?? null;
        }
        return [
            'question_id'   => (int) $q['id'],
            'correct_index' => (int) $q['correct_index'],
            'is_correct'    => $chosen !== null && $chosen === (int) $q['correct_index'],
            'evidence'      => $q['section'] === 'reading' ? (string) $q['evidence_quote'] : null,
            'html'          => Question::render($q, $chosen, $passage),
        ];
    }

    public static function attemptPayload(array $a, array $user): array
    {
        $data = self::clientQuestions($a['question_ids'], $a['mode']);
        $answers = [];
        $rows = q_all('SELECT question_id, chosen_index, is_correct, flagged FROM answers WHERE attempt_id = ?', [$a['id']]);
        $reveal = $a['mode'] !== 'simulation' || $a['finished_at'];
        $qs = $reveal && $rows ? Question::many(array_column($rows, 'question_id')) : [];
        foreach ($rows as $r) {
            $item = ['chosen' => $r['chosen_index'] === null ? null : (int) $r['chosen_index'], 'flagged' => (bool) $r['flagged']];
            if ($reveal && isset($qs[(int) $r['question_id']])) {
                $item += self::reveal($qs[(int) $r['question_id']], $item['chosen']);
            }
            $answers[(string) $r['question_id']] = $item;
        }
        $remaining = $a['mode'] === 'simulation' && !$a['finished_at'] ? self::simTick($a, true) : null;
        return [
            'ok'        => true,
            'attempt'   => [
                'id' => (int) $a['id'], 'mode' => $a['mode'], 'section' => $a['section'],
                'finished' => (bool) $a['finished_at'], 'remaining_s' => $remaining,
                'tempo' => self::TEMPO[$a['section']] ?? 0, 'sim_answer_s' => self::SIM_ANSWER_SECONDS,
            ],
            'questions' => $data['questions'],
            'passages'  => $data['passages'],
            'answers'   => $answers,
        ];
    }

    /* ------------------------------------------------------------
     * Menjawab & menyelesaikan
     * ------------------------------------------------------------ */

    public static function answer(array $a, array $user, int $questionId, ?int $chosen, int $timeMs, bool $flagged, bool $audioMissing): array
    {
        if ($a['finished_at']) {
            return ['ok' => false, 'error' => 'finished'];
        }
        if (!in_array($questionId, $a['question_ids'], true)) {
            return ['ok' => false, 'error' => 'not_in_attempt'];
        }
        if ($chosen !== null && ($chosen < 0 || $chosen > 3)) {
            return ['ok' => false, 'error' => 'bad_choice'];
        }
        $q = Question::find($questionId);
        if (!$q) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        $timeMs = max(0, min($timeMs, 3600 * 1000));
        $existing = q_one('SELECT * FROM answers WHERE attempt_id = ? AND question_id = ?', [$a['id'], $questionId]);
        $isCorrect = $chosen !== null && $chosen === (int) $q['correct_index'];

        if ($a['mode'] === 'simulation') {
            $remaining = self::simTick($a);
            if ($remaining <= 0) {
                $res = self::finish(self::attempt((int) $a['id']), $user);
                return ['ok' => false, 'error' => 'time_up', 'redirect' => $res['redirect']];
            }
            if ($existing && $q['section'] === 'listening' && $existing['chosen_index'] !== null) {
                return ['ok' => false, 'error' => 'locked'];
            }
            if ($existing) {
                q('UPDATE answers SET chosen_index = ?, is_correct = ?, time_ms = time_ms + ?, flagged = ?, audio_missing = GREATEST(audio_missing, ?) WHERE id = ?', [
                    $chosen, $isCorrect ? 1 : 0, $timeMs, $flagged ? 1 : 0, $audioMissing ? 1 : 0, $existing['id'],
                ]);
            } else {
                q('INSERT INTO answers (attempt_id, question_id, user_id, chosen_index, is_correct, time_ms, flagged, audio_missing) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                    $a['id'], $questionId, $user['id'], $chosen, $isCorrect ? 1 : 0, $timeMs, $flagged ? 1 : 0, $audioMissing ? 1 : 0,
                ]);
            }
            self::updateProgress((int) $user['id'], (int) $a['rumah_id']);
            return ['ok' => true, 'saved' => true, 'remaining_s' => $remaining];
        }

        // Mode latihan / review: jawaban terkunci setelah pembahasan muncul
        if ($existing && $existing['chosen_index'] !== null) {
            return ['ok' => true, 'locked' => true] + self::reveal($q, (int) $existing['chosen_index']);
        }
        if ($chosen === null) {
            return ['ok' => false, 'error' => 'no_choice'];
        }
        if ($existing) {
            q('UPDATE answers SET chosen_index = ?, is_correct = ?, time_ms = ?, flagged = ?, audio_missing = ? WHERE id = ?', [
                $chosen, $isCorrect ? 1 : 0, $timeMs, $flagged ? 1 : 0, $audioMissing ? 1 : 0, $existing['id'],
            ]);
        } else {
            q('INSERT INTO answers (attempt_id, question_id, user_id, chosen_index, is_correct, time_ms, flagged, audio_missing) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                $a['id'], $questionId, $user['id'], $chosen, $isCorrect ? 1 : 0, $timeMs, $flagged ? 1 : 0, $audioMissing ? 1 : 0,
            ]);
        }
        self::updateMistake((int) $user['id'], $questionId, $isCorrect);
        $progress = self::updateProgress((int) $user['id'], (int) $a['rumah_id']);
        self::refreshAttemptScore((int) $a['id']);
        return ['ok' => true, 'progress' => $progress] + self::reveal($q, $chosen);
    }

    public static function flag(array $a, int $questionId, bool $flagged): void
    {
        if (!in_array($questionId, $a['question_ids'], true)) {
            return;
        }
        $ex = q_one('SELECT id FROM answers WHERE attempt_id = ? AND question_id = ?', [$a['id'], $questionId]);
        if ($ex) {
            q('UPDATE answers SET flagged = ? WHERE id = ?', [$flagged ? 1 : 0, $ex['id']]);
        } else {
            q('INSERT INTO answers (attempt_id, question_id, user_id, chosen_index, is_correct, flagged) VALUES (?, ?, ?, NULL, 0, ?)', [$a['id'], $questionId, $a['user_id'], $flagged ? 1 : 0]);
        }
    }

    public static function refreshAttemptScore(int $attemptId): void
    {
        q('UPDATE attempts SET correct = (SELECT COUNT(*) FROM answers WHERE attempt_id = ? AND is_correct = 1) WHERE id = ?', [$attemptId, $attemptId]);
    }

    public static function finish(array $a, array $user, int $timeSpent = 0): array
    {
        if (!$a['finished_at']) {
            if ($a['mode'] === 'simulation') {
                // Soal yang tidak dijawab dicatat sebagai kosong (salah)
                $answered = array_map('intval', array_column(q_all('SELECT question_id FROM answers WHERE attempt_id = ?', [$a['id']]), 'question_id'));
                foreach (array_diff($a['question_ids'], $answered) as $qid) {
                    q('INSERT IGNORE INTO answers (attempt_id, question_id, user_id, chosen_index, is_correct) VALUES (?, ?, ?, NULL, 0)', [$a['id'], $qid, $user['id']]);
                }
                foreach (q_all('SELECT question_id, is_correct FROM answers WHERE attempt_id = ?', [$a['id']]) as $r) {
                    self::updateMistake((int) $user['id'], (int) $r['question_id'], (bool) $r['is_correct']);
                }
                $timeSpent = self::SIM_SECONDS[$a['section']] - max(0, (int) $a['remaining_s']);
            } else {
                $timeSpent = (int) round(((int) q_val('SELECT COALESCE(SUM(time_ms),0) FROM answers WHERE attempt_id = ?', [$a['id']])) / 1000);
            }
            self::refreshAttemptScore((int) $a['id']);
            q('UPDATE attempts SET finished_at = NOW(), time_spent_s = ? WHERE id = ? AND finished_at IS NULL', [max(0, $timeSpent), $a['id']]);
            if ($a['mode'] !== 'review') {
                self::updateProgress((int) $user['id'], (int) $a['rumah_id'], true);
            }
        }
        return ['ok' => true, 'redirect' => url('hasil/' . $a['id'])];
    }

    /* ------------------------------------------------------------
     * Progres & Buku Kesalahan
     * ------------------------------------------------------------ */

    public static function updateMistake(int $userId, int $questionId, bool $correct): void
    {
        if ($correct) {
            q('UPDATE mistakes SET correct_streak = correct_streak + 1, last_seen_at = NOW() WHERE user_id = ? AND question_id = ?', [$userId, $questionId]);
        } else {
            q('INSERT INTO mistakes (user_id, question_id, wrong_count, correct_streak, last_seen_at) VALUES (?, ?, 1, 0, NOW())
               ON DUPLICATE KEY UPDATE wrong_count = wrong_count + 1, correct_streak = 0, last_seen_at = NOW()', [$userId, $questionId]);
        }
    }

    public static function updateProgress(int $userId, int $rumahId, bool $withPct = false): array
    {
        $rumah = Rumah::byId($rumahId);
        if (!$rumah || $rumah['kind'] === 'sample') {
            return [];
        }
        $distinct = (int) q_val(
            "SELECT COUNT(DISTINCT a.question_id) FROM answers a JOIN attempts t ON t.id = a.attempt_id
             WHERE a.user_id = ? AND t.rumah_id = ? AND t.mode IN ('practice','simulation') AND a.chosen_index IS NOT NULL",
            [$userId, $rumahId]
        );
        $threshold = Rumah::completionThreshold($rumah);
        $prev = q_one('SELECT * FROM progress WHERE user_id = ? AND rumah_id = ?', [$userId, $rumahId]);
        $completedAt = $prev['completed_at'] ?? null;
        $justCompleted = false;
        if (!$completedAt && $distinct >= $threshold && Rumah::total($rumah) > 0) {
            $completedAt = date('Y-m-d H:i:s');
            $justCompleted = true;
        }
        $lastPct = $prev['last_pct'] ?? null;
        $bestPct = $prev['best_pct'] ?? null;
        if ($withPct) {
            // Persentase dari percobaan terakhir yang selesai di tiap seksi
            $rows = q_all(
                "SELECT t.section, t.correct, t.total FROM attempts t
                 JOIN (SELECT section, MAX(id) mid FROM attempts WHERE user_id = ? AND rumah_id = ? AND finished_at IS NOT NULL AND mode IN ('practice','simulation') GROUP BY section) x ON x.mid = t.id",
                [$userId, $rumahId]
            );
            $c = array_sum(array_column($rows, 'correct'));
            $tot = array_sum(array_column($rows, 'total'));
            if ($tot > 0) {
                $lastPct = round($c / $tot * 100, 2);
                $bestPct = max((float) ($bestPct ?? 0), $lastPct);
            }
        }
        q('INSERT INTO progress (user_id, rumah_id, answered_distinct, best_pct, last_pct, completed_at) VALUES (?, ?, ?, ?, ?, ?)
           ON DUPLICATE KEY UPDATE answered_distinct = VALUES(answered_distinct), best_pct = VALUES(best_pct), last_pct = VALUES(last_pct), completed_at = VALUES(completed_at)', [
            $userId, $rumahId, $distinct, $bestPct, $lastPct, $completedAt,
        ]);
        return ['answered' => $distinct, 'threshold' => $threshold, 'completed' => (bool) $completedAt, 'just_completed' => $justCompleted];
    }

    /** @return array<int,array> rumah_id => baris progress */
    public static function progressMap(int $userId): array
    {
        $out = [];
        foreach (q_all('SELECT * FROM progress WHERE user_id = ?', [$userId]) as $p) {
            $out[(int) $p['rumah_id']] = $p;
        }
        return $out;
    }

    public static function rumahState(?array $progress): string
    {
        if (!$progress || (int) $progress['answered_distinct'] === 0) {
            return 'todo';
        }
        return $progress['completed_at'] ? 'done' : 'progress';
    }

    /** Status tiap seksi di halaman Rumah. */
    public static function sectionStatus(int $userId, array $rumah, string $section, string $mode = 'practice'): array
    {
        $open = q_one('SELECT * FROM attempts WHERE user_id = ? AND rumah_id = ? AND section = ? AND mode = ? AND finished_at IS NULL ORDER BY id DESC LIMIT 1', [$userId, $rumah['id'], $section, $mode]);
        if ($open) {
            $answered = (int) q_val('SELECT COUNT(*) FROM answers WHERE attempt_id = ? AND chosen_index IS NOT NULL', [$open['id']]);
            return ['state' => 'progress', 'answered' => $answered, 'total' => (int) $open['total'], 'attempt' => (int) $open['id']];
        }
        $last = q_one('SELECT * FROM attempts WHERE user_id = ? AND rumah_id = ? AND section = ? AND mode = ? AND finished_at IS NOT NULL ORDER BY id DESC LIMIT 1', [$userId, $rumah['id'], $section, $mode]);
        if ($last) {
            $best = (int) q_val('SELECT MAX(correct) FROM attempts WHERE user_id = ? AND rumah_id = ? AND section = ? AND mode = ? AND finished_at IS NOT NULL', [$userId, $rumah['id'], $section, $mode]);
            return ['state' => 'done', 'correct' => (int) $last['correct'], 'total' => (int) $last['total'], 'best' => $best, 'attempt' => (int) $last['id']];
        }
        $count = $rumah['kind'] === 'adaptive' ? self::ADAPTIVE_PER_SECTION : (Rumah::questionCounts()[(int) $rumah['id']][$section] ?? 0);
        return ['state' => 'todo', 'total' => $count];
    }

    /** Rumah berikutnya yang disarankan: 1-11 → Sim 1 → 12-14 → Sim 2. */
    /** Hari yang dituju tombol Lanjut Latihan: Hari terakhir yang dikerjakan dan belum selesai, atau saran berikutnya. */
    public static function continueTarget(int $userId, ?array $progress = null): ?array
    {
        $progress ??= self::progressMap($userId);
        $last = q_one("SELECT rumah_id FROM attempts WHERE user_id = ? AND mode IN ('practice','simulation') ORDER BY id DESC LIMIT 1", [$userId]);
        if ($last) {
            $r = Rumah::byId((int) $last['rumah_id']);
            if ($r && empty($progress[(int) $r['id']]['completed_at'])) {
                return $r;
            }
        }
        return self::suggestedNext($progress);
    }

    public static function suggestedNext(array $progress): ?array
    {
        $order = [];
        foreach (Rumah::all() as $r) {
            if ($r['kind'] === 'practice' || $r['kind'] === 'adaptive') {
                $order[(int) $r['number']] = $r;
            }
        }
        ksort($order);
        $sims = array_values(array_filter(Rumah::all(), fn($r) => $r['kind'] === 'simulation'));
        usort($sims, fn($a, $b) => $a['number'] <=> $b['number']);
        $seq = [];
        foreach ($order as $n => $r) {
            $seq[] = $r;
            if ($n === 11 && isset($sims[0])) {
                $seq[] = $sims[0];
            }
        }
        if (isset($sims[1])) {
            $seq[] = $sims[1];
        }
        foreach ($seq as $r) {
            $p = $progress[(int) $r['id']] ?? null;
            if (!($p['completed_at'] ?? null) && Rumah::total($r) > 0) {
                return $r;
            }
        }
        return null;
    }

    public static function blokCompleted(int $userId, string $code, ?array $progress = null): bool
    {
        $progress ??= self::progressMap($userId);
        $any = false;
        foreach (Rumah::all() as $r) {
            if ($r['blok_code'] === $code && in_array($r['kind'], ['practice', 'adaptive'], true)) {
                $any = true;
                if (empty($progress[(int) $r['id']]['completed_at'])) {
                    return false;
                }
            }
        }
        return $any;
    }

    /** Buku Kesalahan: dikelompokkan per tag aturan, urut dari yang paling sering salah. */
    public static function mistakeBook(int $userId, ?string $blokCode = null): array
    {
        $sql = "SELECT m.question_id, m.wrong_count, m.correct_streak, q.rule_tags, q.section, q.prompt, q.type, r.blok_id, b.code AS blok_code
                FROM mistakes m JOIN questions q ON q.id = m.question_id JOIN rumahs r ON r.id = q.rumah_id LEFT JOIN bloks b ON b.id = r.blok_id
                WHERE m.user_id = ? AND m.correct_streak < 2";
        $params = [$userId];
        if ($blokCode) {
            $sql .= ' AND b.code = ?';
            $params[] = $blokCode;
        }
        $groups = [];
        foreach (q_all($sql, $params) as $m) {
            $tags = json_col($m['rule_tags']) ?: ['-'];
            foreach ($tags as $t) {
                $groups[$t] ??= ['tag' => $t, 'section' => $m['section'], 'wrong' => 0, 'questions' => []];
                $groups[$t]['wrong'] += (int) $m['wrong_count'];
                $groups[$t]['questions'][] = $m;
            }
        }
        usort($groups, fn($a, $b) => [$b['wrong'], count($b['questions'])] <=> [$a['wrong'], count($a['questions'])]);
        return $groups;
    }

    /** Buat percobaan review dari tag aturan: soal salah + soal bertag sama, diacak. */
    public static function drillAttempt(int $userId, string $tag, int $limit = 10): ?array
    {
        $mist = q_all(
            "SELECT m.question_id, q.section, q.rumah_id FROM mistakes m JOIN questions q ON q.id = m.question_id
             WHERE m.user_id = ? AND m.correct_streak < 2 AND JSON_CONTAINS(q.rule_tags, JSON_QUOTE(?)) AND q.status = 'published'",
            [$userId, $tag]
        );
        $ids = array_map(fn($r) => (int) $r['question_id'], $mist);
        $section = $mist[0]['section'] ?? null;
        $others = q_all(
            "SELECT q.id, q.section, q.rumah_id FROM questions q JOIN rumahs r ON r.id = q.rumah_id
             WHERE JSON_CONTAINS(q.rule_tags, JSON_QUOTE(?)) AND q.status = 'published' AND r.kind IN ('practice','adaptive') ORDER BY RAND() LIMIT 40",
            [$tag]
        );
        foreach ($others as $o) {
            if (count($ids) >= $limit) {
                break;
            }
            $section ??= $o['section'];
            if ($o['section'] === $section && !in_array((int) $o['id'], $ids, true)) {
                $ids[] = (int) $o['id'];
            }
        }
        if (!$ids || !$section) {
            return null;
        }
        shuffle($ids);
        $ids = self::groupOrder(array_slice($ids, 0, $limit));
        $rumahId = (int) ($mist[0]['rumah_id'] ?? $others[0]['rumah_id']);
        return self::createAttempt($userId, $rumahId, $section, 'review', $ids);
    }
}
