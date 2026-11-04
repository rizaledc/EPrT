<?php /** @var array $rumah @var array $status */ ?>
<section class="container">
  <nav class="small mt-24"><a href="<?= e(url('latihan')) ?>"><?= e(t('nav.practice')) ?></a> / <?= e(t('map.sim_h')) ?></nav>
  <div class="rumah-hero">
    <div class="big-num" aria-hidden="true">S<?= (int) $rumah['number'] ?></div>
    <div>
      <span class="eyebrow"><?= e(t('sim.eyebrow')) ?></span>
      <h1><?= e(Rumah::title($rumah)) ?></h1>
      <p class="muted mt-8 mb-0 prose"><?= e(t('sim.desc')) ?></p>
    </div>
  </div>
  <div class="card card-hl mb-24">
    <h3><?= e(t('sim.rules_h')) ?></h3>
    <ul class="rich-list mb-0">
      <li><span><?= e(t('sim.rule1')) ?></span></li>
      <li><span><?= e(t('sim.rule2')) ?></span></li>
      <li><span><?= e(t('sim.rule3')) ?></span></li>
      <li><span><?= e(t('sim.rule4')) ?></span></li>
    </ul>
  </div>
  <div class="section-starts">
    <?php foreach (Practice::SECTIONS as $s): $st = $status[$s]; $mins = Practice::SIM_SECONDS[$s] / 60; ?>
      <div class="card card-sm section-start sec-<?= $s ?>">
        <div class="row-between"><span class="chip chip-sec"><?= e(section_label($s)) ?></span><span class="tiny muted mono"><?= (int) ($st['total'] ?? 0) ?> <?= e(t('common.questions')) ?> · <?= $mins ?> <?= e(t('common.minutes')) ?></span></div>
        <div class="status">
          <?php if ($st['state'] === 'progress'): ?><?= e(t('rumah.status.progress', ['n' => $st['answered'], 't' => $st['total']])) ?>
          <?php elseif ($st['state'] === 'done'): ?><?= e(t('rumah.status.done', ['c' => $st['correct'], 't' => $st['total'], 'b' => $st['best']])) ?>
          <?php elseif (($st['total'] ?? 0) === 0): ?><?= e(t('map.soon')) ?>
          <?php else: ?><?= e(t('rumah.status.todo')) ?><?php endif; ?>
        </div>
        <?php if (($st['total'] ?? 0) > 0 || $st['state'] !== 'todo'): ?>
          <?= btn_primary($st['state'] === 'progress' ? t('rumah.continue_section', ['s' => section_label($s)]) : ($st['state'] === 'done' ? t('rumah.redo_section', ['s' => section_label($s)]) : t('rumah.start_section', ['s' => section_label($s)])), Rumah::url($rumah, $s), 'btn-sm') ?>
          <?php if ($st['state'] === 'done'): ?><a class="small" href="<?= e(url('hasil/' . $st['attempt'])) ?>"><?= e(t('rumah.last_result')) ?></a><?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
