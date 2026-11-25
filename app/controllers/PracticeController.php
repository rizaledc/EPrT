<?php
declare(strict_types=1);

final class PracticeController
{
    public function map(): void
    {
        $u = require_login();
        $progress = Practice::progressMap((int) $u['id']);
        $rumahs = Rumah::all();
        $bloks = q_all('SELECT * FROM bloks ORDER BY sort');
        $practiceRumahs = array_filter($rumahs, fn($r) => in_array($r['kind'], ['practice', 'adaptive'], true));
        $done = count(array_filter($practiceRumahs, fn($r) => !empty($progress[(int) $r['id']]['completed_at'])));
        $next = Practice::suggestedNext($progress);
        $continue = Practice::continueTarget((int) $u['id'], $progress);
        $allBloksDone = true;
        foreach (['A', 'B', 'C', 'D'] as $c) {
            $allBloksDone = $allBloksDone && Practice::blokCompleted((int) $u['id'], $c, $progress);
        }
        $ann = setting('announcement', []);
        render('practice/map', [
            'title'     => t('map.title'),
            'user'      => $u,
            'progress'  => $progress,
            'rumahs'    => $rumahs,
            'bloks'     => $bloks,
            'doneCount' => $done,
            'totalCount'=> count($practiceRumahs),
            'next'      => $next,
            'continue'  => $continue,
            'allBloksDone' => $allBloksDone,
            'announcement' => is_array($ann) ? trim((string) ($ann[active_lang()] ?? $ann['id'] ?? '')) : '',
            'estimate'  => ScoreEstimator::estimate((int) $u['id']),
            'compactSponsors' => true,
            'noindex'   => true,
        ]);
    }

    public function rumah(string $blok, string $rumah): void
    {
        $u = require_login();
        $r = Rumah::fromRoute($blok, $rumah);
        if (!$r) {
            abort(404);
        }
        if (Rumah::isLegacyRoute($blok, $rumah)) {
            redirect(Rumah::url($r), 301);
        }
        $status = [];
        foreach (Practice::SECTIONS as $s) {
            $status[$s] = Practice::sectionStatus((int) $u['id'], $r, $s);
        }
        $progress = Practice::progressMap((int) $u['id'])[(int) $r['id']] ?? null;
        render('practice/rumah', [
            'title'    => Rumah::title($r),
            'rumah'    => $r,
            'status'   => $status,
            'progress' => $progress,
            'threshold'=> Rumah::completionThreshold($r),
            'compactSponsors' => true,
            'noindex'  => true,
        ]);
    }

    public function session(string $blok, string $rumah, string $section): void
    {
        $u = require_login();
        $r = Rumah::fromRoute($blok, $rumah);
        if (!$r || !in_array($section, Practice::SECTIONS, true)) {
            abort(404);
        }
        if (Rumah::isLegacyRoute($blok, $rumah)) {
            redirect(Rumah::url($r, $section), 301);
        }
        $a = Practice::openAttempt($u, $r, $section, 'practice');
        if (!$a) {
            flash('info', t('session.no_questions'));
            redirect(Rumah::url($r));
        }
        $this->renderSession($a, $r, Rumah::url($r));
    }

    public function simulation(string $n): void
    {
        $u = require_login();
        $r = Rumah::bySlug('sim' . (int) $n);
        if (!$r) {
            abort(404);
        }
        $status = [];
        foreach (Practice::SECTIONS as $s) {
            $status[$s] = Practice::sectionStatus((int) $u['id'], $r, $s, 'simulation');
        }
        render('practice/simulation', ['title' => Rumah::title($r), 'rumah' => $r, 'status' => $status, 'noindex' => true, 'compactSponsors' => true]);
    }

    public function simulationSession(string $n, string $section): void
    {
        $u = require_login();
        $r = Rumah::bySlug('sim' . (int) $n);
        if (!$r || !in_array($section, Practice::SECTIONS, true)) {
            abort(404);
        }
        $a = Practice::openAttempt($u, $r, $section, 'simulation');
        if (!$a) {
            flash('info', t('session.no_questions'));
            redirect(Rumah::url($r));
        }
        $this->renderSession($a, $r, Rumah::url($r));
    }

