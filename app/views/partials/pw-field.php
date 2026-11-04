<?php
/** @var string $name @var string $id @var string $label @var string $autocomplete @var bool $guide */
$guide = $guide ?? false;
?>
<div class="field">
  <label for="<?= e($id) ?>"><?= e($label) ?></label>
  <div class="input-wrap">
    <input class="input" type="password" id="<?= e($id) ?>" name="<?= e($name) ?>" autocomplete="<?= e($autocomplete) ?>" maxlength="72" required <?= $guide ? 'data-pw-input aria-describedby="' . e($id) . '-guide"' : '' ?> <?= !empty($confirmOf) ? 'data-pw-confirm="' . e($confirmOf) . '"' : '' ?>>
    <button type="button" class="eye" data-pw-eye aria-label="<?= e(t('auth.show_password')) ?>" aria-pressed="false"><span class="eye-on"><?= icon('eye', 20) ?></span><span class="eye-off hidden"><?= icon('eye-off', 20) ?></span></button>
  </div>
  <?php if ($guide): ?>
  <div id="<?= e($id) ?>-guide">
    <ul class="pw-checklist" data-pw-checklist>
      <?php foreach (['length', 'upper', 'lower', 'digit', 'symbol'] as $r): ?>
        <li data-rule="<?= $r ?>"><span class="mk mk-no" aria-hidden="true">○</span><span class="mk mk-ok" aria-hidden="true"><?= icon('check', 16) ?></span><span><?= e(t('auth.rule.' . $r)) ?></span><span class="sr-only" data-rule-state><?= e(t('auth.rule.not_met')) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <div class="row-between mt-8"><span class="pw-meter-label" data-pw-label aria-live="polite"><?= e(t('auth.meter.0')) ?></span></div>
    <div class="pw-meter" data-pw-meter data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <p class="hint mt-8 mb-0"><?= e(t('auth.pw_example_hint')) ?> <code><?= e(Auth::EXAMPLE_PASSWORD) ?></code>. <?= e(t('auth.pw_example_note')) ?></p>
  </div>
  <?php endif; ?>
  <?php if (!empty($confirmOf)): ?><span class="error-text hidden" data-pw-mismatch><?= e(t('auth.err.pw_mismatch')) ?></span><?php endif; ?>
</div>
