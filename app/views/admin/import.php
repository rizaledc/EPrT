<div class="grid-2">
  <form class="card form" method="post" action="<?= e(url('admin/impor')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <h3>Impor JSON</h3>
    <p class="small muted mb-0">Format sama dengan file seed (<code>{"rumah":"r02","material_id":"…","items":[…]}</code>) atau daftar soal yang masing-masing punya field <code>"rumah"</code> (PRD bagian 16). Selalu divalidasi dulu.</p>
    <div class="field"><label for="file">File .json</label><input type="file" id="file" name="file" accept="application/json,.json"></div>
    <div class="field"><label for="json">…atau tempel JSON</label><textarea class="input mono" id="json" name="json" rows="12"><?= e($json) ?></textarea></div>
    <div class="row">
      <button class="btn btn-secondary" name="mode" value="validate">Validasi (pratinjau)</button>
      <button class="btn btn-primary" name="mode" value="import"><span>Validasi & impor</span><span class="btn-arrow" aria-hidden="true"><?= icon('upload', 16) ?></span></button>
    </div>
  </form>
  <div class="stack">
    <?php if ($result !== null): ?>
      <div class="card card-sm">
        <h3><?= $result['committed'] ? 'Impor berhasil' : (empty($result['errors']) ? 'Valid, belum diimpor' : 'Ada error') ?></h3>
        <p><?= (int) $result['questions'] ?> soal · <?= count($result['errors']) ?> error · <?= count($result['warnings']) ?> peringatan</p>
        <?php if ($result['errors']): ?><ul class="rich-list"><?php foreach ($result['errors'] as $er): ?><li class="ex-bad"><span class="ex-mark"><?= icon('x', 14) ?></span><span style="text-decoration:none"><?= e($er) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if ($result['warnings']): ?><ul class="rich-list"><?php foreach ($result['warnings'] as $w): ?><li><span><?= e($w) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="card card-sm">
      <h3>Ekspor per Rumah</h3>
      <div class="row"><?php foreach ($rumahs as $r): ?><a class="chip chip-outline" href="<?= e(url('admin/ekspor/' . $r['slug'])) ?>"><?= e($r['slug']) ?></a><?php endforeach; ?></div>
    </div>
    <div class="card card-sm">
      <h3>Cadangan</h3>
      <p class="small muted">Unduh seluruh database sebagai file SQL. Aktifkan juga backup harian Hostinger.</p>
      <a class="btn btn-dark btn-sm" href="<?= e(url('admin/ekspor-sql')) ?>"><?= icon('download', 16) ?> Ekspor semua data (SQL)</a>
    </div>
  </div>
</div>