    public function reviewSession(string $id): void
    {
        $u = require_login();
        $a = Practice::ownedAttempt((int) $id, $u);
        if ($a['finished_at']) {
            redirect('/hasil/' . $a['id']);
        }
        $r = Rumah::byId((int) $a['rumah_id']);
        if ($a['mode'] !== 'review') {
            redirect(Rumah::url($r, $a['section']));
        }
        $this->renderSession($a, $r, url('riwayat#kesalahan'));
    }

    private function renderSession(array $a, array $r, string $exitUrl): void
    {
        $mode = $a['mode'];
        $title = $mode === 'review' ? t('session.review_title') : Rumah::title($r);
        render('practice/session', [
            'title'      => $title . ' · ' . section_label($a['section']),
            'bodyClass'  => 'session-body sec-' . $a['section'],
            'hideNav'    => true,
            'hideFooter' => true,
            'noindex'    => true,
            'attempt'    => $a,
            'rumah'      => $r,
            'heading'    => $title,
            'exitUrl'    => $exitUrl,
            'scripts'    => ['engine'],
            'pageData'   => [
                'mode'      => $mode,
                'section'   => $a['section'],
                'attemptId' => (int) $a['id'],
                'endpoints' => [
                    'load'   => url('api/attempt/' . $a['id']),
                    'answer' => url('api/answer'),
                    'flag'   => url('api/flag'),
                    'audioPlayed' => url('api/audio-played'),
                    'tick'   => url('api/attempt/' . $a['id'] . '/tick'),
                    'finish' => url('api/attempt/' . $a['id'] . '/finish'),
                ],
                'exitUrl'   => $exitUrl,
                'i18n'      => self::engineStrings(),
                'lang'      => active_lang(),
            ],
        ]);
    }

