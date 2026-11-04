<?php
/** @var array $est @var array $user @var array $weak @var bool $unlocked */
$target = (int) $user['target_score'];
$pos = fn($v) => max(0, min(100, ($v - 310) / (677 - 310) * 100));
?>
<section class="container page-head">
  <span class="eyebrow"><?= e(t('final.eyebrow')) ?></span>
  <h1><?= e(t('final.h1a')) ?> <span class="hl"><?= e(t('final.h1b')) ?></span></h1>
</section>
<section class="container">
<?php if (!$unlocked): ?>
  <div class="card card-hl dots">
    <h2><?= icon('lock', 28) ?> <?= e(t('final.locked_h')) ?></h2>
    <p class="muted prose"><?= e(t('final.locked')) ?></p>
    <?= btn_primary(t('result.back_map'), url('latihan')) ?>
  </div>
<?php elseif ($est['total'] === null): ?>
  <div class="card"><p class="mb-0"><?= e(t('score.not_enough')) ?></p></div>
<?php else: ?>
  <div class="final-score">
    <div class="card dots">
      <span class="chip chip-ink"><?= e(t('score.estimated')) ?></span>
      <div class="score-big mt-16"><?= (int) $est['total'] ?></div>
      <p class="mt-8"><?= e(t('score.range', ['a' => $est['low'], 'b' => $est['high']])) ?><?php if ($est['low_data']): ?> · <span class="chip chip-lime chip-xs"><?= e(t('score.low_data')) ?></span><?php endif; ?></p>
      <div class="target-line" aria-hidden="true">
        <div class="range" style="left:<?= $pos($est['low']) ?>%;width:<?= $pos($est['high']) - $pos($est['low']) ?>%"></div>
        <div class="fill" style="width:<?= $pos($est['total']) ?>%"></div>
        <div class="goal" style="left:<?= $pos($target) ?>%"><span><?= e(t('final.target_short', ['t' => $target])) ?></span></div>
      </div>
      <h2 class="mb-0"><?= e(t(ScoreEstimator::status((int) $est['total']))) ?></h2>
      <p class="muted"><?= e($est['total'] >= $target ? t('final.above', ['t' => $target]) : t('final.below', ['d' => $target - $est['total'], 't' => $target])) ?></p>
      <?php if ($simDone === 0): ?><p class="small"><?= icon('sparkle', 14) ?> <?= e(t('final.sim_hint')) ?></p><?php endif; ?>
      <a class="btn btn-primary mt-16" href="<?= e(url('rapor/akhir.pdf')) ?>"><span><?= e(t('final.download')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('download', 18) ?></span></a>
    </div>
    <div class="stack">
      <?php foreach (Practice::SECTIONS as $s): $d = $est['sections'][$s]; ?>
        <div class="card card-sm sec-<?= $s ?>">
          <div class="row-between"><span class="chip chip-sec"><?= e(section_label($s)) ?></span><span class="display" style="font-size:36px"><?= $d ? (int) $d['scaled'] : '-' ?></span></div>
          <div class="progress-bar <?= $s ?> mt-16"><span style="width:<?= $d ? $d['pct'] : 0 ?>%"></span></div>
          <p class="small muted mt-8 mb-0"><?= $d ? e(t('final.sec_meta', ['p' => fmt_num($d['pct'], 1), 'n' => $d['answered']])) : '' ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="mt-48">
    <h2><?= e(t('final.weak_h')) ?></h2>
    <?php if (!$weak): ?><div class="card empty"><?= e(t('pdf.no_weak')) ?></div><?php endif; ?>
    <div class="grid-3">
      <?php foreach ($weak as $w): ?>
        <div class="card card-sm">
          <span class="chip chip-<?= e($w['section']) ?>"><?= e($w['tag']) ?></span>
          <h3 class="mt-8"><?= e($w['info'] ? tr($w['info'], 'name') : $w['tag']) ?></h3>
          <p class="small muted"><?= e(t('final.wrong_n', ['n' => $w['wrong']])) ?></p>
          <form method="post" action="<?= e(url('latih-ulang')) ?>"><?= csrf_field() ?><input type="hidden" name="tag" value="<?= e($w['tag']) ?>">
            <button class="btn btn-primary btn-sm" type="submit"><span><?= e(t('history.drill')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('replay', 14) ?></span></button></form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <p class="small muted mt-24 prose"><?= e(t('final.formula')) ?></p>
<?php endif; ?>
</section>
