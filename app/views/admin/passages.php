<div class="row mb-16"><a class="btn btn-primary btn-sm" href="<?= e(url('admin/grup/baru')) ?>"><span>Grup / teks baru</span><span class="btn-arrow" aria-hidden="true"><?= icon('plus', 14) ?></span></a></div>
<div class="table-wrap"><table class="table">
  <thead><tr><th>ID</th><th>Rumah</th><th>Seksi</th><th>#</th><th>Part</th><th>Judul / awal naskah</th><th class="num">Soal</th><th>Audio</th></tr></thead>
  <tbody><?php foreach ($rows as $p): $script = json_col($p['script']); ?>
    <tr><td><a href="<?= e(url('admin/grup/' . $p['id'])) ?>"><?= $p['id'] ?></a></td><td><?= e($p['slug']) ?></td><td><?= e($p['section']) ?></td><td class="mono"><?= $p['sort'] ?></td><td><?= e((string) $p['part']) ?></td>
    <td><a href="<?= e(url('admin/grup/' . $p['id'])) ?>"><?= e($p['title'] ?: mb_strimwidth((string) ($script[0]['text'] ?? ''), 0, 70, '…')) ?></a></td><td class="num mono"><?= $p['qn'] ?></td>
    <td><?php if ($p['section'] === 'listening'): ?><?= audio_exists($p['audio_path']) ? '<span class="chip chip-xs chip-good">ada</span>' : '<span class="chip chip-xs chip-bad">belum</span>' ?><?php else: ?>–<?php endif; ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div>
