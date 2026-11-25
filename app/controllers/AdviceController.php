<?php
declare(strict_types=1);

final class AdviceController
{
    public function index(): void
    {
        $u = require_login();
        $uid = (int) $u['id'];
        $target = $this->target($u, $_GET['target'] ?? null);
        $est = ScoreEstimator::estimate($uid);
        $plan = $est['total'] !== null ? AiAdvisor::plan($est, $target) : null;
        render('advice/index', [
            'title'     => t('advice.title'),
            'user'      => $u,
            'est'       => $est,
            'plan'      => $plan,
            'target'    => $target,
            'balance'   => Coins::balance($uid),
            'analyses'  => AiAdvisor::forUser($uid, 10),
            'aiOn'      => AiAdvisor::enabled(),
            'left'      => max(0, AiAdvisor::dailyLimit() - AiAdvisor::usedToday($uid)),
            'noindex'   => true,
        ]);
    }

    public function analyze(): void
    {
        $u = require_login();
        $target = $this->target($u, $_POST['target'] ?? null);
        try {
            $id = AiAdvisor::run($u, $target);
        } catch (RuntimeException $e) {
            $code = in_array($e->getMessage(), ['disabled', 'no_data', 'no_coins', 'limit'], true) ? $e->getMessage() : 'failed';
            flash('error', t('advice.err.' . $code));
            redirect($code === 'no_coins' ? '/koin' : '/rekomendasi?target=' . $target);
        }
        flash('success', t('advice.done'));
        redirect('/rekomendasi?target=' . $target . '#analisis-' . $id);
    }

    private function target(array $u, $raw): int
    {
        $t = $raw !== null && $raw !== '' ? (int) $raw : (int) $u['target_score'];
        return max(310, min(677, $t));
    }
}
