<?php
/** @var array $c @var array $tiers @var int $current */
$price = LegalController::rupiah((int) $tiers[$current]['price']);
?>
<section class="container page-head">
  <span class="eyebrow"><?= e($c['eyebrow']) ?></span>
  <h1><?= e($c['h1']) ?></h1>
  <p><?= e($c['lead']) ?></p>
</section>
<section class="container legal-wrap">
  <div class="price-grid">
    <article class="card price-card">
      <h2><?= e($c['free_name']) ?></h2>
      <p class="price"><span class="amount"><?= e($c['free_price']) ?></span> <span class="muted"><?= e($c['free_period']) ?></span></p>
      <ul class="price-list"><?php foreach ($c['free_items'] as $li): ?><li><?= icon('check', 18) ?><span><?= e($li) ?></span></li><?php endforeach; ?></ul>
      <a class="btn btn-secondary" href="<?= e(url('daftar')) ?>"><?= e($c['free_cta']) ?></a>
    </article>
    <article class="card price-card price-premium">
      <div class="row-between"><h2><?= e($c['premium_name']) ?></h2><span class="chip"><?= e($c['popular']) ?></span></div>
      <p class="price"><span class="amount"><?= e($price) ?></span> <span class="muted"><?= e($c['premium_period']) ?></span></p>
      <ul class="price-list"><?php foreach ($c['premium_items'] as $li): ?><li><?= icon('check', 18) ?><span><?= e($li) ?></span></li><?php endforeach; ?></ul>
      <button type="button" class="btn btn-primary" disabled aria-disabled="true"><?= e($c['premium_cta']) ?></button>
      <p class="small muted mb-0 mt-8"><?= e($c['premium_note']) ?></p>
    </article>
  </div>
  <article class="card legal-card mt-24">
    <h2 class="mt-0"><?= e($c['tiers_h']) ?></h2>
    <div class="tier-list">
      <?php foreach ($tiers as $i => $t): ?>
        <div class="tier <?= $i === $current ? 'is-now' : '' ?>">
          <span><?= e($t['to'] === null ? $c['tier_after'] : strtr($c['tier'], ['{from}' => $t['from'], '{to}' => $t['to']])) ?></span>
          <strong><?= e(LegalController::rupiah((int) $t['price'])) ?></strong>
          <?php if ($i === $current): ?><span class="chip chip-xs"><?= e($c['tier_now']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <ul class="small muted mt-16 mb-0"><?php foreach ($c['notes'] as $n): ?><li><?= LegalController::linkify($n) ?></li><?php endforeach; ?></ul>
  </article>
  <?= partial('legal-nav') ?>
</section>
