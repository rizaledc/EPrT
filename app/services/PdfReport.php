<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

/** Rapor PDF (Dompdf, A4 potret, Plus Jakarta Sans di-embed). */
final class PdfReport
{
    private static function dompdf(): Dompdf
    {
        if (!class_exists(Dompdf::class)) {
            throw new RuntimeException('Dompdf belum terpasang (folder app/vendor).');
        }
        $cache = STORAGE_PATH . '/pdf-cache';
        if (!is_dir($cache)) {
            @mkdir($cache, 0775, true);
        }
        $o = new Options();
        $o->set('isRemoteEnabled', false);
        $o->set('isPhpEnabled', false);
        $o->set('isHtml5ParserEnabled', true);
        $o->set('fontDir', $cache);
        $o->set('fontCache', $cache);
        $o->set('tempDir', $cache);
        $o->set('chroot', [APP_PATH]);
        $o->set('defaultFont', 'Plus Jakarta Sans');
        $d = new Dompdf($o);
        $d->setPaper('A4', 'portrait');
        return $d;
    }

    private static function css(): string
    {
        $f = STORAGE_PATH . '/fonts';
        return "
        @font-face { font-family: 'Plus Jakarta Sans'; font-weight: normal; src: url('file://$f/PlusJakartaSans-Regular.ttf') format('truetype'); }
        @font-face { font-family: 'Plus Jakarta Sans'; font-weight: bold; src: url('file://$f/PlusJakartaSans-Bold.ttf') format('truetype'); }
        @font-face { font-family: 'Bricolage'; font-weight: bold; src: url('file://$f/BricolageGrotesque-ExtraBold.ttf') format('truetype'); }
        @page { margin: 28px 34px 40px; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #24101C; font-size: 11px; line-height: 1.5; }
        h1, .display { font-family: 'Bricolage', 'Plus Jakarta Sans', sans-serif; font-weight: bold; letter-spacing: -0.5px; }
        h1 { font-size: 26px; margin: 0; line-height: 1.1; }
        h2 { font-size: 13px; margin: 18px 0 8px; text-transform: uppercase; letter-spacing: 1.5px; color: #B3194F; }
        .head { border-bottom: 2px solid #24101C; padding-bottom: 10px; margin-bottom: 8px; }
        .logo { font-family: 'Bricolage', sans-serif; font-weight: bold; font-size: 15px; }
        .logo b { color: #E8336F; }
        .muted { color: #76606C; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #76606C; border-bottom: 1px solid #F0DFE6; padding: 6px 6px; }
        td { padding: 6px 6px; border-bottom: 1px solid #F0DFE6; }
        .num { text-align: right; }
        .bar { height: 12px; background: #F0DFE6; border-radius: 6px; }
        .bar div { height: 12px; border-radius: 6px; }
        .L { background: #E8336F; } .S { background: #6B2FD6; } .R { background: #0E9A86; }
        .tL { color: #B3194F; } .tS { color: #6B2FD6; } .tR { color: #0a6f61; }
        .big { font-family: 'Bricolage', sans-serif; font-weight: bold; font-size: 54px; line-height: 1; color: #E8336F; }
        .box { background: #FFE3EC; border-radius: 12px; padding: 12px 14px; }
        .chip { display: inline-block; background: #24101C; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 9px; font-weight: bold; }
        .foot { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8.5px; color: #76606C; border-top: 1px solid #F0DFE6; padding-top: 6px; }
        .weak td { vertical-align: top; }
        ";
    }

    private static function header(string $title, array $user): string
    {
        return '<div class="head"><table><tr><td style="border:0;padding:0"><span class="logo">Perumahan<b>Inggris</b></span><h1>' . e($title) . '</h1></td>'
            . '<td style="border:0;padding:0;text-align:right" class="muted">' . e($user['username']) . '<br>' . e(fmt_date(date('Y-m-d H:i:s'))) . '</td></tr></table></div>';
    }

    private static function footer(): string
    {
        $sp = implode(' · ', array_map(fn($s) => e($s['name']), Sponsor::all()));
        return '<div class="foot">' . e(t('sponsor.eyebrow')) . ': ' . $sp . '<br>' . e(t('footer.disclaimer')) . '</div>';
    }

    private static function bars(array $pcts): string
    {
        $map = ['listening' => 'L', 'structure' => 'S', 'reading' => 'R'];
        $h = '<table>';
        foreach ($map as $sec => $k) {
            $p = $pcts[$sec] ?? null;
            $w = $p === null ? 0 : max(0, min(100, (float) $p));
            $h .= '<tr><td style="width:24%;border:0" class="t' . $k . '"><b>' . e(section_label($sec)) . '</b></td>'
                . '<td style="border:0"><div class="bar"><div class="' . $k . '" style="width:' . $w . '%"></div></div></td>'
                . '<td style="width:12%;border:0" class="num"><b>' . ($p === null ? '–' : fmt_num($p) . '%') . '</b></td></tr>';
        }
        return $h . '</table>';
    }

    private static function weakTable(array $weak, int $userId): string
    {
        if (!$weak) {
            return '<p class="muted">' . e(t('pdf.no_weak')) . '</p>';
        }
        $h = '<table class="weak"><tr><th>' . e(t('pdf.rule')) . '</th><th class="num">' . e(t('pdf.wrong')) . '</th><th>' . e(t('pdf.advice')) . '</th></tr>';
        foreach ($weak as $w) {
            $tipRow = q_one(
                "SELECT q.tip_id, q.tip_en FROM mistakes m JOIN questions q ON q.id = m.question_id
                 WHERE m.user_id = ? AND JSON_CONTAINS(q.rule_tags, JSON_QUOTE(?)) ORDER BY m.wrong_count DESC LIMIT 1",
                [$userId, $w['tag']]
            );
            $tip = $tipRow ? tr($tipRow, 'tip') : '';
            $name = $w['info'] ? tr($w['info'], 'name') : $w['tag'];
            $h .= '<tr><td style="width:34%"><b>' . e($name) . '</b><br><span class="muted">' . e($w['tag']) . '</span></td><td class="num" style="width:10%">' . (int) $w['wrong'] . '×</td>'
                . '<td>' . e(t('pdf.advice_line', ['rule' => $name])) . ($tip ? ' ' . e($tip) : '') . '</td></tr>';
        }
        return $h . '</table>';
    }

    private static function stream(Dompdf $d, string $html, string $filename): never
    {
        $d->loadHtml($html, 'UTF-8');
        $d->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, no-store');
        echo $d->output();
        exit;
    }

    public static function blok(array $user, string $code): never
    {
        $uid = (int) $user['id'];
        $blok = q_one('SELECT * FROM bloks WHERE code = ?', [$code]);
        $rumahs = array_values(array_filter(Rumah::all(), fn($r) => $r['blok_code'] === $code && in_array($r['kind'], ['practice', 'adaptive'], true)));
        $rows = '';
        $secC = ['listening' => 0, 'structure' => 0, 'reading' => 0];
        $secN = $secC;
        foreach ($rumahs as $r) {
            $cells = '';
            $totC = 0;
            $totN = 0;
            $time = (int) q_val("SELECT COALESCE(SUM(time_spent_s),0) FROM attempts WHERE user_id = ? AND rumah_id = ? AND finished_at IS NOT NULL AND mode = 'practice'", [$uid, $r['id']]);
            foreach (Practice::SECTIONS as $s) {
                $a = q_one("SELECT correct, total FROM attempts WHERE user_id = ? AND rumah_id = ? AND section = ? AND mode = 'practice' AND finished_at IS NOT NULL ORDER BY id DESC LIMIT 1", [$uid, $r['id'], $s]);
                if (!$a) {
                    // Belum menekan "selesai": pakai jawaban yang sudah ada
                    $a = q_one("SELECT SUM(a.is_correct) correct, COUNT(*) total FROM answers a JOIN attempts t ON t.id = a.attempt_id WHERE t.user_id = ? AND t.rumah_id = ? AND t.section = ? AND t.mode = 'practice' AND a.chosen_index IS NOT NULL", [$uid, $r['id'], $s]);
                }
                $c = (int) ($a['correct'] ?? 0);
                $n = (int) ($a['total'] ?? 0);
                $cells .= '<td class="num">' . ($n ? "$c/$n" : '–') . '</td>';
                $totC += $c;
                $totN += $n;
                $secC[$s] += $c;
                $secN[$s] += $n;
            }
            $rows .= '<tr><td><b>' . (int) $r['number'] . '</b> · ' . e(Rumah::title($r)) . '</td>' . $cells
                . '<td class="num"><b>' . ($totN ? round($totC / $totN * 100) . '%' : '–') . '</b></td><td class="num">' . fmt_duration($time) . '</td></tr>';
        }
        $pcts = [];
        foreach ($secC as $s => $c) {
            $pcts[$s] = $secN[$s] ? round($c / $secN[$s] * 100, 1) : null;
        }
        $est = ScoreEstimator::estimate($uid);
        $weak = ScoreEstimator::weakestTags($uid, 5, $code);
        $title = t('pdf.blok_title', ['b' => $code, 'name' => tr($blok, 'name')]);

        $html = '<html><head><meta charset="utf-8"><style>' . self::css() . '</style></head><body>'
            . self::footer()
            . self::header($title, $user)
            . '<h2>' . e(t('pdf.per_rumah')) . '</h2>'
            . '<table><tr><th>Rumah</th><th class="num">L</th><th class="num">S</th><th class="num">R</th><th class="num">Total</th><th class="num">' . e(t('pdf.time')) . '</th></tr>' . $rows . '</table>'
            . '<h2>' . e(t('pdf.pct_section')) . '</h2>' . self::bars($pcts)
            . '<h2>' . e(t('pdf.weakest5')) . '</h2>' . self::weakTable($weak, $uid)
            . '<h2>' . e(t('pdf.estimate_tmp')) . '</h2>'
            . '<div class="box">' . ($est['total'] !== null
                ? '<span class="display" style="font-size:28px">' . $est['total'] . '</span> &nbsp;<span class="muted">' . e(t('score.range', ['a' => $est['low'], 'b' => $est['high']])) . '</span> &nbsp;<span class="chip">' . e(t('score.estimated')) . '</span>'
                  . ($est['low_data'] ? '<br><span class="muted">' . e(t('score.low_data')) . '</span>' : '')
                : e(t('score.not_enough'))) . '</div>'
            . '</body></html>';
        $filename = sprintf('PerumahanInggris-Rapor-Blok%s-%s-%s.pdf', $code, preg_replace('/[^A-Za-z0-9_]/', '', $user['username']), date('Y-m-d'));
        self::stream(self::dompdf(), $html, $filename);
    }

    public static function final(array $user): never
    {
        $uid = (int) $user['id'];
        $est = ScoreEstimator::estimate($uid);
        $weak = ScoreEstimator::weakestTags($uid, 3);
        $target = (int) $user['target_score'];
        $status = $est['total'] !== null ? t(ScoreEstimator::status($est['total'])) : '';
        $secRows = '';
        $pcts = [];
        foreach (Practice::SECTIONS as $s) {
            $d = $est['sections'][$s];
            $pcts[$s] = $d['pct'] ?? null;
            $secRows .= '<tr><td><b>' . e(section_label($s)) . '</b></td><td class="num">' . ($d ? $d['scaled'] : '–') . '</td><td class="num">' . ($d ? fmt_num($d['pct'], 1) . '%' : '–') . '</td><td class="num">' . ($d ? $d['answered'] : 0) . '</td></tr>';
        }
        $html = '<html><head><meta charset="utf-8"><style>' . self::css() . '</style></head><body>'
            . self::footer()
            . self::header(t('pdf.final_title'), $user)
            . '<table style="margin-top:14px"><tr><td style="border:0;width:45%"><div class="muted">' . e(t('score.estimated')) . '</div><div class="big">' . ($est['total'] ?? '–') . '</div>'
            . '<div>' . ($est['total'] !== null ? e(t('score.range', ['a' => $est['low'], 'b' => $est['high']])) : '') . '</div></td>'
            . '<td style="border:0"><div class="box"><b>' . e($status) . '</b><br>' . e(t('final.target', ['t' => $target])) . '<br>'
            . ($est['low_data'] ? '<span class="muted">' . e(t('score.low_data')) . '</span>' : '') . '</div></td></tr></table>'
            . '<h2>' . e(t('final.sections')) . '</h2>'
            . '<table><tr><th>' . e(t('history.col.section')) . '</th><th class="num">' . e(t('final.scaled')) . '</th><th class="num">% ' . e(t('common.correct')) . '</th><th class="num">' . e(t('final.answers')) . '</th></tr>' . $secRows . '</table>'
            . '<div style="margin-top:10px">' . self::bars($pcts) . '</div>'
            . '<h2>' . e(t('final.weak_h')) . '</h2>' . self::weakTable($weak, $uid)
            . '<p class="muted" style="margin-top:14px">' . e(t('final.formula')) . '</p>'
            . '</body></html>';
        $filename = sprintf('PerumahanInggris-Rapor-Akhir-%s-%s.pdf', preg_replace('/[^A-Za-z0-9_]/', '', $user['username']), date('Y-m-d'));
        self::stream(self::dompdf(), $html, $filename);
    }
}
