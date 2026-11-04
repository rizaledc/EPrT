<?php
/** @var array $rumah @var array $status */
$material = tr($rumah, 'material');
$seen = [];
$adaptive = $rumah['kind'] === 'adaptive';
?>
<section class="container">
  <nav class="small mt-24" aria-label="breadcrumb"><a href="<?= e(url('latihan')) ?>"><?= e(t('nav.practice')) ?></a> / <?= e(t('term.level_n', ['b' => $rumah['blok_code']])) ?> · <?= e(tr(['name_id' => $rumah['blok_name_id'], 'name_en' => $rumah['blok_name_en']], 'name')) ?></nav>
  <div class="rumah-hero">
    <div class="big-num" aria-hidden="true"><?= (int) $rumah['number'] ?></div>
    <div>
      <span class="eyebrow"><?= e(t('map.rumah_n', ['n' => $rumah['number']])) ?><?= $adaptive ? ' · ' . e(t('rumah.adaptive')) : '' ?></span>
      <h1><?= e(Rumah::title($rumah)) ?></h1>
      <?php $answered = (int) ($progress['answered_distinct'] ?? 0); ?>
      <p class="muted mb-0 mt-8"><?php if (!empty($progress['completed_at'])): ?><span class="chip chip-good"><?= icon('check', 14) ?> <?= e(t('map.done')) ?></span><?php else: ?><?= e(t('rumah.progress', ['n' => $answered, 't' => $threshold])) ?><?php endif; ?></p>
    </div>
  </div>
</section>

<section class="container">
  <div class="section-starts">
    <?php foreach (Practice::SECTIONS as $s): $st = $status[$s]; $none = $st['state'] === 'todo' && ($st['total'] ?? 0) === 0 && !$adaptive; ?>
      <div class="card card-sm section-start sec-<?= $s ?>">
        <div class="row-between"><span class="chip chip-sec"><?= icon($s === 'listening' ? 'headphones' : ($s === 'structure' ? 'pen' : 'book'), 14) ?> <?= e(section_label($s)) ?></span>
          <span class="tiny muted mono"><?= (int) ($st['total'] ?? 0) ?> <?= e(t('common.questions')) ?></span></div>
        <div class="status">
          <?php if ($st['state'] === 'progress'): ?><?= e(t('rumah.status.progress', ['n' => $st['answered'], 't' => $st['total']])) ?>
          <?php elseif ($st['state'] === 'done'): ?><?= e(t('rumah.status.done', ['c' => $st['correct'], 't' => $st['total'], 'b' => $st['best']])) ?>
          <?php elseif ($none): ?><?= e(t('map.soon')) ?>
          <?php else: ?><?= e(t('rumah.status.todo')) ?><?php endif; ?>
        </div>
        <?php if (!$none): ?>
          <?= btn_primary($st['state'] === 'progress' ? t('rumah.continue_section', ['s' => section_label($s)]) : ($st['state'] === 'done' ? t('rumah.redo_section', ['s' => section_label($s)]) : t('rumah.start_section', ['s' => section_label($s)])), Rumah::url($rumah, $s), 'btn-sm') ?>
          <?php if ($st['state'] === 'done'): ?><a class="small" href="<?= e(url('hasil/' . $st['attempt'])) ?>"><?= e(t('rumah.last_result')) ?></a><?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container mt-48">
  <span class="eyebrow"><?= e(t('rumah.material_eyebrow')) ?></span>
  <h2 class="mt-8"><?= e(t('rumah.material_h')) ?></h2>
  <?php if ($material === ''): ?>
    <div class="card empty"><?= e($adaptive ? t('rumah.adaptive_desc') : t('rumah.no_material')) ?></div>
  <?php else:
    // Materi dibagi per seksi dengan penanda baris "=== listening|structure|reading"
    $parts = preg_split('/^===\s*(listening|structure|reading)\s*$/mi', $material, -1, PREG_SPLIT_DELIM_CAPTURE);
    $intro = trim((string) array_shift($parts));
  ?>
    <?php if ($intro !== ''): ?><div class="material prose mb-24"><?= rich($intro, $seen) ?></div><?php endif; ?>
    <div class="grid-3">
      <?php for ($i = 0; $i + 1 < count($parts); $i += 2): $sec = strtolower($parts[$i]); ?>
        <article class="card concept-card sec-<?= e($sec) ?>">
          <span class="chip chip-<?= e($sec) ?>"><?= e(section_label($sec)) ?></span>
          <div class="material mt-16"><?= rich((string) $parts[$i + 1], $seen) ?></div>
        </article>
      <?php endfor; ?>
    </div>
    <?= fallback_chip($rumah, 'material') ?>
  <?php endif; ?>
</section>
