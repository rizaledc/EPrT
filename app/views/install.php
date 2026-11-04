<section class="container container-narrow" style="padding-top:56px;padding-bottom:72px">
  <span class="eyebrow">INSTALL</span>
  <h1 class="mt-8"><?= e(t('install.h1')) ?></h1>
  <p class="muted prose"><?= e(t('install.sub')) ?></p>
  <?php if ($log): ?>
    <div class="card mt-24"><ul class="rich-list">
      <?php foreach ($log as [$type, $msg]): ?>
        <li class="<?= $type === 'ok' ? 'ex-good' : ($type === 'error' ? 'ex-bad' : '') ?>"><span class="ex-mark"><?= icon($type === 'ok' ? 'check' : ($type === 'error' ? 'x' : 'circle'), 16) ?></span><span style="text-decoration:none"><?= e($msg) ?></span></li>
      <?php endforeach; ?>
    </ul></div>
  <?php endif; ?>
  <?php if ($done): ?>
    <div class="mt-24"><?= btn_primary(t('nav.login'), url('masuk')) ?></div>
  <?php else: ?>
    <form class="card form mt-24" method="post" action="<?= e(url('install')) ?>">
      <?= csrf_field() ?>
      <ol class="small muted">
        <li><?= e(t('install.req.config')) ?></li>
        <li><?= e(t('install.req.password')) ?></li>
      </ol>
      <div class="field">
        <label for="admin_password"><?= e(t('install.confirm')) ?></label>
        <input class="input" type="password" id="admin_password" name="admin_password" autocomplete="off" required>
        <span class="hint"><?= e(t('install.confirm_hint')) ?></span>
      </div>
      <div><button class="btn btn-dark" type="submit"><?= e(t('install.run')) ?></button></div>
    </form>
  <?php endif; ?>
</section>