    public static function engineStrings(): array
    {
        $keys = ['session.loading', 'session.next', 'session.prev', 'session.finish', 'session.finish_confirm_t', 'session.finish_confirm_d', 'session.finish_unanswered',
            'session.cancel', 'session.flag', 'session.flagged', 'session.saved', 'session.save_error', 'session.check', 'session.replay', 'session.transcript',
            'session.audio_missing', 'session.audio_tts', 'session.read_transcript', 'session.playing', 'session.play', 'session.audio_done', 'session.answer_time',
            'session.time_up', 'session.listen_first', 'session.passage', 'session.show_passage', 'session.hide_passage', 'session.question_of', 'session.start_t',
            'session.start_d', 'session.start_btn', 'session.sim_rules', 'session.practice_rules', 'session.result_t', 'session.see_result', 'session.sample_done_t',
            'session.sample_done_d', 'session.register_cta', 'session.offline', 'session.locked', 'session.timer_reminder', 'session.timer_toggle', 'session.directions',
            'session.question_audio', 'session.talk_audio', 'session.choose_first', 'common.correct', 'common.wrong', 'session.number', 'session.speed', 'session.tts_unavailable',
            'session.completed_rumah', 'session.continue_auto', 'session.retrying',
            'session.instr.error_chip', 'session.instr.error', 'session.instr.completion_chip', 'session.instr.completion', 'session.st_dir_t',
            'session.st_dir_completion', 'session.st_dir_error', 'session.st_dir_example', 'session.st_dir_answer', 'session.st_dir_ex_completion', 'session.st_dir_ex_error',
            'session.audio_played', 'session.audio_retry'];
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = t($k);
        }
        return $out;
    }

    public function sample(): void
    {
        $r = Rumah::bySlug('contoh');
        render('practice/session', [
            'title'      => t('sample.title'),
            'description'=> t('sample.meta'),
            'bodyClass'  => 'session-body sec-structure',
            'hideNav'    => true,
            'hideFooter' => true,
            'attempt'    => ['id' => 0, 'mode' => 'sample', 'section' => 'mixed'],
            'rumah'      => $r,
            'heading'    => t('sample.title'),
            'exitUrl'    => url('/'),
            'scripts'    => ['engine'],
            'pageData'   => [
                'mode'      => 'sample',
                'section'   => 'mixed',
                'attemptId' => 0,
                'endpoints' => ['load' => url('api/sample'), 'answer' => url('api/sample/answer')],
                'exitUrl'   => url('/'),
                'registerUrl' => url('daftar'),
                'i18n'      => self::engineStrings(),
                'lang'      => active_lang(),
            ],
        ]);
    }

    public function result(string $id): void
    {
        $u = require_login();
        $a = Practice::ownedAttempt((int) $id, $u, true);
        $r = Rumah::byId((int) $a['rumah_id']);
        if (!$a['finished_at'] && (int) $a['user_id'] === (int) $u['id']) {
            // Belum selesai: kembali ke sesi
            redirect($a['mode'] === 'review' ? '/sesi/' . $a['id'] : Rumah::url($r, $a['section']));
        }
        $qs = Question::many($a['question_ids']);
        $passages = Question::passages(array_values($qs));
        $answers = [];
        foreach (q_all('SELECT * FROM answers WHERE attempt_id = ?', [$a['id']]) as $row) {
            $answers[(int) $row['question_id']] = $row;
        }
        $nextSection = null;
        if ($a['mode'] !== 'review') {
            foreach (Practice::SECTIONS as $s) {
                if ($s === $a['section']) {
                    continue;
                }
                $st = Practice::sectionStatus((int) $a['user_id'], $r, $s, $a['mode'] === 'simulation' ? 'simulation' : 'practice');
                if ($st['state'] !== 'done' && ($st['total'] ?? 0) > 0) {
                    $nextSection = $s;
                    break;
                }
            }
        }
        $wrongCount = count(array_filter($a['question_ids'], fn($qid) => empty($answers[$qid]['is_correct'])));
        render('practice/result', [
            'title'    => t('result.title'),
            'attempt'  => $a,
            'rumah'    => $r,
            'questions'=> $qs,
            'passages' => $passages,
            'answers'  => $answers,
            'nextSection' => $nextSection,
            'wrongCount'  => $wrongCount,
            'progress' => Practice::progressMap((int) $a['user_id'])[(int) $r['id']] ?? null,
            'noindex'  => true,
            'compactSponsors' => true,
            'scripts'  => ['result'],
        ]);
    }

    public function retryWrong(string $id): void
    {
        $u = require_login();
        $a = Practice::ownedAttempt((int) $id, $u);
        $wrong = [];
        foreach ($a['question_ids'] as $qid) {
            $ans = q_one('SELECT is_correct FROM answers WHERE attempt_id = ? AND question_id = ?', [$a['id'], $qid]);
            if (!$ans || !(int) $ans['is_correct']) {
                $wrong[] = $qid;
            }
        }
        if (!$wrong) {
            flash('success', t('result.no_wrong'));
            redirect('/hasil/' . $a['id']);
        }
        $new = Practice::createAttempt((int) $u['id'], (int) $a['rumah_id'], $a['section'], 'review', Practice::groupOrder($wrong));
        redirect('/sesi/' . $new['id']);
    }

    public function drillTag(): void
    {
        $u = require_login();
        $tag = (string) input('tag');
        if (!preg_match('/^[A-Z0-9\-]{2,30}$/', $tag)) {
            abort(404);
        }
        $a = Practice::drillAttempt((int) $u['id'], $tag);
        if (!$a) {
            flash('info', t('history.drill_empty'));
            redirect('/riwayat#kesalahan');
        }
        redirect('/sesi/' . $a['id']);
    }

    public function history(): void
    {
        $u = require_login();
        $attempts = q_all('SELECT a.*, r.slug, r.kind, r.number, r.title_id, r.title_en, b.code AS blok_code FROM attempts a
                           JOIN rumahs r ON r.id = a.rumah_id LEFT JOIN bloks b ON b.id = r.blok_id
                           WHERE a.user_id = ? ORDER BY a.id DESC LIMIT 200', [$u['id']]);
        render('practice/history', [
            'title'    => t('history.title'),
            'attempts' => $attempts,
            'book'     => Practice::mistakeBook((int) $u['id']),
            'tags'     => RuleTag::map(),
            'noindex'  => true,
            'compactSponsors' => true,
        ]);
    }
}
