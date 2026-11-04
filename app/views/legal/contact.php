<?php
/** @var array $c @var array $contact */
$hasWa = $contact['wa'] !== '';
?>
<section class="container page-head">
  <span class="eyebrow"><?= e($c['eyebrow']) ?></span>
  <h1><?= e($c['h1']) ?></h1>
  <p><?= e($c['lead']) ?></p>
</section>
<section class="container legal-wrap">
  <div class="contact-grid <?= $hasWa ? '' : 'contact-single' ?>">
    <div class="card contact-card">
      <span class="contact-ic" aria-hidden="true"><?= icon('file', 22) ?></span>
      <span class="eyebrow"><?= e($c['email']) ?></span>
      <strong class="contact-email" data-copy-text><?= e($contact['email']) ?></strong>
      <div class="contact-actions">
        <button type="button" class="btn btn-secondary btn-sm" data-copy-btn data-copied="<?= e($c['copied']) ?>"><?= icon('copy', 16) ?> <span><?= e($c['copy']) ?></span></button>
        <a class="btn btn-primary btn-sm" href="mailto:<?= e($contact['email']) ?>"><span><?= e($c['write']) ?></span></a>
      </div>
      <p class="small muted mb-0" data-copy-status role="status" aria-live="polite"></p>
    </div>
    <?php if ($hasWa): ?>
      <a class="card contact-card" href="<?= e((string) $contact['wa_link']) ?>" target="_blank" rel="noopener">
        <span class="contact-ic" aria-hidden="true"><?= icon('users', 22) ?></span>
        <span class="eyebrow"><?= e($c['wa']) ?></span>
        <strong><?= e($contact['wa']) ?></strong>
      </a>
    <?php endif; ?>
  </div>
  <article class="card legal-card mt-24">
    <section class="legal-sec">
      <h2><?= e($c['hours_h']) ?></h2>
      <p><?= e($c['hours']) ?></p>
    </section>
    <section class="legal-sec">
      <h2><?= e($c['tips_h']) ?></h2>
      <ul><?php foreach ($c['tips'] as $li): ?><li><?= e($li) ?></li><?php endforeach; ?></ul>
    </section>
  </article>
  <?= partial('legal-nav') ?>
</section>
