<?php
declare(strict_types=1);

final class ApiController
{
    private function apiUser(): array
    {
        $u = require_login();
        if (!rate_hit('api:' . $u['id'], 120, 60)) {
            json_response(['ok' => false, 'error' => 'rate_limited'], 429);
        }
        return $u;
    }

    public function attempt(string $id): void
    {
        $u = $this->apiUser();
        $a = Practice::ownedAttempt((int) $id, $u);
        json_response(Practice::attemptPayload($a, $u));
    }

    public function answer(): void
    {
        $u = $this->apiUser();
        $b = json_body();
        $a = Practice::ownedAttempt((int) ($b['attempt_id'] ?? 0), $u);
        $chosen = isset($b['chosen_index']) && $b['chosen_index'] !== null ? (int) $b['chosen_index'] : null;
        $res = Practice::answer($a, $u, (int) ($b['question_id'] ?? 0), $chosen, (int) ($b['time_ms'] ?? 0), !empty($b['flagged']), !empty($b['audio_missing']));
        json_response($res, $res['ok'] ? 200 : 422);
    }

    public function audioPlayed(): void
    {
        $u = $this->apiUser();
        $b = json_body();
        $a = Practice::ownedAttempt((int) ($b['attempt_id'] ?? 0), $u);
        $res = Practice::markAudioPlayed($a, (int) ($b['question_id'] ?? 0));
        json_response($res, $res['ok'] ? 200 : 422);
    }

    public function flag(): void
    {
        $u = $this->apiUser();
        $b = json_body();
        $a = Practice::ownedAttempt((int) ($b['attempt_id'] ?? 0), $u);
        if (!$a['finished_at']) {
            Practice::flag($a, (int) ($b['question_id'] ?? 0), !empty($b['flagged']));
        }
        json_response(['ok' => true]);
    }

    public function tick(string $id): void
    {
        $u = require_login();
        $a = Practice::ownedAttempt((int) $id, $u);
        if ($a['finished_at'] || $a['mode'] !== 'simulation') {
            json_response(['ok' => true, 'remaining_s' => null]);
        }
        $remaining = Practice::simTick($a);
        if ($remaining <= 0) {
            $r = Practice::finish(Practice::attempt((int) $a['id']), $u);
            json_response(['ok' => true, 'remaining_s' => 0, 'finished' => true, 'redirect' => $r['redirect']]);
        }
        json_response(['ok' => true, 'remaining_s' => $remaining]);
    }

    public function finish(string $id): void
    {
        $u = require_login();
        $a = Practice::ownedAttempt((int) $id, $u);
        if ($a['mode'] === 'simulation' && !$a['finished_at']) {
            Practice::simTick($a);
            $a = Practice::attempt((int) $a['id']);
        }
        json_response(Practice::finish($a, $u));
    }

    /* ---------- Rumah Contoh (tamu, tidak disimpan) ---------- */

    private static function sampleRumah(): array
    {
        $r = Rumah::bySlug('contoh');
        if (!$r) {
            json_response(['ok' => false, 'error' => 'no_sample'], 404);
        }
        return $r;
    }

    public function sample(): void
    {
        $r = self::sampleRumah();
        $ids = [];
        foreach (Practice::SECTIONS as $s) {
            array_push($ids, ...Practice::sectionQuestionIds((int) $r['id'], $s));
        }
        $data = Practice::clientQuestions($ids, 'practice');
        json_response(['ok' => true, 'attempt' => ['id' => 0, 'mode' => 'sample', 'section' => 'mixed', 'finished' => false, 'remaining_s' => null, 'tempo' => 0, 'sim_answer_s' => 12]] + $data + ['answers' => (object) []]);
    }

    public function sampleAnswer(): void
    {
        if (!rate_hit('sample:' . ip_hash(), 120, 60)) {
            json_response(['ok' => false, 'error' => 'rate_limited'], 429);
        }
        $r = self::sampleRumah();
        $b = json_body();
        $q = Question::find((int) ($b['question_id'] ?? 0));
        if (!$q || (int) $q['rumah_id'] !== (int) $r['id'] || $q['status'] !== 'published') {
            json_response(['ok' => false, 'error' => 'not_found'], 404);
        }
        $chosen = isset($b['chosen_index']) ? max(0, min(3, (int) $b['chosen_index'])) : null;
        if ($chosen === null) {
            json_response(['ok' => false, 'error' => 'no_choice'], 422);
        }
        json_response(['ok' => true] + Practice::reveal($q, $chosen));
    }

    public function usernameCheck(): void
    {
        if (!rate_hit('ucheck:' . ip_hash(), 60, 60)) {
            json_response(['ok' => false], 429);
        }
        $u = (string) ($_GET['u'] ?? '');
        $errs = Auth::validateUsername($u);
        $out = ['ok' => true, 'valid' => !$errs, 'message' => $errs ? t($errs[0]) : t('auth.username_ok')];
        if (in_array('auth.err.username_taken', $errs, true)) {
            $s = Auth::suggestUsernames($u);
            $out['message'] = t('auth.err.username_taken_suggest', ['a' => $s[0] ?? $u . '_1', 'b' => $s[1] ?? $u . '2026']);
        }
        json_response($out);
    }
}
