<?php /** @var array $attempts @var array $book @var array $tags */ ?>
<section class="container page-head">
  <span class="eyebrow"><?= e(t('history.eyebrow')) ?></span>
  <h1><?= e(t('history.h1a')) ?> <span class="hl"><?= e(t('history.h1b')) ?></span></h1>
</section>
<section class="container">
  <nav class="tabs" aria-label="<?= e(t('history.tabs')) ?>">
    <a href="#percobaan" data-tab="percobaan" aria-current="true"><?= e(t('history.tab_attempts')) ?></a>
    <a href="#kesalahan" data-tab="kesalahan" aria-current="false"><?= e(t('history.tab_mistakes')) ?> (<?= count($book) ?>)</a>
  </nav>

  <div id="percobaan" data-panel="percobaan">
    <?php if (!$attempts): ?>
      <div class="card empty"><?= e(t('history.empty')) ?> <a href="<?= e(url('latihan')) ?>"><?= e(t('nav.practice')) ?></a></div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th><?= e(t('history.col.date')) ?></th><th><?= e(t('history.col.day')) ?></th><th><?= e(t('history.col.section')) ?></th><th><?= e(t('history.col.mode')) ?></th><th class="num"><?= e(t('history.col.score')) ?></th><th class="num"><?= e(t('history.col.time')) ?></th><th></th></tr></thead>
        <tbody>
          <?php foreach ($attempts as $a): ?>
            <tr>
              <td><?= e(fmt_date($a['started_at'])) ?></td>
              <td><?= e(tr($a, 'title')) ?></td>
              <td><span class="sec-dot <?= e($a['section']) ?>"></span> <?= e(section_label($a['section'])) ?></td>
              <td><?= e(t('mode.' . $a['mode'])) ?></td>
              <td class="num mono"><?= $a['finished_at'] ? (int) $a['correct'] . '/' . (int) $a['total'] : '-' ?></td>
              <td class="num mono"><?= $a['finished_at'] ? fmt_duration((int) $a['time_spent_s']) : '-' ?></td>
              <td><?php if ($a['finished_at']): ?><a href="<?= e(url('hasil/' . $a['id'])) ?>"><?= e(t('history.view')) ?></a>
                <?php else: $r = Rumah::byId((int) $a['rumah_id']); ?><a href="<?= e($a['mode'] === 'review' ? url('sesi/' . $a['id']) : Rumah::url($r, $a['section'])) ?>"><?= e(t('history.resume')) ?></a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div id="kesalahan" data-panel="kesalahan" hidden>
    <p class="muted prose"><?= e(t('history.book_desc')) ?></p>
    <?php if (!$book): ?>
      <div class="card empty"><?= e(t('history.book_empty')) ?></div>
    <?php endif; ?>
    <div class="stack">
      <?php foreach ($book as $g): $ti = $tags[$g['tag']] ?? null; ?>
        <div class="card card-sm mistake-group">
          <div>
            <span class="chip chip-<?= e($g['section']) ?>"><?= e($g['tag']) ?></span>
            <h3 class="mt-8 mb-0"><?= e($ti ? tr($ti, 'name') : $g['tag']) ?></h3>
            <p class="small muted mb-0"><?= e(t('history.group_meta', ['q' => count($g['questions']), 'w' => $g['wrong']])) ?>
              <?php if (!empty($ti['glossary_slug'])): ?> · <a href="<?= e(url('kamus#' . $ti['glossary_slug'])) ?>"><?= e(t('glossary.open')) ?></a><?php endif; ?></p>
          </div>
          <div class="row">
            <span class="mistake-count"><?= (int) $g['wrong'] ?>×</span>
            <form method="post" action="<?= e(url('latih-ulang')) ?>"><?= csrf_field() ?><input type="hidden" name="tag" value="<?= e($g['tag']) ?>">
              <button class="btn btn-primary btn-sm" type="submit"><span><?= e(t('history.drill')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('replay', 14) ?></span></button></form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
