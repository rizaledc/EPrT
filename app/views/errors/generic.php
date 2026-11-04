<section class="error-page">
  <div>
    <div class="code"><?= (int) ($code ?? 400) ?></div>
    <h1 class="mt-16" style="font-size:clamp(28px,4vw,44px)"><?= e($title ?? '') ?></h1>
    <?php if (!empty($message)): ?><p class="muted"><?= e($message) ?></p><?php endif; ?>
    <div class="row mt-24" style="justify-content:center"><?= btn_primary(t('error.home'), url('/')) ?></div>
  </div>
</section>
