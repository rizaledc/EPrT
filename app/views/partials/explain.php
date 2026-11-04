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
<?php
$verdictPool = $chosen === null ? [] : t_list($isCorrect ? 'explain.verdict_ok' : 'explain.verdict_no');
$verdict = $chosen === null ? t('explain.blank', ['letter' => $letters[$correct]]) : '';
if ($verdictPool) {
    $pick = $verdictPool[abs(crc32(($q['id'] ?? $q['prompt'] ?? '') . '|' . $chosen)) % count($verdictPool)];
    $verdict = strtr($pick, ['{letter}' => $letters[$correct]]);
}
?>
<section class="explain <?= $state ?>" aria-label="<?= e(t('explain.label')) ?>">
  <div class="explain-verdict">
    <span class="vi"><?= icon($chosen === null ? 'circle' : ($isCorrect ? 'check' : 'x'), 20) ?></span><span><?= e($verdict) ?></span>
  </div>
  <div class="explain-body">
    <div class="ex-sec">
      <h4><?= e(t('explain.why_correct', ['letter' => $letters[$correct]])) ?></h4>
      <div class="ex-text"><?= rich(tr($q, 'why_correct'), $seen) ?><?= fallback_chip($q, 'why_correct') ?></div>
    </div>
    <div class="ex-sec">
      <h4><?= e(t('explain.why_wrong')) ?></h4>
      <ul class="why-wrong">
        <?php foreach ($q['options'] as $i => $opt): if ($i === $correct) continue; ?>
          <li class="<?= $chosen === $i ? 'chosen' : '' ?>">
            <span class="letter" aria-hidden="true"><?= $letters[$i] ?></span>
            <div class="ww-body">
              <span class="sr-only"><?= e(t('explain.option')) ?> <?= $letters[$i] ?>.</span>
              <?php if ($chosen === $i): ?><span class="pick-badge"><?= e(t('explain.your_pick')) ?></span><?php endif; ?>
              <p><?= rich_inline((string) ($whyWrong[(string) $i] ?? '-'), $seen) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php if (tr($q, 'analogy') !== ''): ?>
    <div class="ex-sec analogy">
      <h4><?= icon('bulb', 14) ?> <?= e(t('explain.analogy')) ?></h4>
      <p><?= rich_inline(tr($q, 'analogy'), $seen) ?><?= fallback_chip($q, 'analogy') ?></p>
    </div>
    <?php endif; ?>
    <?php if (tr($q, 'tip') !== ''): ?>
    <div class="ex-sec tip">
      <h4><?= icon('sparkle', 14) ?> <?= e(t('explain.tip')) ?></h4>
      <p><?= rich_inline(tr($q, 'tip'), $seen) ?></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($q['rule_tags'])): ?>
    <div class="ex-sec">
      <h4><?= icon('tag', 14) ?> <?= e(t('explain.rule')) ?></h4>
      <div class="tag-chips">
        <?php foreach ($q['rule_tags'] as $tag): $ti = $tagInfo[$tag] ?? null; ?>
          <a class="chip chip-<?= e($ti['section'] ?? 'listening') ?>" href="<?= e(url('kamus' . (!empty($ti['glossary_slug']) ? '#' . $ti['glossary_slug'] : ''))) ?>"><?= e($ti ? tr($ti, 'name') : t('explain.rule')) ?></a>
        <?php endforeach; ?>
      </div>
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
        <p class="mt-8 small"><strong><?= e(t('explain.key_sentence')) ?>:</strong> <em><?= e($q['key_sentence']) ?></em>
        <?php if (!empty($q['key_translation_id']) && active_lang() === 'id'): ?><br><strong><?= e(t('explain.translation')) ?>:</strong> <?= e($q['key_translation_id']) ?><?php endif; ?></p>
      <?php endif; ?>
      <?php if (!empty($q['liaison_note'])): ?><p class="small muted liaison"><?= icon('volume', 14) ?> <?= rich_inline((string) $q['liaison_note'], $seen) ?></p><?php endif; ?>
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
