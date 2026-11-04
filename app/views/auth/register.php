<div class="auth-wrap">
  <?= partial('auth-side') ?>
  <div class="auth-main">
    <div class="auth-card">
      <span class="eyebrow"><?= e(t('auth.register.eyebrow')) ?></span>
      <h1 class="mt-8"><?= e(t('auth.register.h1')) ?></h1>
      <p class="muted"><?= e(t('auth.register.sub')) ?></p>
      <?php if (!$open): ?>
        <div class="form-errors" role="alert"><?= e(t('auth.register.closed')) ?></div>
      <?php else: ?>
      <form class="form mt-24" method="post" action="<?= e(url('daftar')) ?>" novalidate data-register-form>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <?= partial('form-errors', ['errors' => $errors]) ?>
        <div class="field">
          <label for="username"><?= e(t('auth.username')) ?></label>
          <input class="input" id="username" name="username" value="<?= e(old('username')) ?>" autocomplete="username" required minlength="4" maxlength="20" pattern="[A-Za-z_][A-Za-z0-9_]{3,19}" autocapitalize="none" spellcheck="false" data-username aria-describedby="username-hint">
          <span class="hint" id="username-hint" data-username-hint><?= e(t('auth.username_hint')) ?></span>
        </div>
        <?= partial('pw-field', ['name' => 'password', 'id' => 'password', 'label' => t('auth.password'), 'autocomplete' => 'new-password', 'guide' => true]) ?>
        <?= partial('pw-field', ['name' => 'password_confirm', 'id' => 'password_confirm', 'label' => t('auth.password_confirm'), 'autocomplete' => 'new-password', 'confirmOf' => 'password']) ?>
        <div class="field">
          <label for="lang"><?= e(t('auth.lang')) ?></label>
          <select class="input" id="lang" name="lang">
            <option value="id" <?= (old('lang', active_lang()) === 'id') ? 'selected' : '' ?>>Bahasa Indonesia</option>
            <option value="en" <?= (old('lang', active_lang()) === 'en') ? 'selected' : '' ?>>English</option>
          </select>
        </div>
        <label class="check"><input type="checkbox" name="agree" value="1" required data-agree> <span><?= e(t('auth.agree')) ?></span></label>
        <button class="btn btn-primary btn-block" type="submit" disabled data-register-submit><span><?= e(t('auth.register.submit')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 18) ?></span></button>
        <p class="small center mb-0"><?= e(t('auth.have_account')) ?> <a href="<?= e(url('masuk')) ?>"><?= e(t('nav.login')) ?></a></p>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
