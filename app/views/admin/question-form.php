<?php
$q = $q ?? [];
$letters = ['A','B','C','D'];
$v = fn($k, $d = '') => e((string) ($q[$k] ?? $d));
$opts = $q['options'] ?? ['', '', '', ''];
$ci = (int) ($q['correct_index'] ?? 0);
$wwId = $q['why_wrong_id'] ?? [];
$wwEn = $q['why_wrong_en'] ?? [];
$curRumah = '';
foreach ($rumahs as $r) { if ((int) $r['id'] === (int) ($q['rumah_id'] ?? 0)) $curRumah = $r['slug']; }
?>
<?= partial('form-errors', ['errors' => $errors]) ?>
<?php if (!empty($q['id'])): ?>
<div class="row mb-24">
  <?php foreach (['duplicate' => 'Duplikat', 'toggle' => ($q['status'] ?? '') === 'published' ? 'Nonaktifkan (draft)' : 'Terbitkan'] as $a => $l): ?>
    <form method="post" action="<?= e(url('admin/soal/' . $q['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="<?= $a ?>"><button class="btn btn-secondary btn-sm"><?= e($l) ?></button></form>
  <?php endforeach; ?>
  <form method="post" action="<?= e(url('admin/soal/' . $q['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm" data-confirm="Hapus soal ini beserta semua jawabannya?">Hapus</button></form>
  <?php if ($stats): ?><span class="small muted">Dijawab <?= $stats['n'] ?>× · <?= $stats['pct'] !== null ? round((float) $stats['pct'] * 100) . '% benar' : '-' ?> ·
    pilihan: <?php foreach ($stats['dist'] as $d): ?><?= $letters[(int) $d['chosen_index']] ?>=<?= $d['n'] ?> <?php endforeach; ?></span><?php endif; ?>
</div>
<?php endif; ?>

