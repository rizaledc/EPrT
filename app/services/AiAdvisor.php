<?php
declare(strict_types=1);

/**
 * Rekomendasi belajar.
 * Angka target (jumlah benar per seksi) dihitung pasti di sini dari tabel konversi skor;
 * model AI hanya menulis ringkasan, tips, dan rencana belajar dari angka tersebut.
 */
final class AiAdvisor
{
    public const COST_COINS = 1;
    public const MAX_TOKENS = 1100;

    public static function enabled(): bool
    {
        return setting('ai_enabled', true) !== false && OpenRouter::configured();
    }

    public static function dailyLimit(): int
    {
        return max(1, (int) setting('ai_daily_limit', 5));
    }

    public static function usedToday(int $userId): int
    {
        return (int) q_val("SELECT COUNT(*) FROM ai_analyses WHERE user_id = ? AND status <> 'failed' AND created_at >= CURDATE()", [$userId]);
    }

    /** Jumlah benar sekarang (perkiraan) per seksi, dari ScoreEstimator. */
    public static function currentRaw(array $est): array
    {
        $raw = [];
        foreach (ScoreEstimator::MAX_RAW as $sec => $max) {
            $d = $est['sections'][$sec] ?? null;
            $raw[$sec] = $d ? (int) round($d['pct'] / 100 * $max) : 0;
        }
        return $raw;
    }

    /**
     * Target jumlah benar per seksi untuk mencapai skor target dengan kenaikan paling ringan dan merata.
     * @return array{met:bool,reachable:bool,target:int,current:array,raw:array,scaled:array,total:int,gain:array}
     */
    public static function plan(array $est, int $target): array
    {
        $cur = self::currentRaw($est);
        $scaledAt = [];
        foreach (ScoreEstimator::MAX_RAW as $sec => $max) {
            for ($r = 0; $r <= $max; $r++) {
                $scaledAt[$sec][$r] = ScoreEstimator::convert($sec, $r);
            }
        }
        $total = fn($l, $s, $r) => ScoreEstimator::total($scaledAt['listening'][$l], $scaledAt['structure'][$s], $scaledAt['reading'][$r]);
        $M = ScoreEstimator::MAX_RAW;
        $curTotal = $total($cur['listening'], $cur['structure'], $cur['reading']);
        $best = null;
        $bestCost = INF;
        if ($curTotal >= $target) {
            $best = $cur;
        } else {
            for ($l = $cur['listening']; $l <= $M['listening']; $l++) {
                $cl = (($l - $cur['listening']) / $M['listening']) ** 2;
                if ($cl >= $bestCost) {
                    break;
                }
                for ($s = $cur['structure']; $s <= $M['structure']; $s++) {
                    $cs = $cl + (($s - $cur['structure']) / $M['structure']) ** 2;
                    if ($cs >= $bestCost) {
                        break;
                    }
                    for ($r = $cur['reading']; $r <= $M['reading']; $r++) {
                        $c = $cs + (($r - $cur['reading']) / $M['reading']) ** 2;
                        if ($c >= $bestCost) {
                            break;
                        }
                        if ($total($l, $s, $r) >= $target) {
                            $best = ['listening' => $l, 'structure' => $s, 'reading' => $r];
                            $bestCost = $c;
                            break;
                        }
                    }
                }
            }
        }
        $reachable = $best !== null;
        $best = $best ?? $M;
        $scaled = [];
        $gain = [];
        foreach ($best as $sec => $r) {
            $scaled[$sec] = $scaledAt[$sec][$r];
            $gain[$sec] = $r - $cur[$sec];
        }
        return [
            'met'       => $curTotal >= $target,
            'reachable' => $reachable,
            'target'    => $target,
            'current'   => $cur,
            'current_total' => $curTotal,
            'raw'       => $best,
            'scaled'    => $scaled,
            'total'     => ScoreEstimator::total($scaled['listening'], $scaled['structure'], $scaled['reading']),
            'gain'      => $gain,
        ];
    }

