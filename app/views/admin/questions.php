<?php $letters = ['A','B','C','D']; ?>
<div class="row-between mb-16"><a class="btn btn-primary btn-sm" href="<?= e(url('admin/soal/baru')) ?>"><span>Soal baru</span><span class="btn-arrow" aria-hidden="true"><?= icon('plus', 14) ?></span></a></div>
<form class="filters" method="get">
  <select class="input" name="blok"><option value="">Blok</option><?php foreach (['A','B','C','D'] as $b): ?><option <?= $f['blok'] === $b ? 'selected' : '' ?>><?= $b ?></option><?php endforeach; ?></select>
  <select class="input" name="rumah"><option value="">Rumah</option><?php foreach ($rumahs as $r): ?><option value="<?= e($r['slug']) ?>" <?= $f['rumah'] === $r['slug'] ? 'selected' : '' ?>><?= e($r['slug']) ?></option><?php endforeach; ?></select>
  <select class="input" name="section"><option value="">Seksi</option><?php foreach (Practice::SECTIONS as $s): ?><option <?= $f['section'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
  <select class="input" name="type"><option value="">Tipe</option><?php foreach (array_merge(...array_values(ContentImporter::TYPES)) as $t): ?><option <?= $f['type'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
  <select class="input" name="tag"><option value="">Tag</option><?php foreach ($tags as $code => $t): ?><option <?= $f['tag'] === $code ? 'selected' : '' ?>><?= e($code) ?></option><?php endforeach; ?></select>
  <select class="input" name="status"><option value="">Status</option><option <?= $f['status'] === 'published' ? 'selected' : '' ?>>published</option><option <?= $f['status'] === 'draft' ? 'selected' : '' ?>>draft</option></select>
  <button class="btn btn-dark btn-sm">Filter</button>
</form>
<p class="small muted"><?= count($rows) ?> soal</p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>ID</th><th>Rumah</th><th>Seksi</th><th>No</th><th>Tipe</th><th>Soal</th><th>Kunci</th><th>Tag</th><th class="num">Dijawab</th><th class="num">% benar</th><th>Status</th></tr></thead>
  <tbody><?php foreach ($rows as $q): ?>
    <tr><td><a href="<?= e(url('admin/soal/' . $q['id'])) ?>"><?= $q['id'] ?></a></td><td><?= e($q['slug']) ?></td><td><span class="sec-dot <?= e($q['section']) ?>"></span> <?= e($q['section']) ?></td><td class="mono"><?= $q['number'] ?></td><td class="small"><?= e($q['type']) ?></td>
    <td data-q><a href="<?= e(url('admin/soal/' . $q['id'])) ?>"><?= e(mb_strimwidth(preg_replace('/\{\/?[A-D]\}/', '', (string) $q['prompt']), 0, 80, '…')) ?></a></td>
    <td class="mono"><?= $letters[(int) $q['correct_index']] ?></td><td class="small"><?= e(implode(', ', json_col($q['rule_tags']))) ?></td>
    <td class="num mono"><?= (int) $q['n'] ?></td><td class="num mono"><?= $q['pct'] !== null ? round((float) $q['pct'] * 100) . '%' : '-' ?></td>
    <td><span class="chip chip-xs <?= $q['status'] === 'published' ? 'chip-good' : 'chip-outline' ?>"><?= e($q['status']) ?></span></td></tr>
  <?php endforeach; ?></tbody>
</table></div>
