<?php
/** @var array $est @var ?array $plan @var int $target @var int $balance @var array $analyses @var bool $aiOn @var int $left */
?>
<section class="container container-narrow page-head">
  <span class="eyebrow"><?= e(t('advice.eyebrow')) ?></span>
  <h1><?= e(t('advice.h1a')) ?> <span class="hl"><?= e(t('advice.h1b')) ?></span></h1>
</section>
<section class="container container-narrow stack">
  <form class="card card-sm row" method="get" action="<?= e(url('rekomendasi')) ?>">
    <div class="field mb-0" style="flex:1;min-width:160px">
      <label for="target"><?= e(t('advice.target_label')) ?></label>
      <input class="input" id="target" name="target" type="number" min="310" max="677" step="1" value="<?= (int) $target ?>" inputmode="numeric">
    </div>
    <button class="btn btn-secondary" type="submit" style="align-self:flex-end"><?= icon('refresh', 16) ?> <?= e(t('advice.update')) ?></button>
  </form>

<?php if (!$plan): ?>
  <div class="card"><p class="mb-0"><?= e(t('advice.no_data')) ?></p></div>
<?php else: ?>
  <div class="card dots">
    <div class="row-between">
      <div><span class="eyebrow"><?= e(t('advice.now')) ?></span><div class="score-big"><?= (int) $est['total'] ?></div></div>
      <div style="text-align:right"><span class="eyebrow"><?= e(t('advice.goal')) ?></span><div class="score-big"><?= (int) $plan['total'] ?></div></div>
    </div>
    <h2 class="mt-24"><?= e(t('advice.plan_h')) ?></h2>
    <p class="small muted"><?= e(t('advice.plan_sub', ['t' => $target])) ?></p>
    <?php if ($plan['met']): ?><div class="announce mb-16"><?= icon('check', 18) ?> <?= e(t('advice.met', ['t' => $target])) ?></div><?php endif; ?>
    <?php if (!$plan['reachable']): ?><div class="announce mb-16"><?= icon('target', 18) ?> <?= e(t('advice.unreachable', ['t' => $target])) ?></div><?php endif; ?>
    <div class="grid-3 advice-targets">
      <?php foreach (ScoreEstimator::MAX_RAW as $sec => $max): $g = $plan['gain'][$sec]; ?>
        <div class="card card-sm sec-<?= e($sec) ?>">
          <span class="chip chip-<?= e($sec) ?>"><?= e(section_label($sec)) ?></span>
          <div class="display advice-raw mt-8"><?= (int) $plan['raw'][$sec] ?><span class="muted">/<?= $max ?></span></div>
          <p class="small mb-0"><?= e(t('advice.row', ['c' => $plan['raw'][$sec], 'm' => $max])) ?> · <?= e(t('advice.scaled', ['s' => $plan['scaled'][$sec]])) ?></p>
          <p class="small muted mb-0"><?= e(t('advice.now_row', ['c' => $plan['current'][$sec]])) ?> · <strong><?= $g > 0 ? e(t('advice.gain', ['n' => $g])) : e(t('advice.keep')) ?></strong></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card card-hl" id="analisis">
    <h2><?= icon('sparkle', 22) ?> <?= e(t('advice.ai_h')) ?></h2>
    <p class="muted"><?= e(t('advice.ai_p')) ?></p>
    <?php if ($aiOn): ?>
      <form class="row" method="post" action="<?= e(url('rekomendasi/analisis')) ?>" data-ai-form>
        <?= csrf_field() ?><input type="hidden" name="target" value="<?= (int) $target ?>">
        <button class="btn btn-primary" type="submit" <?= $balance < AiAdvisor::COST_COINS || $left < 1 ? 'disabled' : '' ?> data-once data-busy-label="<?= e(t('advice.working')) ?>"><span><?= e(t('advice.ai_btn')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('sparkle', 16) ?></span></button>
        <span class="chip chip-ink"><?= icon('coin', 14) ?> <?= e(t('advice.balance', ['n' => $balance])) ?></span>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('koin')) ?>"><?= e(t('advice.buy')) ?></a>
      </form>
      <p class="small muted mt-8 mb-0"><?= e(t('advice.left', ['n' => $left])) ?></p>
    <?php else: ?>
      <div class="announce"><?= icon('clock', 18) ?> <?= e(t('advice.ai_off')) ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

  <?php if ($analyses): ?>
  <h2 class="mt-24"><?= e(t('advice.history_h')) ?></h2>
  <?php foreach ($analyses as $i => $a): ?>
    <details class="card ai-out" id="analisis-<?= (int) $a['id'] ?>" <?= $i === 0 ? 'open' : '' ?>>
      <summary><strong><?= e(t('advice.for_target', ['t' => (int) $a['target'], 'd' => fmt_date($a['created_at'])])) ?></strong></summary>
      <div class="ai-body mt-16"><?= rich((string) $a['output']) ?></div>
    </details>
  <?php endforeach; ?>
  <?php endif; ?>
  <p class="small muted prose"><?= e(t('advice.disclaimer')) ?></p>
</section>
