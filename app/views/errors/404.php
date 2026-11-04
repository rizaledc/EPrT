<section class="error-page">
  <div>
    <div class="code">404</div>
    <h1 class="mt-16" style="font-size:clamp(28px,4vw,44px)"><?= e(t('error.404.h')) ?></h1>
    <p class="muted"><?= e(t('error.404.p')) ?></p>
    <div class="row mt-24" style="justify-content:center"><?= btn_primary(t('error.home'), url('/')) ?></div>
  </div>
</section>
