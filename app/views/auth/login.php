<div class="auth-wrap">
  <?= partial('auth-side') ?>
  <div class="auth-main">
    <div class="auth-card">
      <span class="eyebrow"><?= e(t('auth.login.eyebrow')) ?></span>
      <h1 class="mt-8"><?= e(t('auth.login.h1')) ?></h1>
      <form class="form mt-24" method="post" action="<?= e(url('masuk')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <?php if ($error): ?><div class="form-errors" role="alert"><?= e($error) ?></div><?php endif; ?>
        <div class="field">
          <label for="username"><?= e(t('auth.username')) ?></label>
          <input class="input" id="username" name="username" value="<?= e(old('username')) ?>" autocomplete="username" required autocapitalize="none" spellcheck="false" maxlength="40">
        </div>
        <?= partial('pw-field', ['name' => 'password', 'id' => 'password', 'label' => t('auth.password'), 'autocomplete' => 'current-password']) ?>
        <div class="row-between">
          <label class="check"><input type="checkbox" name="remember" value="1"> <span><?= e(t('auth.remember')) ?></span></label>
          <a class="small" href="<?= e(url('lupa-password')) ?>"><?= e(t('auth.forgot_link')) ?></a>
        </div>
        <button class="btn btn-primary btn-block" type="submit"><span><?= e(t('auth.login.submit')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 18) ?></span></button>
        <p class="small center mb-0"><?= e(t('auth.no_account')) ?> <a href="<?= e(url('daftar' . ($next !== '/latihan' ? '?next=' . rawurlencode($next) : ''))) ?>"><?= e(t('nav.register')) ?></a></p>
      </form>
    </div>
  </div>
</div>
