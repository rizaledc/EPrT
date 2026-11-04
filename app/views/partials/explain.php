<?php
/**
 * Kartu pembahasan.
 * @var array $q       baris soal (options/why_wrong_* sudah array)
 * @var ?int  $chosen  pilihan pengguna (null = tidak menjawab)
 * @var ?array $passage passage (untuk transkrip listening)
 */
$seen = [];
$letters = ['A', 'B', 'C', 'D'];
$correct = (int) $q['correct_index'];
$chosen = isset($chosen) && $chosen !== null && $chosen !== '' ? (int) $chosen : null;
$isCorrect = $chosen === $correct;
$state = $chosen === null ? 'is-blank' : ($isCorrect ? 'is-correct' : 'is-wrong');
$whyWrong = active_lang() === 'en' && !empty($q['why_wrong_en']) ? $q['why_wrong_en'] : ($q['why_wrong_id'] ?? []);
$tagInfo = RuleTag::map();
?>
<section class="explain <?= $state ?>" aria-label="<?= e(t('explain.label')) ?>">
  <div class="explain-verdict">
    <?php if ($chosen === null): ?>
      <span class="vi"><?= icon('circle', 18) ?></span><span><?= e(t('explain.blank', ['letter' => $letters[$correct]])) ?></span>
    <?php elseif ($isCorrect): ?>
      <span class="vi"><?= icon('check', 20) ?></span><span><?= e(t('explain.correct')) ?></span>
    <?php else: ?>
      <span class="vi"><?= icon('x', 20) ?></span><span><?= e(t('explain.wrong', ['letter' => $letters[$correct]])) ?></span>
    <?php endif; ?>
  </div>
  <div class="explain-body">
    <?php if ($chosen !== null && !$isCorrect && !empty($whyWrong[(string) $chosen])): ?>
      <div>
        <h4><?= icon('x', 14) ?> <?= e(t('explain.your_choice', ['letter' => $letters[$chosen]])) ?></h4>
        <p><?= rich_inline((string) $whyWrong[(string) $chosen], $seen) ?></p>
      </div>
    <?php endif; ?>
    <div>
      <h4><?= icon('check', 14) ?> <?= e($isCorrect ? t('explain.why_correct_yes') : t('explain.why_correct', ['letter' => $letters[$correct]])) ?></h4>
      <div><?= rich(tr($q, 'why_correct'), $seen) ?><?= fallback_chip($q, 'why_correct') ?></div>
    </div>
    <div>
      <h4><?= icon('list', 14) ?> <?= e(t('explain.why_wrong')) ?></h4>
      <ul class="why-wrong">
        <?php foreach ($q['options'] as $i => $opt): if ($i === $correct) continue; ?>
          <li class="<?= $chosen === $i ? 'chosen' : '' ?>"><span class="letter"><?= $letters[$i] ?></span>
            <span><?php if ($chosen === $i): ?><strong><?= e(t('explain.your_pick')) ?></strong> · <?php endif; ?><?= rich_inline((string) ($whyWrong[(string) $i] ?? '–'), $seen) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php if (tr($q, 'analogy') !== ''): ?>
    <div class="analogy">
      <h4><?= icon('bulb', 14) ?> <?= e(t('explain.analogy')) ?></h4>
      <p><?= rich_inline(tr($q, 'analogy'), $seen) ?><?= fallback_chip($q, 'analogy') ?></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($q['rule_tags'])): ?>
    <div>
      <h4><?= icon('tag', 14) ?> <?= e(t('explain.rule')) ?></h4>
      <div class="tag-chips">
        <?php foreach ($q['rule_tags'] as $tag): $ti = $tagInfo[$tag] ?? null; ?>
          <a class="chip chip-<?= e($ti['section'] ?? 'listening') ?>" href="<?= e(url('kamus' . (!empty($ti['glossary_slug']) ? '#' . $ti['glossary_slug'] : ''))) ?>" title="<?= e($ti ? tr($ti, 'name') : $tag) ?>"><?= e($tag) ?><?php if ($ti): ?> · <?= e(tr($ti, 'name')) ?><?php endif; ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if (tr($q, 'tip') !== ''): ?>
    <div class="tip">
      <h4><?= icon('sparkle', 14) ?> <?= e(t('explain.tip')) ?></h4>
      <p><?= rich_inline(tr($q, 'tip'), $seen) ?></p>
    </div>
    <?php endif; ?>
    <?php if ($q['section'] === 'listening' && !empty($passage['script'])): ?>
    <div>
      <h4><?= icon('headphones', 14) ?> <?= e(t('explain.transcript')) ?></h4>
      <div class="transcript">
        <?php foreach ($passage['script'] as $line):
          $txt = e($line['text']);
          if (!empty($q['key_sentence'])) {
              $ks = e($q['key_sentence']);
              $txt = str_replace($ks, '<mark>' . $ks . '</mark>', $txt);
          } ?>
          <div><span class="sp"><?= e(Speaker::label($line['speaker'])) ?></span><?= $txt ?></div>
        <?php endforeach; ?>
        <?php if (!empty($q['prompt'])): ?><div><span class="sp"><?= e(Speaker::label('N')) ?></span><em><?= e($q['prompt']) ?></em></div><?php endif; ?>
      </div>
      <?php if (!empty($q['key_sentence'])): ?>
        <p class="mt-8 small"><strong><?= e(t('explain.key_sentence')) ?>:</strong> “<?= e($q['key_sentence']) ?>”
        <?php if (!empty($q['key_translation_id']) && active_lang() === 'id'): ?><br><strong><?= e(t('explain.translation')) ?>:</strong> <?= e($q['key_translation_id']) ?><?php endif; ?></p>
      <?php endif; ?>
      <?php if (!empty($q['liaison_note'])): ?><p class="small muted"><?= icon('volume', 14) ?> <?= e($q['liaison_note']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($q['section'] === 'reading' && !empty($q['evidence_quote'])): ?>
    <div>
      <h4><?= icon('book', 14) ?> <?= e(t('explain.evidence')) ?></h4>
      <blockquote data-evidence><?= e($q['evidence_quote']) ?></blockquote>
    </div>
    <?php endif; ?>
  </div>
</section>
