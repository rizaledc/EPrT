<?php
declare(strict_types=1);

final class ReportController
{
    private function allBloksDone(int $userId): bool
    {
        $progress = Practice::progressMap($userId);
        foreach (['A', 'B', 'C', 'D'] as $c) {
            if (!Practice::blokCompleted($userId, $c, $progress)) {
                return false;
            }
        }
        return true;
    }

    public function final(): void
    {
        $u = require_login();
        $unlocked = $this->allBloksDone((int) $u['id']);
        $est = ScoreEstimator::estimate((int) $u['id']);
        $simDone = (int) q_val("SELECT COUNT(*) FROM attempts t JOIN rumahs r ON r.id = t.rumah_id WHERE t.user_id = ? AND r.kind = 'simulation' AND t.finished_at IS NOT NULL", [$u['id']]);
        render('report/final', [
            'title'    => t('final.title'),
            'unlocked' => $unlocked,
            'est'      => $est,
            'user'     => $u,
            'weak'     => ScoreEstimator::weakestTags((int) $u['id'], 3),
            'simDone'  => $simDone,
            'noindex'  => true,
            'compactSponsors' => true,
        ]);
    }

    public function finalPdf(): void
    {
        $u = require_login();
        if (!$this->allBloksDone((int) $u['id'])) {
            flash('info', t('final.locked'));
            redirect('/rapor/akhir');
        }
        PdfReport::final($u);
    }

    public function blokPdf(string $blok): void
    {
        // Pola baru /rapor/level-a.pdf; pola lama /rapor/a.pdf dialihkan 301
        if (preg_match('/^[a-dA-D]$/', $blok)) {
            redirect('/rapor/level-' . strtolower($blok) . '.pdf', 301);
        }
        $u = require_login();
        $code = strtoupper(preg_replace('/^level-/', '', $blok));
        if (!in_array($code, ['A', 'B', 'C', 'D'], true)) {
            abort(404);
        }
        if (!Practice::blokCompleted((int) $u['id'], $code)) {
            flash('info', t('map.blok_locked'));
            redirect('/latihan');
        }
        PdfReport::blok($u, $code);
    }
}
