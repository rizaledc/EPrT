<?php
/** @var array $c @var array $contact */
?>
<section class="container page-head">
  <span class="eyebrow"><?= e($c['eyebrow']) ?></span>
  <h1><?= e($c['h1']) ?></h1>
  <p><?= e($c['lead']) ?></p>
</section>
<section class="container legal-wrap">
  <div class="contact-grid">
    <a class="card contact-card" href="mailto:<?= e($contact['email']) ?>">
      <span class="contact-ic" aria-hidden="true"><?= icon('file', 22) ?></span>
      <span class="eyebrow"><?= e($c['email']) ?></span>
      <strong><?= e($contact['email']) ?></strong>
    </a>
    <a class="card contact-card" href="<?= e($contact['wa_link']) ?>" target="_blank" rel="noopener">
      <span class="contact-ic" aria-hidden="true"><?= icon('users', 22) ?></span>
      <span class="eyebrow"><?= e($c['wa']) ?></span>
      <strong><?= e($contact['wa']) ?></strong>
    </a>
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
