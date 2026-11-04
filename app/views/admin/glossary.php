<?php $e = $edit ?? []; $grps = ['pelaku', 'kelas', 'bagian', 'waktu', 'aturan']; ?>
<form class="card form mb-24" method="post" action="<?= e(url('admin/kamus')) ?>">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($e['id'] ?? 0) ?>">
  <h3><?= $e ? 'Edit istilah' : 'Tambah istilah' ?></h3>
  <div class="admin-form-grid">
    <div class="field"><label for="slug">Slug</label><input class="input mono" id="slug" name="slug" value="<?= e((string) ($e['slug'] ?? '')) ?>" required></div>
    <div class="field"><label for="grp">Grup</label><select class="input" id="grp" name="grp"><?php foreach ($grps as $g): ?><option <?= ($e['grp'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="term_en">Istilah (EN)</label><input class="input" id="term_en" name="term_en" value="<?= e((string) ($e['term_en'] ?? '')) ?>" required></div>
    <div class="field"><label for="meaning_id">Arti Indonesia</label><input class="input" id="meaning_id" name="meaning_id" value="<?= e((string) ($e['meaning_id'] ?? '')) ?>" required></div>
    <div class="field"><label for="def_id">Definisi ID</label><textarea class="input" id="def_id" name="def_id" rows="2"><?= e((string) ($e['def_id'] ?? '')) ?></textarea></div>
    <div class="field"><label for="def_en">Definition EN</label><textarea class="input" id="def_en" name="def_en" rows="2"><?= e((string) ($e['def_en'] ?? '')) ?></textarea></div>
    <div class="field full"><label for="example">Contoh (pakai __garis bawah__)</label><input class="input" id="example" name="example" value="<?= e((string) ($e['example'] ?? '')) ?>"></div>
    <div class="field"><label for="analogy_id">Perumpamaan ID</label><textarea class="input" id="analogy_id" name="analogy_id" rows="2"><?= e((string) ($e['analogy_id'] ?? '')) ?></textarea></div>
    <div class="field"><label for="analogy_en">Analogy EN</label><textarea class="input" id="analogy_en" name="analogy_en" rows="2"><?= e((string) ($e['analogy_en'] ?? '')) ?></textarea></div>
    <div class="field"><label for="sort">Urutan</label><input class="input mono" type="number" id="sort" name="sort" value="<?= (int) ($e['sort'] ?? 0) ?>"></div>
  </div>
  <div class="row"><button class="btn btn-primary btn-sm"><span>Simpan</span><span class="btn-arrow" aria-hidden="true"><?= icon('save', 14) ?></span></button><?php if ($e): ?><a href="<?= e(url('admin/kamus')) ?>">Batal</a><?php endif; ?></div>
</form>
<p class="small muted"><?= count($terms) ?> istilah</p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Grup</th><th>Istilah</th><th>Arti</th><th>Definisi ID</th><th></th></tr></thead>
  <tbody><?php foreach ($terms as $t): ?>
    <tr><td><?= e($t['grp']) ?></td><td><strong><?= e($t['term_en']) ?></strong><br><span class="tiny mono muted"><?= e($t['slug']) ?></span></td><td><?= e($t['meaning_id']) ?></td><td class="small"><?= e(mb_strimwidth((string) $t['def_id'], 0, 100, '…')) ?></td>
    <td class="row"><a href="<?= e(url('admin/kamus?edit=' . $t['id'])) ?>">Edit</a>
      <form method="post" action="<?= e(url('admin/kamus')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-ghost btn-sm" data-confirm="Hapus istilah ini?"><?= icon('trash', 14) ?></button></form></td></tr>
  <?php endforeach; ?></tbody>
</table></div>
