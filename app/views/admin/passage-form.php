<?php
$p = $p ?? [];
$scriptText = '';
foreach (json_col($p['script'] ?? null) as $l) { $scriptText .= $l['speaker'] . ': ' . $l['text'] . "\n"; }
if (is_post()) $scriptText = (string) ($_POST['script'] ?? $scriptText);
?>
<?= partial('form-errors', ['errors' => $errors]) ?>
<form class="card form" method="post" action="<?= e(url('admin/grup/' . ($p['id'] ?? 'baru'))) ?>">
  <?= csrf_field() ?>
  <div class="admin-form-grid">
    <div class="field"><label for="rumah">Rumah</label><select class="input" id="rumah" name="rumah"><?php foreach ($rumahs as $r): ?><option value="<?= e($r['slug']) ?>" <?= ($p['slug'] ?? '') === $r['slug'] ? 'selected' : '' ?>><?= e($r['slug'] . ' · ' . $r['title_id']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="section">Seksi</label><select class="input" id="section" name="section"><option <?= ($p['section'] ?? '') === 'listening' ? 'selected' : '' ?>>listening</option><option <?= ($p['section'] ?? '') === 'reading' ? 'selected' : '' ?>>reading</option></select></div>
    <div class="field"><label for="part">Part (listening)</label><select class="input" id="part" name="part"><?php foreach (['A','B','C'] as $pt): ?><option <?= ($p['part'] ?? '') === $pt ? 'selected' : '' ?>><?= $pt ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="sort">Urutan grup</label><input class="input mono" type="number" id="sort" name="sort" min="1" value="<?= e((string) ($p['sort'] ?? '')) ?>" placeholder="otomatis"></div>
    <div class="field full"><label for="title">Judul (reading)</label><input class="input" id="title" name="title" value="<?= e((string) ($p['title'] ?? '')) ?>"></div>
    <div class="field full"><label for="script">Naskah listening (satu baris per giliran: <code>W1: teks</code>)</label><textarea class="input mono" id="script" name="script" rows="8"><?= e($scriptText) ?></textarea>
      <span class="hint">Speaker: N (narator), M1-M4 (pria), W1-W4 (wanita), LEC (dosen).</span></div>
    <div class="field full"><label for="body">Teks reading (paragraf dipisah baris kosong, **kata** ditebalkan)</label><textarea class="input" id="body" name="body" rows="14"><?= e((string) ($p['body'] ?? '')) ?></textarea>
      <?php if (!empty($p['body'])): ?><span class="hint"><?= ReadingText::wordCount((string) $p['body']) ?> kata (disarankan 250-450)</span><?php endif; ?></div>
  </div>
  <div><button class="btn btn-primary" type="submit"><span>Simpan</span><span class="btn-arrow" aria-hidden="true"><?= icon('save', 16) ?></span></button></div>
</form>

<?php if (!empty($p['id'])): ?>
  <?php if ($p['section'] === 'listening'): ?>
  <div class="card card-sm mt-24">
    <h3>Audio percakapan</h3>
    <p class="small mono"><?= e((string) $p['audio_path']) ?> · <?= audio_exists($p['audio_path']) ? '<span class="chip chip-xs chip-good">ada</span>' : '<span class="chip chip-xs chip-bad">belum ada</span>' ?></p>
    <?php if (audio_exists($p['audio_path'])): ?><audio controls preload="none" src="<?= e(url($p['audio_path'])) ?>"></audio><?php endif; ?>
    <form class="row mt-16" method="post" action="<?= e(url('admin/audio/upload')) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
      <input type="hidden" name="audio_id" value="<?= (int) $p['audio_talk_id'] ?>"><input type="hidden" name="back" value="/admin/grup/<?= (int) $p['id'] ?>">
      <input type="file" name="mp3" accept="audio/mpeg,.mp3" required><button class="btn btn-secondary btn-sm"><?= icon('upload', 16) ?> Upload / ganti MP3</button></form>
    <p class="small muted mb-0">Hanya audio/mpeg, maksimal 5 MB. Nama file ditentukan server.</p>
  </div>
  <?php endif; ?>
  <h2 class="mt-32">Soal di grup ini</h2>
  <div class="table-wrap"><table class="table"><thead><tr><th>ID</th><th>No</th><th>Tipe</th><th>Prompt</th><?php if ($p['section'] === 'listening'): ?><th>Audio pertanyaan</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($questions as $q): ?><tr><td><a href="<?= e(url('admin/soal/' . $q['id'])) ?>"><?= $q['id'] ?></a></td><td class="mono"><?= $q['number'] ?></td><td><?= e($q['type']) ?></td><td><?= e(mb_strimwidth((string) $q['prompt'], 0, 80, '…')) ?></td>
    <?php if ($p['section'] === 'listening'): ?><td><?= audio_exists($q['audio_path']) ? '<span class="chip chip-xs chip-good">ada</span>' : '<span class="chip chip-xs chip-bad">belum</span>' ?></td><?php endif; ?></tr><?php endforeach; ?>
  </tbody></table></div>
  <p class="mt-16"><a href="<?= e(url('admin/soal/baru')) ?>">+ Tambah soal ke grup ini</a> (pilih grup #<?= (int) $p['id'] ?> di form).</p>
<?php endif; ?>
