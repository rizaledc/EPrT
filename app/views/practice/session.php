<?php
/** @var array $attempt @var string $heading @var string $exitUrl */
$mode = $attempt['mode'];
$section = $attempt['section'];
?>
<header class="session-head">
  <div class="container session-head-inner">
    <a class="icon-btn" href="<?= e($exitUrl) ?>" data-exit aria-label="<?= e(t('session.exit')) ?>"><?= icon('arrow-left', 20) ?></a>
    <h1 class="session-title"><?= e($heading) ?>
      <small><?= $mode === 'simulation' ? e(t('session.mode_sim')) : ($mode === 'sample' ? e(t('session.mode_sample')) : ($mode === 'review' ? e(t('session.mode_review')) : e(t('session.mode_practice')))) ?></small></h1>
    <span class="chip chip-<?= e($section === 'mixed' ? 'structure' : $section) ?>" data-sec-chip><?= e($section === 'mixed' ? t('sample.mixed') : section_label($section)) ?></span>
    <span class="spacer"></span>
    <span class="session-progress" data-progress aria-live="polite">– / –</span>
    <span class="timer" data-timer hidden><?= icon('clock', 16) ?><span data-timer-text>--:--</span></span>
    <a class="btn btn-secondary btn-sm hide-sm" href="<?= e($exitUrl) ?>" data-exit><?= icon('save', 16) ?> <?= e($mode === 'sample' ? t('session.exit') : t('session.exit_save')) ?></a>
  </div>
  <div class="session-bar"><span data-bar style="width:0%"></span></div>
</header>

<div class="container session-main" data-engine>
  <noscript><div class="card"><?= e(t('session.noscript')) ?></div></noscript>

  <div class="card dots start-overlay" data-start hidden>
    <span class="eyebrow"><?= e($mode === 'simulation' ? t('session.mode_sim') : section_label($section === 'mixed' ? 'structure' : $section)) ?></span>
    <h2 class="mt-8" data-start-title><?= e(t('session.start_t')) ?></h2>
    <ul class="rich-list" data-start-rules></ul>
    <button class="btn btn-primary" type="button" data-start-btn><span><?= e(t('session.start_btn')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 18) ?></span></button>
    <p class="kbd-hint mt-24 mb-0 hide-sm"><?= t('session.kbd_hint') ?></p>
  </div>

  <div class="card center" data-loading><?= e(t('session.loading')) ?></div>

  <div class="q-layout" data-layout hidden>
    <aside class="card passage" data-passage hidden data-collapsed="true">
      <div class="row-between mb-16">
        <span class="chip chip-reading"><?= icon('book', 14) ?> <?= e(t('session.passage')) ?></span>
        <button type="button" class="btn btn-ghost btn-sm passage-toggle" data-passage-toggle aria-expanded="false"><?= e(t('session.show_passage')) ?></button>
      </div>
      <h3 data-passage-title></h3>
      <div class="passage-body" data-passage-body></div>
    </aside>
    <div>
      <article class="card q-card" data-qcard aria-live="polite">
        <div class="q-num" data-qnum></div>
        <div class="audio-box" data-audio hidden>
          <span class="eq" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
          <span class="audio-status" data-audio-status></span>
          <span class="countdown" data-countdown hidden></span>
          <span class="speed" data-speed role="group" aria-label="<?= e(t('session.speed')) ?>" hidden>
            <button type="button" data-rate="0.85" aria-pressed="false">0.85×</button><button type="button" data-rate="1" aria-pressed="true">1.0×</button><button type="button" data-rate="1.15" aria-pressed="false">1.15×</button>
          </span>
          <button type="button" class="btn btn-secondary btn-sm" data-replay hidden><?= icon('replay', 16) ?> <?= e(t('session.replay')) ?></button>
          <button type="button" class="btn btn-secondary btn-sm" data-read-transcript hidden><?= icon('file', 16) ?> <?= e(t('session.read_transcript')) ?></button>
        </div>
        <div class="transcript-box card card-sm mb-16" data-transcript-box hidden></div>
        <div class="q-prompt" data-prompt></div>
        <div class="options" data-options role="group" aria-label="<?= e(t('session.options')) ?>"></div>
        <div class="q-actions">
          <button type="button" class="btn btn-secondary btn-sm btn-flag" data-flag aria-pressed="false"><?= icon('flag', 16) ?> <span data-flag-label><?= e(t('session.flag')) ?></span></button>
          <span class="spacer"></span>
          <button type="button" class="btn btn-ghost btn-sm" data-prev><?= icon('arrow-left', 16) ?> <?= e(t('session.prev')) ?></button>
          <button type="button" class="btn btn-dark btn-sm" data-next><?= e(t('session.next')) ?> <?= icon('arrow-right', 16) ?></button>
        </div>
      </article>
      <div data-explain></div>
    </div>
  </div>

  <div class="card card-sm mt-24" data-navcard hidden>
    <div class="row-between mb-16">
      <strong><?= e(t('session.nav')) ?></strong>
      <label class="check small" data-timer-toggle-wrap hidden><input type="checkbox" data-timer-toggle checked> <span><?= e(t('session.timer_toggle')) ?></span></label>
      <button type="button" class="btn btn-primary btn-sm" data-finish><span><?= e(t('session.finish')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('check', 16) ?></span></button>
    </div>
    <div class="q-nav" data-nav></div>
    <div class="q-legend">
      <span><i style="background:var(--good)"></i><?= e(t('common.correct')) ?></span>
      <span><i style="background:var(--bad)"></i><?= e(t('common.wrong')) ?></span>
      <span><i style="border:1.5px solid var(--ink-500)"></i><?= e(t('session.legend_answered')) ?></span>
      <span><i style="border:1.5px dashed #d9bfcb"></i><?= e(t('session.legend_empty')) ?></span>
      <span><i style="background:var(--lime-300);border-radius:50%;border:2px solid var(--ink-900)"></i><?= e(t('session.flagged')) ?></span>
    </div>
  </div>
</div>

<dialog class="dlg" data-finish-dialog aria-labelledby="fin-t">
  <div class="dlg-body">
    <h2 id="fin-t"><?= e(t('session.finish_confirm_t')) ?></h2>
    <p class="muted" data-finish-text><?= e(t('session.finish_confirm_d')) ?></p>
    <div class="dlg-actions">
      <button type="button" class="btn btn-ghost" data-finish-cancel><?= e(t('session.cancel')) ?></button>
      <button type="button" class="btn btn-primary" data-finish-ok><span><?= e(t('session.finish')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('check', 16) ?></span></button>
    </div>
  </div>
</dialog>

<dialog class="dlg" data-sample-dialog aria-labelledby="sd-t">
  <div class="dlg-body">
    <h2 id="sd-t"><?= e(t('session.sample_done_t')) ?></h2>
    <p class="muted" data-sample-text></p>
    <div class="dlg-actions">
      <a class="btn btn-ghost" href="<?= e(url('/')) ?>"><?= e(t('error.home')) ?></a>
      <?= btn_primary(t('session.register_cta'), url('daftar')) ?>
    </div>
  </div>
</dialog>
