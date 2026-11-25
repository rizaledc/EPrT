<?php /** @var bool $isPremium @var bool $required */ ?>
<?php if ($isPremium): ?>
<?php elseif (!$required): ?>
  <p class="trial-banner"><?= icon('sparkle', 16) ?><span><?= e(t('map.trial_banner')) ?> <a href="<?= e(url('harga')) ?>"><?= e(t('premium.see')) ?></a></span></p>
<?php else: ?>
  <p class="trial-banner premium-lock"><?= icon('lock', 16) ?><span><?= e(t('premium.map_locked')) ?> <a href="<?= e(url('harga')) ?>"><?= e(t('premium.unlock')) ?></a></span></p>
<?php endif; ?>