    /** Data ringkas yang dikirim ke model (tanpa username atau data pribadi). */
    public static function input(int $userId, array $est, array $plan): array
    {
        $sections = [];
        foreach (ScoreEstimator::MAX_RAW as $sec => $max) {
            $d = $est['sections'][$sec] ?? null;
            $sections[$sec] = [
                'persen_benar'          => $d ? $d['pct'] : null,
                'skor_skala'            => $d ? $d['scaled'] : null,
                'soal_dijawab'          => $d ? $d['answered'] : 0,
                'benar_sekarang'        => $plan['current'][$sec] . ' dari ' . $max,
                'target_benar'          => $plan['raw'][$sec] . ' dari ' . $max,
                'skor_skala_target'     => $plan['scaled'][$sec],
                'tambahan_benar'        => $plan['gain'][$sec],
            ];
        }
        $weak = [];
        foreach (ScoreEstimator::weakestTags($userId, 6) as $w) {
            $weak[] = ['seksi' => $w['section'], 'topik' => $w['info'] ? tr($w['info'], 'name') : $w['tag'], 'jumlah_salah' => $w['wrong']];
        }
        $simDone = (int) q_val("SELECT COUNT(*) FROM attempts t JOIN rumahs r ON r.id = t.rumah_id WHERE t.user_id = ? AND r.kind = 'simulation' AND t.finished_at IS NOT NULL", [$userId]);
        return [
            'skor_perkiraan_sekarang' => $est['total'],
            'rentang'                 => [$est['low'], $est['high']],
            'skor_target'             => $plan['target'],
            'target_sudah_tercapai'   => $plan['met'],
            'target_bisa_dicapai'     => $plan['reachable'],
            'skor_jika_target_benar_tercapai' => $plan['total'],
            'seksi'                   => $sections,
            'topik_paling_sering_salah' => $weak,
            'seksi_simulasi_selesai'  => $simDone,
            'total_jawaban'           => $est['answers'],
            'skala'                   => 'Listening 31-68, Structure 31-68, Reading 31-67; total = (L+S+R) x 10 / 3',
        ];
    }

