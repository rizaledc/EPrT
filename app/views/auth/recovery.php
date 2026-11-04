<section class="container container-narrow" style="padding-top:56px;padding-bottom:72px">
  <span class="eyebrow"><?= e(t('auth.recovery.eyebrow')) ?></span>
  <h1 class="mt-8"><?= e(t('auth.recovery.h1')) ?></h1>
  <p class="muted prose"><?= e(t('auth.recovery.sub')) ?></p>
  <div class="card mt-24">
    <div class="recovery-code" id="rc" data-copy-source><?= e($code) ?></div>
    <div class="row mt-16">
      <button type="button" class="btn btn-secondary btn-sm" data-copy="#rc"><?= icon('copy', 16) ?> <span data-copy-label><?= e(t('common.copy')) ?></span></button>
      <span class="small muted"><?= e(t('auth.recovery.where')) ?></span>
    </div>
    <form class="form mt-24" method="post" action="<?= e(url('daftar/kode-pemulihan')) ?>">
      <?= csrf_field() ?>
      <label class="check"><input type="checkbox" name="saved" value="1" required data-saved-check> <span><?= e(t('auth.recovery.saved')) ?></span></label>
      <button class="btn btn-primary" type="submit" disabled data-saved-submit><span><?= e(t('auth.recovery.continue')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 18) ?></span></button>
    </form>
  </div>
</section>
