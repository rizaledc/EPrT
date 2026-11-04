<?php
declare(strict_types=1);

/**
 * Perkiraan skor format PBT (310–677).
 * Konversi raw → skala per seksi memakai tabel di settings.score_table (interpolasi linear).
 */
final class ScoreEstimator
{
    public const MAX_RAW = ['listening' => 50, 'structure' => 40, 'reading' => 50];
    public const WEIGHTS = ['sim' => 0.60, 'd' => 0.25, 'ac' => 0.15];
    public const DEFAULT_TABLE = [
        'points'    => [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50],
        'listening' => [31, 32, 37, 41, 44, 47, 50, 53, 57, 61, 68],
        'structure' => [31, 31, 36, 41, 46, 50, 55, 60, 68, null, null],
        'reading'   => [31, 31, 34, 39, 43, 46, 49, 52, 56, 60, 67],
    ];

    public static function table(): array
    {
        $t = setting('score_table');
        return is_array($t) && isset($t['points'], $t['listening'], $t['structure'], $t['reading']) ? $t : self::DEFAULT_TABLE;
    }

    public static function convert(string $section, float $raw): int
    {
        $t = self::table();
        $pts = $t['points'];
        $vals = $t[$section];
        $pairs = [];
        foreach ($pts as $i => $p) {
            if (isset($vals[$i]) && $vals[$i] !== null) {
                $pairs[] = [(float) $p, (float) $vals[$i]];
            }
        }
        $raw = max(0.0, min((float) self::MAX_RAW[$section], $raw));
        if (!$pairs) {
            return 31;
        }
        if ($raw <= $pairs[0][0]) {
            return (int) round($pairs[0][1]);
        }
        for ($i = 1; $i < count($pairs); $i++) {
            [$x1, $y1] = $pairs[$i];
            [$x0, $y0] = $pairs[$i - 1];
            if ($raw <= $x1) {
                return (int) round($y0 + ($y1 - $y0) * (($raw - $x0) / max(0.0001, $x1 - $x0)));
            }
        }
        return (int) round(end($pairs)[1]);
    }

    public static function total(int $l, int $s, int $r): int
    {
        return (int) round(($l + $s + $r) * 10 / 3);
    }

    /**
     * @param ?string $blokCode batasi pada satu Blok (untuk Rapor Blok); null = semua data
     * @return array{total:?int,low:?int,high:?int,sections:array,answers:int,low_data:bool}
     */
    public static function estimate(int $userId, ?string $blokCode = null): array
    {
        $sql = "SELECT q.section, r.kind, b.code AS blok, SUM(a.is_correct) c, COUNT(*) n
                FROM answers a JOIN attempts t ON t.id = a.attempt_id JOIN questions q ON q.id = a.question_id
                JOIN rumahs r ON r.id = t.rumah_id LEFT JOIN bloks b ON b.id = r.blok_id
                WHERE a.user_id = ? AND t.mode IN ('practice','simulation') AND (a.chosen_index IS NOT NULL OR t.mode = 'simulation')";
        $params = [$userId];
        if ($blokCode) {
            $sql .= ' AND b.code = ?';
            $params[] = $blokCode;
        }
        $sql .= ' GROUP BY q.section, r.kind, b.code';
        $agg = [];
        $answers = 0;
        foreach (q_all($sql, $params) as $row) {
            if ($row['kind'] === 'sample') {
                continue;
            }
            $cat = $row['kind'] === 'simulation' ? 'sim' : ($row['blok'] === 'D' ? 'd' : 'ac');
            $agg[$row['section']][$cat]['c'] = ($agg[$row['section']][$cat]['c'] ?? 0) + (int) $row['c'];
            $agg[$row['section']][$cat]['n'] = ($agg[$row['section']][$cat]['n'] ?? 0) + (int) $row['n'];
            $answers += (int) $row['n'];
        }
        $sections = [];
        foreach (Practice::SECTIONS as $sec) {
            $wSum = 0.0;
            $pSum = 0.0;
            $n = 0;
            $c = 0;
            foreach (self::WEIGHTS as $cat => $w) {
                $d = $agg[$sec][$cat] ?? null;
                if ($d && $d['n'] > 0) {
                    $wSum += $w;
                    $pSum += $w * ($d['c'] / $d['n']);
                    $n += $d['n'];
                    $c += $d['c'];
                }
            }
            if ($wSum > 0) {
                $pct = $pSum / $wSum;
                $scaled = self::convert($sec, $pct * self::MAX_RAW[$sec]);
                $sections[$sec] = ['pct' => round($pct * 100, 1), 'scaled' => $scaled, 'answered' => $n, 'correct' => $c];
            } else {
                $sections[$sec] = null;
            }
        }
        $total = null;
        if ($sections['listening'] && $sections['structure'] && $sections['reading']) {
            $total = self::total($sections['listening']['scaled'], $sections['structure']['scaled'], $sections['reading']['scaled']);
        }
        return [
            'total'    => $total,
            'low'      => $total !== null ? max(310, $total - 20) : null,
            'high'     => $total !== null ? min(677, $total + 20) : null,
            'sections' => $sections,
            'answers'  => $answers,
            'low_data' => $answers < 150,
        ];
    }

    public static function status(int $score): string
    {
        if ($score >= 550) {
            return 'score.status.very_ready';
        }
        if ($score >= 500) {
            return 'score.status.ready';
        }
        if ($score >= 450) {
            return 'score.status.almost';
        }
        return 'score.status.more';
    }

    /** Tag aturan dengan kesalahan terbanyak. */
    public static function weakestTags(int $userId, int $limit = 3, ?string $blokCode = null): array
    {
        $book = Practice::mistakeBook($userId, $blokCode);
        $tags = RuleTag::map();
        $out = [];
        foreach (array_slice($book, 0, $limit) as $g) {
            $out[] = ['tag' => $g['tag'], 'wrong' => $g['wrong'], 'info' => $tags[$g['tag']] ?? null, 'section' => $g['section']];
        }
        return $out;
    }
}