    public static function messages(array $input, string $lang): array
    {
        $en = $lang === 'en';
        $system = $en
            ? "You are a friendly study coach for a PBT-format English proficiency test (Listening, Structure, Reading) on the PerumahanInggris practice site. Write a personal recommendation in clear, casual English addressed to the learner as you.\n"
              . "Rules:\n- Use the numbers in the data exactly. Never recalculate or invent scores or targets.\n- Do not use quotation marks of any kind and do not use em dashes. To emphasize a word, use **bold**.\n- Never use the words house or block. Practice units are called Day and Level.\n- Do not mention the data format, JSON, or that you are an AI.\n"
              . "Format (markdown, at most 280 words):\n## Summary\n2 to 3 sentences: strongest and weakest section, current estimate versus target.\n## Target correct answers\nOne bullet per section: Listening, Structure, Reading, each with target correct out of the maximum, current correct, and the scaled score.\n## Tips\n3 to 5 bullets, specific to the most frequent mistake topics.\n## 7 day plan\n3 to 4 bullets such as Day 1-2: ...\n"
              . "If the target is already met, congratulate briefly and suggest a higher stretch goal. If the target cannot be reached, say so gently and give the best achievable plan."
            : "Kamu adalah pelatih belajar yang ramah untuk tes kemampuan bahasa Inggris format PBT (Listening, Structure, Reading) di situs latihan PerumahanInggris. Tulis rekomendasi personal dalam Bahasa Indonesia santai, sapa pembaca dengan kamu.\n"
              . "Aturan:\n- Pakai angka dari data apa adanya. Jangan menghitung ulang atau mengarang skor dan target.\n- Jangan memakai tanda kutip jenis apa pun dan jangan memakai tanda pisah panjang. Untuk menegaskan kata, pakai **tebal**.\n- Jangan memakai kata rumah atau blok. Satuan latihan disebut Hari dan Level.\n- Jangan menyebut format data, JSON, atau bahwa kamu AI.\n"
              . "Format (markdown, maksimal 280 kata):\n## Ringkasan\n2 sampai 3 kalimat: seksi terkuat dan terlemah, perkiraan skor sekarang dibanding target.\n## Target jumlah benar\nSatu butir per seksi: Listening, Structure, Reading, masing-masing berisi target benar dari jumlah soal, benar sekarang, dan skor skalanya.\n## Tips\n3 sampai 5 butir, spesifik ke topik yang paling sering salah.\n## Rencana 7 hari\n3 sampai 4 butir, misalnya Hari 1-2: ...\n"
              . "Kalau target sudah tercapai, beri selamat singkat lalu sarankan target yang lebih tinggi. Kalau target tidak mungkin dicapai, sampaikan dengan halus dan beri rencana terbaik yang bisa dicapai.";
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => ($en ? "Learner data:\n" : "Data pelajar:\n") . json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        ];
    }

    /** Rapikan keluaran model agar sesuai aturan teks situs (tanpa tanda kutip dan tanpa tanda pisah panjang). */
    public static function clean(string $text): string
    {
        $t = str_replace(["\r\n", "\r"], "\n", $text);
        $t = preg_replace('/^```[a-z]*\s*$/m', '', $t);
        $t = preg_replace('/^#{1,6}\s+/m', '## ', $t);
        $t = preg_replace('/^(\s*)[*•]\s+/m', '$1- ', $t);
        $t = preg_replace('/\s*[—―]\s*/u', ', ', $t);
        $t = str_replace('–', '-', $t);
        $t = str_replace(['"', '“', '”', '„', '«', '»'], '', $t);
        // Apostrof hanya boleh di dalam kata
        $t = preg_replace("/(?<!\\p{L})['‘’]|['‘’](?!\\p{L})/u", '', $t);
        $t = str_replace('‘', '', $t);
        $t = preg_replace_callback('/\b(rumah|blok|houses?|blocks?)\b/iu', function ($m) {
            $w = mb_strtolower($m[1]);
            return ['rumah' => 'Hari', 'blok' => 'Level', 'house' => 'Day', 'houses' => 'Days', 'block' => 'Level', 'blocks' => 'Levels'][$w] ?? '';
        }, $t);
        $t = preg_replace('/ +,/', ',', $t);
        $t = preg_replace("/\n{3,}/", "\n\n", trim($t));
        return mb_substr($t, 0, 6000);
    }

    /**
     * Jalankan satu analisis: potong 1 koin, panggil model, kembalikan koin bila gagal.
     * @throws RuntimeException dengan kode: disabled, no_data, no_coins, limit, failed
     */
    public static function run(array $user, int $target): int
    {
        $uid = (int) $user['id'];
        if (!self::enabled()) {
            throw new RuntimeException('disabled');
        }
        $est = ScoreEstimator::estimate($uid);
        if ($est['total'] === null) {
            throw new RuntimeException('no_data');
        }
        if (self::usedToday($uid) >= self::dailyLimit() || !rate_hit('ai:' . $uid, 3, 120)) {
            throw new RuntimeException('limit');
        }
        $plan = self::plan($est, $target);
        $input = self::input($uid, $est, $plan);
        q('INSERT INTO ai_analyses (user_id, target, input_json, model, status) VALUES (?, ?, ?, ?, ?)',
            [$uid, $target, json_encode(['plan' => $plan, 'input' => $input], JSON_UNESCAPED_UNICODE), OpenRouter::model(), 'pending']);
        $id = (int) db()->lastInsertId();
        if (!Coins::spend($uid, self::COST_COINS, 'analysis', 'ai#' . $id)) {
            q('DELETE FROM ai_analyses WHERE id = ?', [$id]);
            throw new RuntimeException('no_coins');
        }
        try {
            $r = OpenRouter::chat(self::messages($input, (string) ($user['lang'] ?? 'id')), self::MAX_TOKENS);
            $out = self::clean($r['text']);
            if (mb_strlen($out) < 80) {
                throw new RuntimeException('Jawaban model terlalu pendek.');
            }
            q("UPDATE ai_analyses SET output = ?, model = ?, prompt_tokens = ?, completion_tokens = ?, cost_usd = ?, status = 'ok' WHERE id = ?",
                [$out, mb_substr($r['model'], 0, 80), $r['prompt_tokens'], $r['completion_tokens'], round($r['cost'], 6), $id]);
        } catch (Throwable $e) {
            q("UPDATE ai_analyses SET status = 'failed', error = ? WHERE id = ?", [mb_substr($e->getMessage(), 0, 255), $id]);
            Coins::credit($uid, self::COST_COINS, 'refund', 'ai#' . $id, 'Analisis gagal');
            app_log('error', 'Analisis AI #' . $id . ' gagal: ' . $e->getMessage());
            throw new RuntimeException('failed');
        }
        return $id;
    }

    public static function forUser(int $userId, int $limit = 10): array
    {
        return q_all("SELECT id, target, output, created_at, status FROM ai_analyses WHERE user_id = ? AND status = 'ok' ORDER BY id DESC LIMIT " . max(1, $limit), [$userId]);
    }
}
