<?php
/** @var array $terms */
$groups = ['pelaku', 'kelas', 'bagian', 'waktu', 'aturan'];
$renderExample = function (string $ex): string {
    $s = e($ex);
    $s = preg_replace('/__(.+?)__/s', '<u>$1</u>', $s);
    return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
};
?>
<section class="container page-head">
  <span class="eyebrow"><?= e(t('glossary.eyebrow')) ?></span>
  <h1><?= e(t('glossary.h1a')) ?> <span class="hl"><?= e(t('glossary.h1b')) ?></span></h1>
  <p><?= e(t('glossary.sub')) ?></p>
</section>

<section class="container">
  <div class="card card-hl car-figure">
    <svg viewBox="0 0 480 240" role="img" aria-labelledby="car-t">
      <title id="car-t"><?= e(t('glossary.car.alt')) ?></title>
      <g fill="none" stroke="#24101C" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <path d="M40 160h300l20-50-60-50H130l-50 50H50a10 10 0 0 0-10 10Z" fill="#fff"/>
        <path d="M140 66h72v44h-112ZM226 66h70l46 44H226Z" fill="#FFE3EC"/>
        <circle cx="110" cy="166" r="26" fill="#24101C"/><circle cx="110" cy="166" r="9" fill="#fff"/>
        <circle cx="290" cy="166" r="26" fill="#24101C"/><circle cx="290" cy="166" r="9" fill="#fff"/>
        <path d="M360 140h48l14 20h-62" fill="#D6F35B"/>
        <path d="M408 160h50" stroke-dasharray="4 8"/>
        <path d="M430 60v110M418 60h24l-12 18Z" fill="#E8336F"/>
      </g>
      <circle cx="170" cy="92" r="11" fill="#E8336F"/><path d="M162 112c2-8 14-8 16 0" stroke="#E8336F" stroke-width="5" stroke-linecap="round" fill="none"/>
      <rect x="300" y="122" width="34" height="20" rx="4" fill="#6B2FD6"/>
      <g font-family="Plus Jakarta Sans, sans-serif" font-size="13" font-weight="700" fill="#24101C">
        <text x="150" y="40">subject = <?= e(t('glossary.car.driver')) ?></text>
        <text x="282" y="214">verb = <?= e(t('glossary.car.engine')) ?></text>
        <text x="352" y="196">conjunction</text>
        <text x="410" y="48">preposition</text>
      </g>
      <path d="M170 46v32M316 200v-58" stroke="#24101C" stroke-width="1.5" stroke-dasharray="3 4"/>
    </svg>
    <div>
      <h2><?= e(t('glossary.car.h')) ?></h2>
      <p class="muted"><?= e(t('glossary.car.p')) ?></p>
      <div class="car-legend">
        <?php foreach (['subject', 'verb', 'auxiliary', 'adjective', 'adverb', 'preposition', 'conjunction'] as $k): ?>
          <div><b><?= e($k) ?></b> = <?= e(t('glossary.car.' . $k)) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="container mt-48">
  <div class="glossary-tools">
    <label class="sr-only" for="g-search"><?= e(t('glossary.search')) ?></label>
    <input class="input" id="g-search" type="search" placeholder="<?= e(t('glossary.search')) ?>" data-g-search autocomplete="off">
    <div class="filter-chips" role="group" aria-label="<?= e(t('glossary.filter')) ?>">
      <button type="button" aria-pressed="true" data-g-filter="all"><?= e(t('glossary.all')) ?></button>
      <?php foreach ($groups as $g): ?>
        <button type="button" aria-pressed="false" data-g-filter="<?= e($g) ?>"><?= e(t('glossary.grp.' . $g)) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <p class="small muted" data-g-count aria-live="polite"></p>
  <?php if (!$terms): ?>
    <div class="empty card"><?= e(t('glossary.empty')) ?></div>
  <?php endif; ?>
  <div class="term-grid">
    <?php foreach ($terms as $g): ?>
      <article class="card card-sm term-card" id="<?= e($g['slug']) ?>" data-g-item data-grp="<?= e($g['grp']) ?>" data-text="<?= e(mb_strtolower($g['term_en'] . ' ' . $g['meaning_id'] . ' ' . $g['def_id'] . ' ' . $g['def_en'])) ?>">
        <span class="chip chip-outline"><?= e(t('glossary.grp.' . $g['grp'])) ?></span>
        <h3><?= e($g['term_en']) ?></h3>
        <div class="meaning"><?= e($g['meaning_id']) ?></div>
        <p class="mb-0"><?= e(tr($g, 'def')) ?></p>
        <?php if ($g['example']): ?><div class="example"><?= $renderExample($g['example']) ?></div><?php endif; ?>
        <?php if (tr($g, 'analogy')): ?><div class="analogy"><?= icon('bulb', 16) ?><span><?= e(tr($g, 'analogy')) ?></span></div><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>
