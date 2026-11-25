<?php /** @var array $o @var int $balance */ ?>
<section class="container container-narrow page-head">
  <span class="eyebrow"><?= e(t('coins.eyebrow')) ?></span>
  <h1><?= e(t('coins.order.h', ['code' => $o['code']])) ?></h1>
</section>
<section class="container container-narrow stack">
  <div class="card <?= $o['status'] === 'paid' ? 'card-hl' : '' ?>">
    <div class="row-between">
      <span class="chip chip-ink"><?= icon('coin', 14) ?> <?= e(t('coins.pkg', ['n' => (int) $o['coins']])) ?> · <?= e(Coins::rupiah((int) $o['amount'])) ?></span>
      <span class="chip <?= $o['status'] === 'paid' ? 'chip-good' : ($o['status'] === 'pending' ? 'chip-lime' : 'chip-outline') ?>"><?= e(t('coins.st.' . $o['status'])) ?></span>
    </div>
    <p class="mt-16"><?= e(!empty($autoPay) ? t('coins.order.redirect') : t('coins.order.' . $o['status'], ['n' => (int) $o['coins'], 'b' => $balance])) ?></p>
    <div class="row">
      <?php if ($o['status'] === 'pending'): ?>
        <?php if ($o['pay_link']): ?><a class="btn btn-primary" href="<?= e($o['pay_link']) ?>" rel="noopener" <?= !empty($autoPay) ? 'data-autogo' : '' ?>><span><?= e(t('coins.pay_now')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-up-right', 16) ?></span></a><?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(url('koin/pesanan/' . $o['code'])) ?>"><?= icon('refresh', 16) ?> <?= e(t('coins.check')) ?></a>
      <?php elseif ($o['status'] === 'paid'): ?>
        <a class="btn btn-primary" href="<?= e(url('rekomendasi')) ?>"><span><?= e(t('coins.to_advice')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 16) ?></span></a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= e(url('koin')) ?>"><?= e(t('coins.back')) ?></a>
    </div>
  </div>
</section>
