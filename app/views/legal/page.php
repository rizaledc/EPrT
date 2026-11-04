<?php
/** @var array $page @var string $updated */
?>
<section class="container page-head">
  <span class="eyebrow"><?= e($page['eyebrow']) ?></span>
  <h1><?= e($page['h1']) ?></h1>
  <p><?= e($page['lead']) ?></p>
  <p class="small muted"><?= e($updated) ?></p>
</section>
<section class="container legal-wrap">
  <article class="card legal-card">
    <?php foreach ($page['sections'] as $sec): ?>
      <section class="legal-sec">
        <h2><?= e($sec['h']) ?></h2>
        <?php foreach ($sec['p'] ?? [] as $p): ?><p><?= LegalController::linkify($p) ?></p><?php endforeach; ?>
        <?php if (!empty($sec['list'])): ?>
          <ul><?php foreach ($sec['list'] as $li): ?><li><?= LegalController::linkify($li) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  </article>
  <?= partial('legal-nav') ?>
</section>