<form class="card form" method="post" action="<?= e(url('admin/soal/' . ($q['id'] ?? 'baru'))) ?>">
  <?= csrf_field() ?>
  <div class="admin-form-grid">
    <div class="field"><label for="rumah">Hari</label><select class="input" id="rumah" name="rumah" required><option value="">-</option><?php foreach ($rumahs as $r): ?><option value="<?= e($r['slug']) ?>" <?= $curRumah === $r['slug'] ? 'selected' : '' ?>><?= e($r['slug'] . ' · ' . $r['title_id']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="section">Seksi</label><select class="input" id="section" name="section"><?php foreach (Practice::SECTIONS as $s): ?><option <?= ($q['section'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="type">Tipe</label><select class="input" id="type" name="type"><?php foreach (array_merge(...array_values(ContentImporter::TYPES)) as $t): ?><option <?= ($q['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="subtype">Subtype (reading)</label><select class="input" id="subtype" name="subtype"><option value="">-</option><?php foreach (ContentImporter::READING_SUBTYPES as $t): ?><option <?= ($q['subtype'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="passage_id">Grup listening / teks reading</label><select class="input" id="passage_id" name="passage_id"><option value="">- (structure)</option><?php foreach ($passages as $p): ?><option value="<?= $p['id'] ?>" <?= (int) ($q['passage_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['slug'] . ' · ' . $p['section'] . ' #' . $p['sort'] . ($p['part'] ? ' Part ' . $p['part'] : '') . ($p['title'] ? ' · ' . $p['title'] : '')) ?></option><?php endforeach; ?></select></div>
    <div class="row">
      <div class="field"><label for="number">Nomor</label><input class="input mono" type="number" id="number" name="number" min="1" value="<?= $v('number', '1') ?>"></div>
      <div class="field"><label for="difficulty">Kesulitan</label><select class="input" id="difficulty" name="difficulty"><?php foreach ([1 => 'mudah', 2 => 'sedang', 3 => 'sulit'] as $d => $l): ?><option value="<?= $d ?>" <?= (int) ($q['difficulty'] ?? 1) === $d ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option <?= ($q['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>published</option><option <?= ($q['status'] ?? '') === 'draft' ? 'selected' : '' ?>>draft</option></select></div>
    </div>
    <div class="field full"><label for="prompt">Prompt</label><textarea class="input" id="prompt" name="prompt" rows="3"><?= $v('prompt') ?></textarea>
      <span class="hint">Error recognition: tandai bagian dengan {A}…{/A} sampai {D}…{/D}. Completion: pakai ______ (6 garis bawah). Listening: teks pertanyaan narator. Reading: **kata** untuk menebalkan.</span></div>
    <?php foreach ([0, 1, 2, 3] as $i): ?>
      <div class="field"><label for="opt<?= $i ?>">Pilihan <?= $letters[$i] ?></label><input class="input" id="opt<?= $i ?>" name="options[<?= $i ?>]" value="<?= e($opts[$i] ?? '') ?>"></div>
    <?php endforeach; ?>
    <div class="field"><label for="correct_index">Jawaban benar</label><select class="input" id="correct_index" name="correct_index"><?php foreach ($letters as $i => $L): ?><option value="<?= $i ?>" <?= $ci === $i ? 'selected' : '' ?>><?= $L ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="rule_tags">Tag aturan (1-2, pisahkan koma)</label><input class="input mono" id="rule_tags" name="rule_tags" list="tag-list" value="<?= e(implode(', ', $q['rule_tags'] ?? [])) ?>">
      <datalist id="tag-list"><?php foreach ($tags as $code => $t): ?><option value="<?= e($code) ?>"><?= e($t['name_id']) ?></option><?php endforeach; ?></datalist></div>
  </div>

  <h3 class="mt-16">Pembahasan (dua bahasa)</h3>
  <div class="lang-pair">
    <div class="field"><label data-lang="ID" for="wci">Kenapa benar</label><textarea class="input" id="wci" name="why_correct_id" rows="4"><?= $v('why_correct_id') ?></textarea></div>
    <div class="field"><label data-lang="EN" for="wce">Why correct</label><textarea class="input" id="wce" name="why_correct_en" rows="4"><?= $v('why_correct_en') ?></textarea></div>
    <?php foreach ([0, 1, 2, 3] as $i): ?>
      <div class="field"><label data-lang="ID" for="wwi<?= $i ?>">Kenapa <?= $letters[$i] ?> salah</label><textarea class="input" id="wwi<?= $i ?>" name="why_wrong_id[<?= $i ?>]" rows="2" placeholder="(kosongkan untuk jawaban benar)"><?= e((string) ($wwId[(string) $i] ?? '')) ?></textarea></div>
      <div class="field"><label data-lang="EN" for="wwe<?= $i ?>">Why <?= $letters[$i] ?> is wrong</label><textarea class="input" id="wwe<?= $i ?>" name="why_wrong_en[<?= $i ?>]" rows="2"><?= e((string) ($wwEn[(string) $i] ?? '')) ?></textarea></div>
    <?php endforeach; ?>
    <div class="field"><label data-lang="ID" for="ani">Perumpamaan</label><textarea class="input" id="ani" name="analogy_id" rows="2"><?= $v('analogy_id') ?></textarea></div>
    <div class="field"><label data-lang="EN" for="ane">Analogy</label><textarea class="input" id="ane" name="analogy_en" rows="2"><?= $v('analogy_en') ?></textarea></div>
    <div class="field"><label data-lang="ID" for="tpi">Trik cepat</label><textarea class="input" id="tpi" name="tip_id" rows="2"><?= $v('tip_id') ?></textarea></div>
    <div class="field"><label data-lang="EN" for="tpe">Quick tip</label><textarea class="input" id="tpe" name="tip_en" rows="2"><?= $v('tip_en') ?></textarea></div>
  </div>
  <h3 class="mt-16">Khusus Listening / Reading</h3>
  <div class="admin-form-grid">
    <div class="field"><label for="ks">Kalimat kunci (listening)</label><input class="input" id="ks" name="key_sentence" value="<?= $v('key_sentence') ?>"></div>
    <div class="field"><label for="kt">Terjemahan kalimat kunci (ID)</label><input class="input" id="kt" name="key_translation_id" value="<?= $v('key_translation_id') ?>"></div>
    <div class="field"><label for="ln">Catatan bunyi sambung</label><input class="input" id="ln" name="liaison_note" value="<?= $v('liaison_note') ?>"></div>
    <div class="field"><label for="ev">Kalimat bukti (reading)</label><textarea class="input" id="ev" name="evidence_quote" rows="2"><?= $v('evidence_quote') ?></textarea></div>
  </div>
  <div><button class="btn btn-primary" type="submit"><span>Simpan soal</span><span class="btn-arrow" aria-hidden="true"><?= icon('save', 16) ?></span></button></div>
</form>

<?php if (!empty($q['id']) && ($q['section'] ?? '') === 'listening'): ?>
<div class="card card-sm mt-24">
  <h3>Audio pertanyaan</h3>
  <p class="small mono"><?= e((string) $audio) ?> · <?= audio_exists($audio) ? '<span class="chip chip-xs chip-good">ada</span>' : '<span class="chip chip-xs chip-bad">belum ada</span>' ?></p>
  <?php if (audio_exists($audio)): ?><audio controls preload="none" src="<?= e(url($audio)) ?>"></audio><?php endif; ?>
  <form class="row mt-16" method="post" action="<?= e(url('admin/audio/upload')) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
    <input type="hidden" name="audio_id" value="<?= (int) $q['audio_question_id'] ?>"><input type="hidden" name="back" value="/admin/soal/<?= (int) $q['id'] ?>">
    <input type="file" name="mp3" accept="audio/mpeg,.mp3" required><button class="btn btn-secondary btn-sm"><?= icon('upload', 16) ?> Upload MP3</button></form>
</div>
<?php endif; ?>

<?php if (!empty($q['id']) && !empty($q['options'])): ?>
<h2 class="mt-32">Pratinjau (seperti tampilan pengguna)</h2>
<div class="grid-2">
  <div class="card q-card">
    <?php if (($q['section'] ?? '') === 'reading' && $previewPassage): ?><details class="mb-16"><summary class="small">Teks: <?= e((string) $previewPassage['title']) ?></summary><div class="passage-body small"><?= ReadingText::html((string) $previewPassage['body']) ?></div></details><?php endif; ?>
    <div class="q-prompt"><?= ($q['section'] ?? '') === 'listening' ? '<em>' . e((string) $q['prompt']) . '</em>' : render_question_text((string) $q['prompt']) ?></div>
    <div class="options"><?php foreach ($q['options'] as $i => $o): ?><div class="option <?= $i === $ci ? 'correct' : '' ?>"><span class="letter"><?= $letters[$i] ?></span><span><?= e($o) ?></span></div><?php endforeach; ?></div>
  </div>
  <div><?= Question::render(Question::hydrate($q), ($ci + 1) % 4, $previewPassage) ?></div>
</div>
<?php endif; ?>
