<?php /** @var array $o @var int $balance */
$isPrem = ($o['product'] ?? 'coins') === 'premium';
$item = $isPrem ? t('premium.item') : t('coins.pkg', ['n' => (int) $o['coins']]);
$until = $isPrem ? (string) q_val('SELECT premium_until FROM users WHERE id = ?', [$o['user_id']]) : '';
?>
<section class="container container-narrow page-head">
  <span class="eyebrow"><?= e(t('coins.eyebrow')) ?></span>
  <h1><?= e(t('coins.order.h', ['code' => $o['code']])) ?></h1>
</section>
<section class="container container-narrow stack">
  <div class="card <?= $o['status'] === 'paid' ? 'card-hl' : '' ?>" <?= $o['status'] === 'pending' ? 'data-order-poll' : '' ?>>
    <div class="row-between">
      <span class="chip chip-ink"><?= icon($isPrem ? 'sparkle' : 'coin', 14) ?> <?= e($item) ?> · <?= e(Coins::rupiah((int) $o['amount'])) ?></span>
      <span class="chip <?= $o['status'] === 'paid' ? 'chip-good' : ($o['status'] === 'pending' ? 'chip-lime' : 'chip-outline') ?>"><?= e(t('coins.st.' . $o['status'])) ?></span>
    </div>
    <p class="mt-16"><?= e($isPrem && $o['status'] === 'paid' ? t('premium.order_paid', ['d' => fmt_date($until, false)]) : t($o['status'] === 'pending' && $o['pay_link'] ? 'coins.order.pay_hint' : 'coins.order.' . $o['status'], ['n' => (int) $o['coins'], 'b' => $balance])) ?></p>
    <div class="row">
      <?php if ($o['status'] === 'pending'): ?>
        <?php if ($o['pay_link']): ?><a class="btn btn-primary" href="<?= e($o['pay_link']) ?>" target="_blank" rel="noopener"><span><?= e(t('coins.pay_now')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-up-right', 16) ?></span></a><?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(url('koin/pesanan/' . $o['code'])) ?>"><?= icon('refresh', 16) ?> <?= e(t('coins.check')) ?></a>
      <?php elseif ($o['status'] === 'paid' && $isPrem): ?>
        <a class="btn btn-primary" href="<?= e(url('latihan')) ?>"><span><?= e(t('nav.practice')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 16) ?></span></a>
      <?php elseif ($o['status'] === 'paid'): ?>
        <a class="btn btn-primary" href="<?= e(url('rekomendasi')) ?>"><span><?= e(t('coins.to_advice')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 16) ?></span></a>
        <a class="btn btn-secondary" href="<?= e(url('latihan')) ?>"><?= icon('home', 16) ?> <?= e(t('nav.practice')) ?></a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= e(url($isPrem ? 'harga' : 'koin')) ?>"><?= e($isPrem ? t('premium.back') : t('coins.back')) ?></a>
    </div>
  </div>
</section>
