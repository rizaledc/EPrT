<?php
/** @var array $attempt @var array $rumah @var array $questions @var array $answers @var array $passages */
$letters = ['A', 'B', 'C', 'D'];
$total = max(1, (int) $attempt['total']);
$correct = (int) $attempt['correct'];
$pct = round($correct / $total * 100);
$mode = $attempt['mode'];
$isSim = $mode === 'simulation';
$scaled = null;
if ($isSim) {
    $max = ['listening' => 50, 'structure' => 40, 'reading' => 50][$attempt['section']];
    $scaled = ScoreEstimator::convert($attempt['section'], $correct / $total * $max);
}
?>
<section class="container page-head">
  <nav class="small"><a href="<?= e(url('latihan')) ?>"><?= e(t('nav.practice')) ?></a> / <a href="<?= e(Rumah::url($rumah)) ?>"><?= e(Rumah::title($rumah)) ?></a></nav>
  <div class="score-hero mt-24">
    <div class="score-big"><?= $correct ?><small>/<?= (int) $attempt['total'] ?></small></div>
    <div>
      <span class="chip chip-<?= e($attempt['section']) ?>"><?= e(section_label($attempt['section'])) ?></span>
      <?php if ($mode === 'review'): ?><span class="chip chip-outline"><?= e(t('session.mode_review')) ?></span><?php endif; ?>
      <h1 class="mt-16" style="font-size:clamp(28px,4vw,48px)"><?= e($pct >= 80 ? t('result.great') : ($pct >= 60 ? t('result.good') : t('result.keep'))) ?></h1>
      <p class="muted mb-0"><?= e(t('result.meta', ['pct' => $pct, 'time' => fmt_duration((int) $attempt['time_spent_s']), 'date' => fmt_date($attempt['finished_at'])])) ?></p>
      <?php if ($scaled !== null): ?><p class="mt-8 mb-0"><strong><?= e(t('result.scaled', ['s' => $scaled])) ?></strong> <span class="chip chip-xs"><?= e(t('score.estimated')) ?></span></p><?php endif; ?>
      <?php if ($progress && $progress['completed_at'] && $mode !== 'review' && !$isSim): ?><p class="mt-8 mb-0"><span class="chip chip-good"><?= icon('check', 14) ?> <?= e(t('result.rumah_done')) ?></span></p><?php endif; ?>
    </div>
  </div>
</section>

<section class="container">
  <div class="card card-sm">
    <div class="row-between mb-16"><strong><?= e(t('result.per_number')) ?></strong>
      <span class="tiny muted"><?= e(t('result.strip_hint')) ?></span></div>
    <div class="strip">
      <?php foreach ($attempt['question_ids'] as $i => $qid): $a = $answers[$qid] ?? null; $cls = !$a || $a['chosen_index'] === null ? 'b' : ((int) $a['is_correct'] ? 'c' : 'w'); ?>
        <a class="<?= $cls ?>" href="#q-<?= $qid ?>" title="<?= $i + 1 ?>: <?= e($cls === 'c' ? t('common.correct') : ($cls === 'w' ? t('common.wrong') : t('result.blank'))) ?>"><?= $i + 1 ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="row mt-24">
    <?php if ($wrongCount > 0): ?>
      <form method="post" action="<?= e(url('ulangi/' . $attempt['id'])) ?>"><?= csrf_field() ?>
        <button class="btn btn-primary" type="submit"><span><?= e(t('result.retry_wrong', ['n' => $wrongCount])) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('replay', 18) ?></span></button></form>
    <?php endif; ?>
    <?php if ($nextSection): ?>
      <a class="btn btn-secondary" href="<?= e(Rumah::url($rumah, $nextSection)) ?>"><?= e(t('result.next_section', ['s' => section_label($nextSection)])) ?> <?= icon('arrow-right', 16) ?></a>
    <?php else: ?>
      <a class="btn btn-secondary" href="<?= e(url('latihan')) ?>"><?= e(t('result.back_map')) ?></a>
    <?php endif; ?>
  </div>
</section>

<section class="container mt-48">
  <h2><?= e(t('result.review_h')) ?></h2>
  <div class="stack">
    <?php foreach ($attempt['question_ids'] as $i => $qid):
      $q = $questions[$qid] ?? null; if (!$q) continue;
      $a = $answers[$qid] ?? null;
      $chosen = ($a && $a['chosen_index'] !== null) ? (int) $a['chosen_index'] : null;
      $cls = $chosen === null ? 'b' : ($chosen === (int) $q['correct_index'] ? 'c' : 'w');
      $p = $q['passage_id'] ? ($passages[(int) $q['passage_id']] ?? null) : null;
      $label = $q['section'] === 'listening' ? ($q['prompt'] ?: t('result.listening_q')) : strip_tags(render_question_text((string) $q['prompt']));
    ?>
      <details class="review-item" id="q-<?= $qid ?>" <?= $cls !== 'c' && $i < 3 ? '' : '' ?>>
        <summary>
          <span class="ri-mark <?= $cls ?>"><?= icon($cls === 'c' ? 'check' : ($cls === 'w' ? 'x' : 'circle'), 16) ?></span>
          <span class="mono small"><?= $i + 1 ?></span>
          <span class="ri-text"><?= e(html_entity_decode($label, ENT_QUOTES)) ?></span>
          <?= icon('chevron-down', 18) ?>
        </summary>
        <div class="ri-inner">
          <?php if ($q['section'] === 'reading' && $p): ?>
            <details class="mb-16"><summary class="small link-arrow"><?= e(t('session.show_passage')) ?>: <?= e((string) $p['title']) ?></summary>
              <div class="passage-body small mt-16" data-evidence-host="<?= e((string) $q['evidence_quote']) ?>"><?= ReadingText::html((string) $p['body']) ?></div></details>
          <?php endif; ?>
          <?php if ($q['section'] !== 'listening'): ?><p class="q-prompt" style="font-size:17px"><?= render_question_text((string) $q['prompt']) ?></p><?php endif; ?>
          <ul class="why-wrong" style="list-style:none;padding:0;display:grid;gap:6px">
            <?php foreach ($q['options'] as $oi => $opt): ?>
              <li class="option <?= $oi === (int) $q['correct_index'] ? 'correct' : ($oi === $chosen ? 'wrong' : '') ?>" style="min-height:44px"><span class="letter"><?= $letters[$oi] ?></span><span><?= e($opt) ?></span>
                <span class="opt-mark ok"><?= icon('check', 18) ?></span><span class="opt-mark no"><?= icon('x', 18) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?= Question::render($q, $chosen, $p) ?>
        </div>
      </details>
    <?php endforeach; ?>
  </div>
</section>
