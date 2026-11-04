<?php
declare(strict_types=1);

final class AdminController
{
    private array $admin;

    public function __construct()
    {
        $this->admin = require_admin();
    }

    private function page(string $view, array $data): never
    {
        $data['noindex'] = true;
        $data['scripts'] = array_merge(['admin'], $data['scripts'] ?? []);
        render('admin/' . $view, $data, 'admin/layout');
    }

    /* ================= Dashboard ================= */

    public function dashboard(): void
    {
        $kpi = [
            'users'   => (int) q_val("SELECT COUNT(*) FROM users WHERE role = 'member'"),
            'new7'    => (int) q_val("SELECT COUNT(*) FROM users WHERE role = 'member' AND created_at > NOW() - INTERVAL 7 DAY"),
            'active7' => (int) q_val("SELECT COUNT(*) FROM users WHERE role = 'member' AND last_active_at > NOW() - INTERVAL 7 DAY"),
            'today'   => (int) q_val('SELECT COUNT(*) FROM attempts WHERE DATE(started_at) = CURDATE()'),
        ];
        $funnel = [];
        foreach (Rumah::all() as $r) {
            if (in_array($r['kind'], ['practice', 'adaptive'], true)) {
                $funnel[] = ['n' => (int) $r['number'], 'count' => (int) q_val('SELECT COUNT(*) FROM progress WHERE rumah_id = ? AND completed_at IS NOT NULL', [$r['id']])];
            }
        }
        $secAvg = [];
        foreach (q_all('SELECT q.section, AVG(a.is_correct) p, COUNT(*) n FROM answers a JOIN questions q ON q.id = a.question_id WHERE a.chosen_index IS NOT NULL GROUP BY q.section') as $r) {
            $secAvg[$r['section']] = ['pct' => round((float) $r['p'] * 100, 1), 'n' => (int) $r['n']];
        }
        $hardest = q_all('SELECT q.id, q.section, q.type, q.prompt, r.slug, AVG(a.is_correct) p, COUNT(*) n FROM answers a JOIN questions q ON q.id = a.question_id JOIN rumahs r ON r.id = q.rumah_id
                          WHERE a.chosen_index IS NOT NULL GROUP BY q.id HAVING n >= 3 ORDER BY p ASC, n DESC LIMIT 10');
        $missingFiles = 0;
        foreach (q_all('SELECT path FROM audio_files') as $af) {
            if (!audio_exists($af['path'])) {
                $missingFiles++;
            }
        }
        $missingAnswers = (int) q_val('SELECT COUNT(DISTINCT question_id) FROM answers WHERE audio_missing = 1');
        $this->page('dashboard', ['title' => 'Dashboard', 'kpi' => $kpi, 'funnel' => $funnel, 'secAvg' => $secAvg, 'hardest' => $hardest,
            'missingFiles' => $missingFiles, 'missingAnswers' => $missingAnswers, 'totalAudio' => (int) q_val('SELECT COUNT(*) FROM audio_files')]);
    }

    /* ================= Pengguna ================= */

    private function userRows(): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? '');
        $sort = (string) ($_GET['sort'] ?? 'created_at');
        $dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $sorts = ['username' => 'u.username_lower', 'created_at' => 'u.created_at', 'last_active_at' => 'u.last_active_at', 'done' => 'done'];
        $orderBy = $sorts[$sort] ?? 'u.created_at';
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = 'u.username_lower LIKE ?';
            $params[] = '%' . strtolower($q) . '%';
        }
        if (in_array($status, ['active', 'disabled'], true)) {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }
        $rows = q_all("SELECT u.*, (SELECT COUNT(*) FROM progress p JOIN rumahs r ON r.id = p.rumah_id WHERE p.user_id = u.id AND p.completed_at IS NOT NULL AND r.kind IN ('practice','adaptive')) AS done
                       FROM users u WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy $dir LIMIT 1000", $params);
        $blokFilter = strtoupper((string) ($_GET['blok'] ?? ''));
        foreach ($rows as $i => &$u) {
            $prog = Practice::progressMap((int) $u['id']);
            $u['bloks'] = implode('', array_filter(['A', 'B', 'C', 'D'], fn($c) => Practice::blokCompleted((int) $u['id'], $c, $prog)));
            $u['sims'] = q_all("SELECT r.number, t.section, t.correct, t.total FROM attempts t JOIN rumahs r ON r.id = t.rumah_id WHERE t.user_id = ? AND r.kind = 'simulation' AND t.finished_at IS NOT NULL ORDER BY t.id DESC LIMIT 6", [$u['id']]);
            $u['estimate'] = ScoreEstimator::estimate((int) $u['id'])['total'];
            if ($blokFilter !== '' && !str_contains($u['bloks'], $blokFilter)) {
                unset($rows[$i]);
            }
        }
        unset($u);
        return array_values($rows);
    }

    public function users(): void
    {
        $this->page('users', ['title' => 'Pengguna', 'users' => $this->userRows()]);
    }

    public function usersCsv(): void
    {
        $rows = $this->userRows();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pengguna-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['username', 'role', 'tanggal_daftar', 'terakhir_aktif', 'rumah_selesai', 'blok_selesai', 'perkiraan_skor', 'bahasa', 'plan', 'status']);
        foreach ($rows as $u) {
            fputcsv($out, [$u['username'], $u['role'], $u['created_at'], $u['last_active_at'], $u['done'] . '/14', $u['bloks'], $u['estimate'], $u['lang'], $u['plan'], $u['status']]);
        }
        fclose($out);
        exit;
    }

    public function userDetail(string $id): void
    {
        $u = q_one('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$u) {
            abort(404);
        }
        $attempts = q_all('SELECT t.*, r.title_id, r.slug FROM attempts t JOIN rumahs r ON r.id = t.rumah_id WHERE t.user_id = ? ORDER BY t.id DESC LIMIT 100', [$u['id']]);
        $answers = q_all('SELECT a.*, q.section, q.type, q.prompt, q.correct_index, r.slug FROM answers a JOIN questions q ON q.id = a.question_id JOIN rumahs r ON r.id = q.rumah_id
                          WHERE a.user_id = ? ORDER BY a.id DESC LIMIT 300', [$u['id']]);
        $logins = q_all('SELECT * FROM login_attempts WHERE username = ? ORDER BY id DESC LIMIT 30', [$u['username_lower']]);
        $once = $_SESSION['admin_once'] ?? null;
        unset($_SESSION['admin_once']);
        $this->page('user', [
            'title'    => $u['username'],
            'u'        => $u,
            'progress' => Practice::progressMap((int) $u['id']),
            'attempts' => $attempts,
            'answers'  => $answers,
            'book'     => Practice::mistakeBook((int) $u['id']),
            'logins'   => $logins,
            'estimate' => ScoreEstimator::estimate((int) $u['id']),
            'once'     => $once,
        ]);
    }

    public function userAction(string $id): void
    {
        $u = q_one('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$u) {
            abort(404);
        }
        $action = (string) input('action');
        $self = (int) $u['id'] === (int) $this->admin['id'];
        switch ($action) {
            case 'reset_password':
                $temp = 'Rumah-' . Auth::newRecoveryCode() . '!' . random_int(10, 99);
                q('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', [Auth::hash($temp), $u['id']]);
                q('DELETE FROM remember_tokens WHERE user_id = ?', [$u['id']]);
                $_SESSION['admin_once'] = ['label' => 'Password sementara (tampil sekali). Pengguna wajib menggantinya saat login berikutnya:', 'value' => $temp];
                admin_log('reset_password', $u['username']);
                break;
            case 'recovery':
                $code = Auth::setRecovery((int) $u['id']);
                $_SESSION['admin_once'] = ['label' => 'Kode pemulihan baru (tampil sekali):', 'value' => $code];
                admin_log('new_recovery_code', $u['username']);
                break;
            case 'toggle_status':
                if ($self) {
                    flash('error', 'Tidak bisa menonaktifkan akun sendiri.');
                    break;
                }
                $new = $u['status'] === 'active' ? 'disabled' : 'active';
                q('UPDATE users SET status = ? WHERE id = ?', [$new, $u['id']]);
                if ($new === 'disabled') {
                    q('DELETE FROM remember_tokens WHERE user_id = ?', [$u['id']]);
                }
                admin_log('set_status', $u['username'], ['status' => $new]);
                flash('success', 'Status diubah menjadi ' . $new . '.');
                break;
            case 'plan':
                $plan = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) input('plan'))) ?: 'free';
                q('UPDATE users SET plan = ? WHERE id = ?', [substr($plan, 0, 20), $u['id']]);
                admin_log('set_plan', $u['username'], ['plan' => $plan]);
                flash('success', 'Plan disimpan.');
                break;
            case 'delete':
                if ($self || $u['role'] === 'admin') {
                    flash('error', 'Akun admin tidak bisa dihapus.');
                    break;
                }
                if (strtolower((string) input('confirm')) !== $u['username_lower']) {
                    flash('error', 'Ketik username dengan benar untuk menghapus.');
                    break;
                }
                q('DELETE FROM users WHERE id = ?', [$u['id']]);
                admin_log('delete_user', $u['username']);
                flash('success', 'Pengguna dihapus.');
                redirect('/admin/pengguna');
        }
        redirect('/admin/pengguna/' . $u['id']);
    }

    /* ================= Soal ================= */

    public function questions(): void
    {
        $f = [
            'rumah' => (string) ($_GET['rumah'] ?? ''), 'section' => (string) ($_GET['section'] ?? ''), 'type' => (string) ($_GET['type'] ?? ''),
            'tag' => (string) ($_GET['tag'] ?? ''), 'status' => (string) ($_GET['status'] ?? ''), 'blok' => (string) ($_GET['blok'] ?? ''),
        ];
        $where = ['1=1'];
        $p = [];
        if ($f['rumah'] !== '') { $where[] = 'r.slug = ?'; $p[] = $f['rumah']; }
        if ($f['blok'] !== '') { $where[] = 'b.code = ?'; $p[] = strtoupper($f['blok']); }
        if ($f['section'] !== '') { $where[] = 'q.section = ?'; $p[] = $f['section']; }
        if ($f['type'] !== '') { $where[] = 'q.type = ?'; $p[] = $f['type']; }
        if ($f['status'] !== '') { $where[] = 'q.status = ?'; $p[] = $f['status']; }
        if ($f['tag'] !== '') { $where[] = 'JSON_CONTAINS(q.rule_tags, JSON_QUOTE(?))'; $p[] = $f['tag']; }
        $rows = q_all('SELECT q.id, q.section, q.type, q.number, q.prompt, q.status, q.difficulty, q.rule_tags, q.correct_index, r.slug, b.code AS blok,
                         (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id AND a.chosen_index IS NOT NULL) AS n,
                         (SELECT AVG(is_correct) FROM answers a WHERE a.question_id = q.id AND a.chosen_index IS NOT NULL) AS pct
                       FROM questions q JOIN rumahs r ON r.id = q.rumah_id LEFT JOIN bloks b ON b.id = r.blok_id
                       WHERE ' . implode(' AND ', $where) . ' ORDER BY r.sort, q.section, q.number LIMIT 800', $p);
        $this->page('questions', ['title' => 'Soal', 'rows' => $rows, 'f' => $f, 'rumahs' => Rumah::all(), 'tags' => RuleTag::map()]);
    }

    public function questionForm(?string $id = null): void
    {
        $q = $id ? Question::find((int) $id) : null;
        if ($id && !$q) {
            abort(404);
        }
        $errors = [];
        if (is_post()) {
            $data = $this->questionFromPost();
            $errors = $this->validateQuestion($data);
            if (!$errors) {
                $cols = ['rumah_id', 'passage_id', 'section', 'type', 'subtype', 'number', 'prompt', 'options', 'correct_index', 'why_correct_id', 'why_correct_en', 'why_wrong_id', 'why_wrong_en',
                    'analogy_id', 'analogy_en', 'tip_id', 'tip_en', 'key_sentence', 'key_translation_id', 'liaison_note', 'evidence_quote', 'rule_tags', 'difficulty', 'status'];
                $vals = array_map(fn($c) => is_array($data[$c]) ? json_encode($data[$c], JSON_UNESCAPED_UNICODE) : $data[$c], array_combine($cols, $cols));
                try {
                    if ($q) {
                        $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
                        q("UPDATE questions SET $set WHERE id = ?", [...array_values($vals), $q['id']]);
                        $qid = (int) $q['id'];
                        admin_log('edit_question', (string) $qid);
                    } else {
                        q('INSERT INTO questions (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($vals));
                        $qid = (int) db()->lastInsertId();
                        admin_log('create_question', (string) $qid);
                    }
                    if ($data['section'] === 'listening') {
                        $this->ensureQuestionAudio($qid);
                    }
                    flash('success', 'Soal disimpan.');
                    redirect('/admin/soal/' . $qid);
                } catch (PDOException $e) {
                    $errors[] = str_contains($e->getMessage(), 'uq_q_number') ? 'Nomor soal sudah dipakai di Rumah + seksi ini.' : $e->getMessage();
                }
            }
            $q = array_merge($q ?? [], $data);
        }
        $passages = q_all('SELECT p.id, p.section, p.part, p.title, p.sort, r.slug FROM passages p JOIN rumahs r ON r.id = p.rumah_id ORDER BY r.sort, p.section, p.sort');
        $stats = null;
        $audio = null;
        if ($q && !empty($q['id'])) {
            $stats = [
                'n' => (int) q_val('SELECT COUNT(*) FROM answers WHERE question_id = ? AND chosen_index IS NOT NULL', [$q['id']]),
                'pct' => q_val('SELECT AVG(is_correct) FROM answers WHERE question_id = ? AND chosen_index IS NOT NULL', [$q['id']]),
                'dist' => q_all('SELECT chosen_index, COUNT(*) n FROM answers WHERE question_id = ? AND chosen_index IS NOT NULL GROUP BY chosen_index', [$q['id']]),
            ];
            if (!empty($q['audio_question_id'])) {
                $audio = q_val('SELECT path FROM audio_files WHERE id = ?', [$q['audio_question_id']]);
            }
        }
        $previewPassage = ($q && !empty($q['passage_id'])) ? (Question::passages([$q])[(int) $q['passage_id']] ?? null) : null;
        $this->page('question-form', ['title' => $q && !empty($q['id']) ? 'Edit soal #' . $q['id'] : 'Soal baru', 'q' => $q, 'errors' => $errors,
            'rumahs' => Rumah::all(), 'passages' => $passages, 'tags' => RuleTag::map(), 'stats' => $stats, 'audio' => $audio, 'previewPassage' => $previewPassage]);
    }

    private function questionFromPost(): array
    {
        $opts = array_map(fn($i) => trim((string) ($_POST['options'][$i] ?? '')), [0, 1, 2, 3]);
        $ci = max(0, min(3, (int) input('correct_index')));
        $wwId = [];
        $wwEn = [];
        foreach ([0, 1, 2, 3] as $i) {
            if ($i === $ci) {
                continue;
            }
            $wwId[(string) $i] = trim((string) ($_POST['why_wrong_id'][$i] ?? ''));
            $wwEn[(string) $i] = trim((string) ($_POST['why_wrong_en'][$i] ?? ''));
        }
        $tags = array_values(array_filter(array_map('trim', explode(',', (string) input('rule_tags')))));
        $section = (string) input('section');
        $rumah = Rumah::bySlug((string) input('rumah'));
        $nil = fn($v) => trim((string) $v) === '' ? null : trim((string) $v);
        return [
            'rumah_id' => $rumah['id'] ?? 0, 'passage_id' => ((int) input('passage_id')) ?: null, 'section' => $section, 'type' => (string) input('type'),
            'subtype' => $nil(input('subtype')), 'number' => (int) input('number'), 'prompt' => (string) ($_POST['prompt'] ?? ''), 'options' => $opts,
            'correct_index' => $ci, 'why_correct_id' => (string) input('why_correct_id'), 'why_correct_en' => (string) input('why_correct_en'),
            'why_wrong_id' => $wwId, 'why_wrong_en' => $wwEn, 'analogy_id' => (string) input('analogy_id'), 'analogy_en' => (string) input('analogy_en'),
            'tip_id' => (string) input('tip_id'), 'tip_en' => (string) input('tip_en'), 'key_sentence' => $nil(input('key_sentence')),
            'key_translation_id' => $nil(input('key_translation_id')), 'liaison_note' => $nil(input('liaison_note')), 'evidence_quote' => $nil(input('evidence_quote')),
            'rule_tags' => $tags, 'difficulty' => max(1, min(3, (int) input('difficulty', 1))), 'status' => input('status') === 'draft' ? 'draft' : 'published',
        ];
    }

    private function validateQuestion(array $d): array
    {
        $e = [];
        if (!$d['rumah_id']) $e[] = 'Pilih Rumah.';
        if (!isset(ContentImporter::TYPES[$d['section']]) || !in_array($d['type'], ContentImporter::TYPES[$d['section']] ?? [], true)) $e[] = 'Tipe tidak cocok dengan seksi.';
        if ($d['number'] < 1) $e[] = 'Nomor soal wajib ≥ 1.';
        if (count(array_filter($d['options'])) !== 4) $e[] = 'Isi keempat pilihan.';
        if ($d['status'] === 'published') {
            foreach (['why_correct_id', 'why_correct_en', 'analogy_id', 'analogy_en', 'tip_id', 'tip_en'] as $f) {
                if (trim((string) $d[$f]) === '') $e[] = "Field $f wajib diisi untuk soal terbit.";
            }
            foreach (['why_wrong_id', 'why_wrong_en'] as $f) {
                foreach ($d[$f] as $k => $v) {
                    if ($v === '') $e[] = "$f untuk pilihan " . 'ABCD'[(int) $k] . ' wajib diisi.';
                }
            }
        }
        if (count($d['rule_tags']) < 1 || count($d['rule_tags']) > 2) $e[] = 'Isi 1-2 tag aturan.';
        foreach ($d['rule_tags'] as $t) {
            if (!isset(RuleTag::map()[$t])) $e[] = "Tag $t tidak dikenal.";
        }
        if (in_array($d['section'], ['listening', 'reading'], true) && !$d['passage_id']) $e[] = 'Soal listening/reading wajib memilih grup/teks.';
        if ($d['type'] === 'structure_error' && !preg_match('/\{A\}.+\{\/A\}.*\{B\}.+\{\/B\}.*\{C\}.+\{\/C\}.*\{D\}.+\{\/D\}/s', $d['prompt'])) $e[] = 'Prompt error recognition wajib memuat {A}…{/A} sampai {D}…{/D}.';
        if ($d['type'] === 'structure_completion' && !str_contains($d['prompt'], '______')) $e[] = 'Prompt completion wajib memuat ______.';
        if ($d['section'] === 'reading' && !in_array($d['subtype'], ContentImporter::READING_SUBTYPES, true)) $e[] = 'Pilih subtype reading.';
        return $e;
    }

    private function ensureQuestionAudio(int $qid): void
    {
        $q = q_one('SELECT q.*, r.slug, p.sort AS psort FROM questions q JOIN rumahs r ON r.id = q.rumah_id LEFT JOIN passages p ON p.id = q.passage_id WHERE q.id = ?', [$qid]);
        if (!$q || $q['audio_question_id']) {
            return;
        }
        $path = sprintf('audio/%s/L-%s-g%02d-q%d.mp3', $q['slug'], $q['slug'], (int) $q['psort'], 100 + $qid);
        q('INSERT IGNORE INTO audio_files (path) VALUES (?)', [$path]);
        q('UPDATE questions SET audio_question_id = (SELECT id FROM audio_files WHERE path = ?) WHERE id = ?', [$path, $qid]);
    }

    public function questionAction(string $id): void
    {
        $q = Question::find((int) $id);
        if (!$q) {
            abort(404);
        }
        switch ((string) input('action')) {
            case 'duplicate':
                $next = (int) q_val('SELECT COALESCE(MAX(number),0) + 1 FROM questions WHERE rumah_id = ? AND section = ?', [$q['rumah_id'], $q['section']]);
                q('INSERT INTO questions (rumah_id, passage_id, section, type, subtype, number, prompt, options, underlines, correct_index, why_correct_id, why_correct_en, why_wrong_id, why_wrong_en,
                   analogy_id, analogy_en, tip_id, tip_en, key_sentence, key_translation_id, liaison_note, evidence_quote, rule_tags, difficulty, status)
                   SELECT rumah_id, passage_id, section, type, subtype, ?, prompt, options, underlines, correct_index, why_correct_id, why_correct_en, why_wrong_id, why_wrong_en,
                   analogy_id, analogy_en, tip_id, tip_en, key_sentence, key_translation_id, liaison_note, evidence_quote, rule_tags, difficulty, \'draft\' FROM questions WHERE id = ?', [$next, $q['id']]);
                $new = (int) db()->lastInsertId();
                admin_log('duplicate_question', (string) $q['id'], ['new' => $new]);
                flash('success', 'Soal diduplikasi sebagai draft.');
                redirect('/admin/soal/' . $new);
            case 'toggle':
                $st = $q['status'] === 'published' ? 'draft' : 'published';
                q('UPDATE questions SET status = ? WHERE id = ?', [$st, $q['id']]);
                admin_log('set_question_status', (string) $q['id'], ['status' => $st]);
                flash('success', $st === 'draft' ? 'Soal dinonaktifkan (draft).' : 'Soal diterbitkan.');
                break;
            case 'delete':
                q('DELETE FROM questions WHERE id = ?', [$q['id']]);
                admin_log('delete_question', (string) $q['id']);
                flash('success', 'Soal dihapus.');
                redirect('/admin/soal');
        }
        redirect('/admin/soal/' . $q['id']);
    }

    /* ================= Grup listening & teks reading ================= */

    public function passages(): void
    {
        $rows = q_all('SELECT p.*, r.slug, af.path AS audio_path, (SELECT COUNT(*) FROM questions q WHERE q.passage_id = p.id) AS qn
                       FROM passages p JOIN rumahs r ON r.id = p.rumah_id LEFT JOIN audio_files af ON af.id = p.audio_talk_id ORDER BY r.sort, p.section, p.sort');
        $this->page('passages', ['title' => 'Grup Listening & Teks Reading', 'rows' => $rows]);
    }

    public function passageForm(?string $id = null): void
    {
        $p = $id ? q_one('SELECT p.*, r.slug, af.path AS audio_path FROM passages p JOIN rumahs r ON r.id = p.rumah_id LEFT JOIN audio_files af ON af.id = p.audio_talk_id WHERE p.id = ?', [(int) $id]) : null;
        if ($id && !$p) {
            abort(404);
        }
        $errors = [];
        if (is_post()) {
            $rumah = Rumah::bySlug((string) input('rumah'));
            $section = input('section') === 'reading' ? 'reading' : 'listening';
            $script = [];
            foreach (preg_split('/\R/', (string) ($_POST['script'] ?? '')) as $ln => $line) {
                if (trim($line) === '') continue;
                if (!preg_match('/^\s*(N|LEC|[MW][1-4])\s*:\s*(.+)$/', $line, $m)) {
                    $errors[] = 'Baris naskah ' . ($ln + 1) . ' harus berformat "W1: teks" (speaker N, M1-M4, W1-W4, LEC).';
                    continue;
                }
                $script[] = ['speaker' => $m[1], 'text' => trim($m[2])];
            }
            if (!$rumah) $errors[] = 'Pilih Rumah.';
            if ($section === 'listening' && !$script) $errors[] = 'Naskah listening wajib diisi.';
            if ($section === 'reading' && trim((string) ($_POST['body'] ?? '')) === '') $errors[] = 'Teks reading wajib diisi.';
            if (!$errors) {
                $sort = (int) input('sort') ?: ((int) q_val('SELECT COALESCE(MAX(sort),0)+1 FROM passages WHERE rumah_id = ? AND section = ?', [$rumah['id'], $section]));
                $part = $section === 'listening' ? (in_array(input('part'), ['A', 'B', 'C'], true) ? input('part') : 'A') : null;
                $params = [$rumah['id'], $section, $part, (string) input('title') ?: null, $section === 'reading' ? (string) $_POST['body'] : null,
                    $section === 'listening' ? json_encode($script, JSON_UNESCAPED_UNICODE) : null, $sort];
                if ($p) {
                    q('UPDATE passages SET rumah_id = ?, section = ?, part = ?, title = ?, body = ?, script = ?, sort = ? WHERE id = ?', [...$params, $p['id']]);
                    $pid = (int) $p['id'];
                } else {
                    q('INSERT INTO passages (rumah_id, section, part, title, body, script, sort) VALUES (?, ?, ?, ?, ?, ?, ?)', $params);
                    $pid = (int) db()->lastInsertId();
                }
                if ($section === 'listening') {
                    $path = sprintf('audio/%s/L-%s-g%02d-talk.mp3', $rumah['slug'], $rumah['slug'], $sort);
                    q('INSERT IGNORE INTO audio_files (path) VALUES (?)', [$path]);
                    q('UPDATE passages SET audio_talk_id = (SELECT id FROM audio_files WHERE path = ?) WHERE id = ?', [$path, $pid]);
                }
                admin_log($p ? 'edit_passage' : 'create_passage', (string) $pid);
                flash('success', 'Grup disimpan.');
                redirect('/admin/grup/' . $pid);
            }
        }
        $questions = $p ? q_all('SELECT q.id, q.number, q.prompt, q.type, af.path AS audio_path FROM questions q LEFT JOIN audio_files af ON af.id = q.audio_question_id WHERE q.passage_id = ? ORDER BY q.number', [$p['id']]) : [];
        $this->page('passage-form', ['title' => $p ? 'Edit grup #' . $p['id'] : 'Grup baru', 'p' => $p, 'errors' => $errors, 'rumahs' => Rumah::all(), 'questions' => $questions]);
    }

    public function audioUpload(): void
    {
        $afId = (int) input('audio_id');
        $af = q_one('SELECT * FROM audio_files WHERE id = ?', [$afId]);
        $back = safe_next((string) input('back'), '/admin/grup');
        if (!$af) {
            flash('error', 'Target audio tidak ditemukan.');
            redirect($back);
        }
        $f = $_FILES['mp3'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Upload gagal.');
            redirect($back);
        }
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if ($ext !== 'mp3' || !in_array($mime, ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg'], true) || $f['size'] > 5 * 1024 * 1024) {
            flash('error', 'Hanya file MP3 (audio/mpeg) maksimal 5 MB.');
            redirect($back);
        }
        // Nama file ditentukan server dari path yang terdaftar (bukan nama upload)
        if (!preg_match('#^audio/[a-z0-9]+/[A-Za-z0-9\-]+\.mp3$#', $af['path'])) {
            flash('error', 'Path audio tidak valid.');
            redirect($back);
        }
        $dest = PUBLIC_PATH . '/' . $af['path'];
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            flash('error', 'Gagal menyimpan file.');
            redirect($back);
        }
        q('UPDATE audio_files SET hash = ?, uploaded_by = ?, duration_ms = NULL WHERE id = ?', [hash_file('sha256', $dest), $this->admin['id'], $af['id']]);
        admin_log('upload_audio', $af['path']);
        flash('success', 'Audio diunggah: ' . $af['path']);
        redirect($back);
    }

    /* ================= Impor / ekspor ================= */

    public function import(): void
    {
        $result = null;
        $json = '';
        if (is_post()) {
            $json = (string) ($_POST['json'] ?? '');
            if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $json = (string) file_get_contents($_FILES['file']['tmp_name']);
            }
            $commit = input('mode') === 'import';
            $imp = new ContentImporter();
            $result = $imp->importJsonString($json, true);
            $result['committed'] = false;
            if ($commit && !$result['errors']) {
                $result = $imp->importJsonString($json, false);
                $result['committed'] = !$result['errors'];
                admin_log('import_json', null, ['questions' => $result['questions']]);
            }
        }
        $this->page('import', ['title' => 'Impor / Ekspor', 'result' => $result, 'json' => $json, 'rumahs' => Rumah::all()]);
    }

    public function export(string $slug): void
    {
        $r = Rumah::bySlug($slug);
        if (!$r) {
            abort(404);
        }
        $items = [];
        $byPassage = [];
        foreach (q_all('SELECT * FROM questions WHERE rumah_id = ? ORDER BY section, number', [$r['id']]) as $q) {
            $q = Question::hydrate($q);
            $out = array_filter([
                'section' => $q['section'], 'type' => $q['type'], 'subtype' => $q['subtype'], 'number' => (int) $q['number'], 'difficulty' => (int) $q['difficulty'],
                'prompt' => $q['prompt'], 'options' => $q['options'], 'correct_index' => (int) $q['correct_index'], 'rule_tags' => $q['rule_tags'],
                'why_correct_id' => $q['why_correct_id'], 'why_correct_en' => $q['why_correct_en'], 'why_wrong_id' => $q['why_wrong_id'], 'why_wrong_en' => $q['why_wrong_en'],
                'analogy_id' => $q['analogy_id'], 'analogy_en' => $q['analogy_en'], 'tip_id' => $q['tip_id'], 'tip_en' => $q['tip_en'],
                'key_sentence' => $q['key_sentence'], 'key_translation_id' => $q['key_translation_id'], 'liaison_note' => $q['liaison_note'],
                'evidence_quote' => $q['evidence_quote'], 'status' => $q['status'] === 'draft' ? 'draft' : null,
            ], fn($v) => $v !== null && $v !== '');
            $out['correct_index'] = (int) $q['correct_index'];
            if ($q['passage_id']) {
                unset($out['section']);
                $byPassage[(int) $q['passage_id']][] = $out;
            } else {
                $items[] = $out;
            }
        }
        $groups = [];
        foreach (q_all('SELECT * FROM passages WHERE rumah_id = ? ORDER BY section, sort', [$r['id']]) as $p) {
            $g = ['section' => $p['section']];
            if ($p['part']) $g['part'] = $p['part'];
            if ($p['title']) $g['title'] = $p['title'];
            if ($p['section'] === 'reading') $g['body'] = $p['body'];
            else $g['script'] = json_col($p['script']);
            $g['questions'] = $byPassage[(int) $p['id']] ?? [];
            $groups[] = $g;
        }
        $listening = array_values(array_filter($groups, fn($g) => $g['section'] === 'listening'));
        $reading = array_values(array_filter($groups, fn($g) => $g['section'] === 'reading'));
        $doc = ['rumah' => $r['slug'], 'material_id' => $r['material_id'], 'material_en' => $r['material_en'], 'items' => array_merge($listening, $items, $reading)];
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $r['slug'] . '.json"');
        echo json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function exportSql(): void
    {
        admin_log('export_sql');
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="perumahaninggris-' . date('Y-m-d-His') . '.sql"');
        $pdo = db();
        echo "-- PerumahanInggris backup " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
        foreach (q_all('SHOW TABLES') as $row) {
            $table = array_values($row)[0];
            $create = q_one("SHOW CREATE TABLE `$table`");
            echo "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n\n";
            $st = $pdo->query("SELECT * FROM `$table`");
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $r);
                echo "INSERT INTO `$table` (`" . implode('`,`', array_keys($r)) . '`) VALUES (' . implode(',', $vals) . ");\n";
            }
            echo "\n";
        }
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        exit;
    }

    /* ================= Kamus ================= */

    public function glossary(): void
    {
        if (is_post()) {
            $action = (string) input('action');
            $id = (int) input('id');
            if ($action === 'delete' && $id) {
                q('DELETE FROM glossary WHERE id = ?', [$id]);
                admin_log('delete_glossary', (string) $id);
                flash('success', 'Istilah dihapus.');
            } else {
                $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) input('slug')));
                $f = ['grp' => (string) input('grp'), 'term_en' => (string) input('term_en'), 'meaning_id' => (string) input('meaning_id'), 'def_id' => (string) input('def_id'),
                    'def_en' => (string) input('def_en'), 'example' => (string) input('example'), 'analogy_id' => (string) input('analogy_id'), 'analogy_en' => (string) input('analogy_en'), 'sort' => (int) input('sort')];
                if ($slug === '' || $f['term_en'] === '' || $f['meaning_id'] === '' || !in_array($f['grp'], ['pelaku', 'kelas', 'bagian', 'waktu', 'aturan'], true)) {
                    flash('error', 'Slug, grup, istilah, dan arti wajib diisi.');
                } elseif ($id) {
                    q('UPDATE glossary SET slug = ?, grp = ?, term_en = ?, meaning_id = ?, def_id = ?, def_en = ?, example = ?, analogy_id = ?, analogy_en = ?, sort = ? WHERE id = ?', [$slug, ...array_values($f), $id]);
                    admin_log('edit_glossary', $slug);
                    flash('success', 'Istilah disimpan.');
                } else {
                    q('INSERT INTO glossary (slug, grp, term_en, meaning_id, def_id, def_en, example, analogy_id, analogy_en, sort) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$slug, ...array_values($f)]);
                    admin_log('create_glossary', $slug);
                    flash('success', 'Istilah ditambahkan.');
                }
            }
            redirect('/admin/kamus' . ($id && $action !== 'delete' ? '?edit=' . $id : ''));
        }
        $edit = isset($_GET['edit']) ? q_one('SELECT * FROM glossary WHERE id = ?', [(int) $_GET['edit']]) : null;
        $this->page('glossary', ['title' => 'Kamus', 'terms' => q_all('SELECT * FROM glossary ORDER BY grp, sort, term_en'), 'edit' => $edit]);
    }

    /* ================= Sponsor ================= */

    public function sponsors(): void
    {
        if (is_post()) {
            $action = (string) input('action');
            $id = (int) input('id');
            if ($action === 'delete' && $id) {
                q('DELETE FROM sponsors WHERE id = ?', [$id]);
                admin_log('delete_sponsor', (string) $id);
                flash('success', 'Sponsor dihapus.');
                redirect('/admin/sponsor');
            }
            $url = (string) input('url');
            if (!preg_match('#^https?://#i', $url) || trim((string) input('name')) === '') {
                flash('error', 'Nama dan URL (http/https) wajib diisi.');
                redirect('/admin/sponsor');
            }
            $logo = null;
            if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
                $logo = $this->saveLogo($_FILES['logo']);
                if ($logo === false) {
                    redirect('/admin/sponsor');
                }
            }
            $vals = [trim((string) input('name')), $url, (string) input('desc_id'), (string) input('desc_en'), input('is_pinned') ? 1 : 0, (int) input('sort_order')];
            if (input('is_pinned')) {
                q('UPDATE sponsors SET is_pinned = 0');
            }
            if ($id) {
                q('UPDATE sponsors SET name = ?, url = ?, desc_id = ?, desc_en = ?, is_pinned = ?, sort_order = ? WHERE id = ?', [...$vals, $id]);
                if ($logo) q('UPDATE sponsors SET logo_path = ? WHERE id = ?', [$logo, $id]);
                if (input('remove_logo')) q('UPDATE sponsors SET logo_path = NULL WHERE id = ?', [$id]);
            } else {
                q('INSERT INTO sponsors (name, url, desc_id, desc_en, is_pinned, sort_order, logo_path) VALUES (?, ?, ?, ?, ?, ?, ?)', [...$vals, $logo ?: null]);
            }
            admin_log($id ? 'edit_sponsor' : 'create_sponsor', (string) input('name'));
            flash('success', 'Sponsor disimpan.');
            redirect('/admin/sponsor');
        }
        $this->page('sponsors', ['title' => 'Sponsor', 'rows' => q_all('SELECT * FROM sponsors ORDER BY is_pinned DESC, sort_order, id')]);
    }

    /** @return string|false */
    private function saveLogo(array $f)
    {
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if ($f['size'] > 500 * 1024 || !(($ext === 'png' && $mime === 'image/png') || ($ext === 'svg' && in_array($mime, ['image/svg+xml', 'text/xml', 'text/plain', 'application/xml'], true)))) {
            flash('error', 'Logo hanya PNG atau SVG, maksimal 500 KB.');
            return false;
        }
        $dir = PUBLIC_PATH . '/assets/img/sponsors';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'sponsor-' . bin2hex(random_bytes(6)) . '.' . $ext;
        if ($ext === 'svg') {
            $clean = SvgSanitizer::clean((string) file_get_contents($f['tmp_name']));
            if ($clean === null) {
                flash('error', 'SVG tidak valid.');
                return false;
            }
            file_put_contents("$dir/$name", $clean);
        } else {
            move_uploaded_file($f['tmp_name'], "$dir/$name");
        }
        return 'assets/img/sponsors/' . $name;
    }

    /* ================= Pengaturan & log ================= */

    public function settings(): void
    {
        $errors = [];
        if (is_post()) {
            $table = json_decode((string) ($_POST['score_table'] ?? ''), true);
            $okTable = is_array($table) && isset($table['points'], $table['listening'], $table['structure'], $table['reading'])
                && count($table['points']) === count($table['listening']) && count($table['points']) === count($table['reading']) && count($table['points']) === count($table['structure']);
            if (!$okTable) {
                $errors[] = 'Tabel konversi harus JSON dengan kunci points, listening, structure, reading yang panjangnya sama.';
            }
            if (!$errors) {
                setting_save('score_table', $table);
                setting_save('default_target', max(310, min(677, (int) input('default_target', 500))));
                setting_save('registration_open', (bool) input('registration_open'));
                setting_save('announcement', ['id' => trim((string) input('announcement_id')), 'en' => trim((string) input('announcement_en'))]);
                admin_log('update_settings');
                flash('success', 'Pengaturan disimpan.');
                redirect('/admin/pengaturan');
            }
        }
        $ann = setting('announcement', []);
        $this->page('settings', [
            'title' => 'Pengaturan', 'errors' => $errors,
            'scoreTable' => (string) ($_POST['score_table'] ?? json_encode(ScoreEstimator::table(), JSON_PRETTY_PRINT)),
            'target' => (int) setting('default_target', 500), 'open' => setting('registration_open', true) !== false,
            'ann' => is_array($ann) ? $ann : [],
        ]);
    }

    public function log(): void
    {
        $rows = q_all('SELECT l.*, u.username FROM admin_log l LEFT JOIN users u ON u.id = l.admin_id ORDER BY l.id DESC LIMIT 300');
        $this->page('log', ['title' => 'Log admin', 'rows' => $rows]);
    }
}
