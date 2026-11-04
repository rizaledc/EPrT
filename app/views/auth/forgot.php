<div class="auth-wrap">
  <?= partial('auth-side') ?>
  <div class="auth-main">
    <div class="auth-card">
      <span class="eyebrow"><?= e(t('auth.forgot.eyebrow')) ?></span>
      <h1 class="mt-8"><?= e(t('auth.forgot.h1')) ?></h1>
      <p class="muted"><?= e(t('auth.forgot.sub')) ?></p>
      <form class="form mt-24" method="post" action="<?= e(url('lupa-password')) ?>" novalidate>
        <?= csrf_field() ?>
        <?= partial('form-errors', ['errors' => $errors]) ?>
        <div class="field">
          <label for="username"><?= e(t('auth.username')) ?></label>
          <input class="input" id="username" name="username" value="<?= e(old('username')) ?>" autocomplete="username" required autocapitalize="none">
        </div>
        <div class="field">
          <label for="code"><?= e(t('auth.forgot.code')) ?></label>
          <input class="input mono" id="code" name="code" placeholder="XXXX-XXXX-XXXX" autocomplete="off" required autocapitalize="characters" spellcheck="false" maxlength="20">
        </div>
        <?= partial('pw-field', ['name' => 'password', 'id' => 'password', 'label' => t('auth.new_password'), 'autocomplete' => 'new-password', 'guide' => true]) ?>
        <?= partial('pw-field', ['name' => 'password_confirm', 'id' => 'password_confirm', 'label' => t('auth.password_confirm'), 'autocomplete' => 'new-password', 'confirmOf' => 'password']) ?>
        <button class="btn btn-primary btn-block" type="submit"><span><?= e(t('auth.forgot.submit')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 18) ?></span></button>
        <p class="small muted mb-0"><?= e(t('auth.forgot.no_code')) ?></p>
      </form>
    </div>
  </div>
</div>
