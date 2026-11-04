<?php foreach (array_merge($rows, [null]) as $s): ?>
<form class="card form mb-16" method="post" action="<?= e(url('admin/sponsor')) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($s['id'] ?? 0) ?>">
  <div class="row-between"><h3 class="mb-0"><?= $s ? e($s['name']) : 'Tambah sponsor' ?></h3><?php if ($s && (int) $s['is_pinned']): ?><span class="chip chip-ink">Dipin · sponsor utama</span><?php endif; ?></div>
  <div class="admin-form-grid">
    <div class="field"><label>Nama</label><input class="input" name="name" value="<?= e((string) ($s['name'] ?? '')) ?>" required></div>
    <div class="field"><label>URL</label><input class="input" type="url" name="url" value="<?= e((string) ($s['url'] ?? 'https://')) ?>" required></div>
    <div class="field"><label>Deskripsi 1 baris (ID)</label><input class="input" name="desc_id" value="<?= e((string) ($s['desc_id'] ?? '')) ?>" maxlength="255"></div>
    <div class="field"><label>Description (EN)</label><input class="input" name="desc_en" value="<?= e((string) ($s['desc_en'] ?? '')) ?>" maxlength="255"></div>
    <div class="field"><label>Logo (PNG/SVG ≤ 500 KB, opsional)</label><input type="file" name="logo" accept=".png,.svg,image/png,image/svg+xml">
      <?php if (!empty($s['logo_path'])): ?><img src="<?= e(url($s['logo_path'])) ?>" alt="" style="max-height:40px;margin-top:8px"><label class="check small"><input type="checkbox" name="remove_logo" value="1"> Hapus logo (pakai wordmark teks)</label><?php endif; ?></div>
    <div class="row"><label class="check"><input type="checkbox" name="is_pinned" value="1" <?= (int) ($s['is_pinned'] ?? 0) ? 'checked' : '' ?>> Pin sebagai sponsor utama</label>
      <div class="field"><label>Urutan</label><input class="input mono" type="number" name="sort_order" value="<?= (int) ($s['sort_order'] ?? 0) ?>" style="max-width:100px"></div></div>
  </div>
  <div class="row"><button class="btn btn-primary btn-sm"><span>Simpan</span><span class="btn-arrow" aria-hidden="true"><?= icon('save', 14) ?></span></button>
  <?php if ($s): ?><button class="btn btn-ghost btn-sm" name="action" value="delete" data-confirm="Hapus sponsor ini?"><?= icon('trash', 14) ?> Hapus</button><?php endif; ?></div>
</form>
<?php endforeach; ?>
