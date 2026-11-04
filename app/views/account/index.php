<?php /** @var array $user */ ?>
<section class="container container-narrow page-head">
  <span class="eyebrow"><?= e(t('account.eyebrow')) ?></span>
  <h1><?= e(t('account.h1')) ?> <span class="hl"><?= e($user['username']) ?></span></h1>
  <p class="muted"><?= e(t('account.joined', ['date' => fmt_date($user['created_at'], false)])) ?> · <?= e(t('account.plan')) ?>: <span class="chip chip-outline"><?= e($user['plan']) ?></span></p>
</section>
<section class="container container-narrow stack">
  <?= partial('form-errors', ['errors' => $errors]) ?>
  <?php if ((int) $user['must_change_password'] === 1): ?>
    <div class="announce"><?= icon('lock', 18) ?> <?= e(t('account.must_change')) ?></div>
  <?php endif; ?>

  <?php if ($newCode): ?>
    <div class="card card-hl">
      <h2><?= e(t('account.recovery.new')) ?></h2>
      <p class="muted"><?= e(t('auth.recovery.sub')) ?></p>
      <div class="recovery-code" id="rc"><?= e($newCode) ?></div>
      <button type="button" class="btn btn-secondary btn-sm mt-16" data-copy="#rc"><?= icon('copy', 16) ?> <span data-copy-label><?= e(t('common.copy')) ?></span></button>
    </div>
  <?php endif; ?>

  <div class="card" id="ganti-password">
    <h2><?= e(t('account.pw.h')) ?></h2>
    <form class="form" method="post" action="<?= e(url('akun')) ?>" novalidate>
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <?= partial('pw-field', ['name' => 'old_password', 'id' => 'old_password', 'label' => t('account.pw.old'), 'autocomplete' => 'current-password']) ?>
      <?= partial('pw-field', ['name' => 'password', 'id' => 'password', 'label' => t('auth.new_password'), 'autocomplete' => 'new-password', 'guide' => true]) ?>
      <?= partial('pw-field', ['name' => 'password_confirm', 'id' => 'password_confirm', 'label' => t('auth.password_confirm'), 'autocomplete' => 'new-password', 'confirmOf' => 'password']) ?>
      <div><button class="btn btn-dark" type="submit"><?= e(t('account.pw.submit')) ?></button></div>
    </form>
  </div>

  <div class="card">
    <h2><?= e(t('account.prefs.h')) ?></h2>
    <form class="form" method="post" action="<?= e(url('akun')) ?>">
      <?= csrf_field() ?><input type="hidden" name="action" value="prefs">
      <div class="grid-2">
        <div class="field">
          <label for="lang"><?= e(t('auth.lang')) ?></label>
          <select class="input" id="lang" name="lang">
            <option value="id" <?= $user['lang'] === 'id' ? 'selected' : '' ?>>Bahasa Indonesia</option>
            <option value="en" <?= $user['lang'] === 'en' ? 'selected' : '' ?>>English</option>
          </select>
        </div>
        <div class="field">
          <label for="target_score"><?= e(t('account.target')) ?></label>
          <input class="input mono" type="number" id="target_score" name="target_score" min="310" max="677" step="1" value="<?= (int) $user['target_score'] ?>">
        </div>
      </div>
      <div><button class="btn btn-dark" type="submit"><?= e(t('common.save')) ?></button></div>
    </form>
  </div>

  <div class="card">
    <h2><?= e(t('account.recovery.h')) ?></h2>
    <p class="muted"><?= e(t('account.recovery.p')) ?></p>
    <form class="form" method="post" action="<?= e(url('akun')) ?>">
      <?= csrf_field() ?><input type="hidden" name="action" value="recovery">
      <?= partial('pw-field', ['name' => 'confirm_password', 'id' => 'confirm_password_rc', 'label' => t('account.confirm_with_password'), 'autocomplete' => 'current-password']) ?>
      <div><button class="btn btn-secondary" type="submit"><?= icon('refresh', 18) ?> <?= e(t('account.recovery.submit')) ?></button></div>
    </form>
  </div>

  <div class="card" id="perangkat">
    <h2><?= e(t('account.devices.h')) ?></h2>
    <?php if ($user['role'] !== 'admin'): ?><p class="muted"><?= e(t('account.devices.d', ['n' => $sessionLimit])) ?></p><?php endif; ?>
    <ul class="device-list">
      <?php foreach ($devices as $d): $isThis = (int) $d['id'] === (int) $currentSession; ?>
      <li class="device-row">
        <span class="device-icon" aria-hidden="true"><?= icon(preg_match('/Android|iPhone|iPad/', $d['device_label']) ? 'phone' : 'monitor', 20) ?></span>
        <div class="device-info">
          <span class="device-name"><strong><?= e($d['device_label'] ?: '-') ?></strong><?php if ($isThis): ?> <span class="chip chip-xs chip-good"><?= e(t('account.devices.this')) ?></span><?php endif; ?></span>
          <span class="small muted"><?= e(t('account.devices.last', ['t' => fmt_date($d['last_seen_at'])])) ?> · <?= e(t('account.devices.since', ['t' => fmt_date($d['created_at'])])) ?></span>
        </div>
        <?php if (!$isThis): ?>
        <form method="post" action="<?= e(url('akun')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="revoke_session"><input type="hidden" name="session_id" value="<?= (int) $d['id'] ?>">
          <button class="btn btn-secondary btn-sm" type="submit"><?= e(t('account.devices.revoke')) ?></button></form>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($devices) > 1): ?>
    <form method="post" action="<?= e(url('akun')) ?>" class="mt-16"><?= csrf_field() ?><input type="hidden" name="action" value="revoke_others">
      <button class="btn btn-dark btn-sm" type="submit"><?= icon('logout', 16) ?> <?= e(t('account.devices.revoke_others')) ?></button></form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><?= e(t('account.logout.h')) ?></h2>
    <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?><button class="btn btn-secondary" type="submit"><?= icon('logout', 18) ?> <?= e(t('nav.logout')) ?></button></form>
  </div>

  <?php if ($user['role'] !== 'admin'): ?>
  <div class="card" style="border-color:var(--bad)">
    <h2><?= e(t('account.delete.h')) ?></h2>
    <p class="muted"><?= e(t('account.delete.p')) ?></p>
    <form class="form" method="post" action="<?= e(url('akun')) ?>">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <div class="field">
        <label for="confirm_username"><?= e(t('account.delete.type', ['name' => $user['username']])) ?></label>
        <input class="input" id="confirm_username" name="confirm_username" autocomplete="off" required>
      </div>
      <div><button class="btn btn-danger" type="submit"><?= icon('trash', 18) ?> <?= e(t('account.delete.submit')) ?></button></div>
    </form>
  </div>
  <?php endif; ?>
</section>
