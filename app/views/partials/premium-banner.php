<?php /** @var bool $isPremium @var bool $required @var bool $guest */
$q = Premium::quota();
$quota = $q ? ' ' . t('premium.quota', ['p' => Coins::rupiah($q['price']), 'n' => $q['left'], 't' => $q['size']]) : '';
?>
<?php if (!empty($guest)): ?>
  <p class="trial-banner"><?= icon('sparkle', 16) ?><span><?= e(t('premium.map_guest')) ?><?= e($quota) ?> <a href="<?= e(url('harga')) ?>"><?= e(t('premium.see')) ?></a></span></p>
<?php elseif ($isPremium): ?>
<?php elseif (!$required): ?>
  <p class="trial-banner"><?= icon('sparkle', 16) ?><span><?= e(t('map.trial_banner')) ?> <a href="<?= e(url('harga')) ?>"><?= e(t('premium.see')) ?></a></span></p>
<?php else: ?>
  <p class="trial-banner premium-lock"><?= icon('lock', 16) ?><span><?= e(t('premium.map_locked')) ?><?= e($quota) ?> <a href="<?= e(url('harga')) ?>"><?= e(t('premium.unlock')) ?></a></span></p>
<?php endif; ?>
