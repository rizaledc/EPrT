<?php
/** @var array $user @var array $progress @var array $rumahs @var array $bloks */
$pct = $totalCount ? round($doneCount / $totalCount * 100) : 0;
$sims = array_values(array_filter($rumahs, fn($r) => $r['kind'] === 'simulation'));
$nextId = $next ? (int) $next['id'] : 0;
?>
<section class="container" style="padding-top:40px">
  <?php if ($announcement !== ''): ?><div class="announce mb-24"><?= icon('sparkle', 18) ?> <span><?= e($announcement) ?></span></div><?php endif; ?>
  <div class="card dots greet-card">
    <div>
      <span class="eyebrow"><?= e(t('map.eyebrow')) ?></span>
      <h2><?= e(t('map.hello', ['name' => $user['username']])) ?></h2>
      <p class="muted mb-16"><?= e(t('map.progress', ['done' => $doneCount, 'total' => $totalCount])) ?></p>
      <div class="progress-bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= e(t('map.progress_label')) ?>"><span style="width:<?= $pct ?>%"></span></div>
    </div>
    <div class="stack">
      <?php if ($continue): ?>
        <?= btn_primary($continue['kind'] === 'simulation' ? t('map.continue_sim', ['n' => $continue['number']]) : t('map.continue', ['n' => $continue['number']]), Rumah::url($continue)) ?>
      <?php endif; ?>
      <?php if ($allBloksDone): ?>
        <a class="btn btn-secondary" href="<?= e(url('rapor/akhir')) ?>"><?= icon('target', 18) ?> <?= e(t('map.final_report')) ?></a>
      <?php elseif ($estimate['total'] !== null): ?>
        <p class="small muted mb-0"><?= e(t('map.estimate_short', ['score' => $estimate['total']])) ?> <span class="chip chip-xs"><?= e(t('score.estimated')) ?></span></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php foreach ($bloks as $b):
  $inBlok = array_values(array_filter($rumahs, fn($r) => $r['blok_code'] === $b['code'] && in_array($r['kind'], ['practice', 'adaptive'], true)));
  $blokDone = Practice::blokCompleted((int) $user['id'], $b['code'], $progress);
?>
<section class="container blok-section" aria-labelledby="blok-<?= e($b['code']) ?>">
  <div class="blok-head">
    <div class="row" style="align-items:flex-end">
      <span class="blok-big" aria-hidden="true"><?= e($b['code']) ?></span>
      <div>
        <span class="eyebrow"><?= e(t('term.level_n', ['b' => $b['code']])) ?></span>
        <h2 id="blok-<?= e($b['code']) ?>"><?= e(tr($b, 'name')) ?></h2>
      </div>
    </div>
    <?php if ($blokDone): ?>
      <a class="btn btn-secondary btn-sm" href="<?= e(url('rapor/level-' . strtolower($b['code']) . '.pdf')) ?>"><?= icon('download', 16) ?> <?= e(t('map.download_blok', ['b' => $b['code']])) ?></a>
    <?php else: ?>
      <span class="btn btn-secondary btn-sm" aria-disabled="true" title="<?= e(t('map.blok_locked')) ?>"><?= icon('lock', 16) ?> <?= e(t('map.download_blok', ['b' => $b['code']])) ?></span>
    <?php endif; ?>
  </div>
  <div class="houses">
    <?php foreach ($inBlok as $r):
      $p = $progress[(int) $r['id']] ?? null;
      $state = Practice::rumahState($p);
      $total = Rumah::total($r);
      $soon = $total === 0;
    ?>
      <a class="house-card state-<?= $state ?> <?= $soon ? 'soon' : '' ?>" href="<?= e(Rumah::url($r)) ?>">
        <?php if ((int) $r['id'] === $nextId): ?><span class="chip chip-lime badge-next"><?= e(t('map.suggested')) ?></span><?php endif; ?>
        <span class="house-num" aria-hidden="true"><?= (int) $r['number'] ?></span>
        <?= partial('house') ?>
        <h3><span class="sr-only"><?= e(t('map.rumah_n', ['n' => $r['number']])) ?>: </span><?= e(Rumah::title($r)) ?></h3>
        <div class="meta">
          <?php if ($soon): ?><?= e(t('map.soon')) ?>
          <?php elseif ($state === 'done'): ?><?= icon('check', 14) ?> <?= e(t('map.done')) ?><?= $p['last_pct'] !== null ? ' · ' . fmt_num($p['last_pct']) . '%' : '' ?>
          <?php elseif ($state === 'progress'): ?><?= e(t('map.in_progress', ['n' => (int) $p['answered_distinct'], 't' => Rumah::completionThreshold($r)])) ?>
          <?php else: ?><?= e(t('map.not_started', ['n' => $r['kind'] === 'adaptive' ? 30 : $total])) ?><?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="container blok-section" aria-labelledby="sim-h">
  <div class="blok-head">
    <div><span class="eyebrow"><?= e(t('map.sim_eyebrow')) ?></span><h2 id="sim-h"><?= e(t('map.sim_h')) ?></h2></div>
  </div>
  <div class="grid-2">
    <?php foreach ($sims as $s):
      $p = $progress[(int) $s['id']] ?? null; $state = Practice::rumahState($p); $total = Rumah::total($s); ?>
      <a class="card sim-card state-<?= $state ?>" href="<?= e(Rumah::url($s)) ?>">
        <?= partial('house') ?>
        <div>
          <?php if ((int) $s['id'] === $nextId): ?><span class="chip chip-lime"><?= e(t('map.suggested')) ?></span><?php endif; ?>
          <h3 class="mt-8"><?= e(Rumah::title($s)) ?></h3>
          <p class="small muted mb-0"><?= $total ? e(t('map.sim_meta')) : e(t('map.soon')) ?></p>
        </div>
        <span class="link-arrow"><?= e($state === 'done' ? t('map.done') : t('map.open')) ?> <?= icon('arrow-right', 16) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="small muted mt-16"><?= e(t('map.order_hint')) ?></p>
</section>
