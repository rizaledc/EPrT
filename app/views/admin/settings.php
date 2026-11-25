<?= partial('form-errors', ['errors' => $errors]) ?>
<form class="card form" method="post" action="<?= e(url('admin/pengaturan')) ?>">
  <?= csrf_field() ?>
  <div class="admin-form-grid">
    <div class="field"><label for="default_target">Target skor default</label><input class="input mono" type="number" id="default_target" name="default_target" min="310" max="677" value="<?= $target ?>"></div>
    <label class="check" style="align-self:end"><input type="checkbox" name="registration_open" value="1" <?= $open ? 'checked' : '' ?>> Pendaftaran dibuka</label>
    <div class="field"><label for="max_sessions">Batas perangkat aktif per akun</label><input class="input mono" type="number" id="max_sessions" name="max_sessions" min="1" max="10" value="<?= (int) $maxSessions ?>">
      <span class="hint">Default 2. Saat login baru melebihi batas, sesi paling lama otomatis dikeluarkan beserta token Ingat saya. Tidak berlaku untuk admin.</span></div>
    <div class="field"><label for="ai">Pengumuman di halaman Latihan (ID)</label><textarea class="input" id="ai" name="announcement_id" rows="2"><?= e((string) ($ann['id'] ?? '')) ?></textarea></div>
    <div class="field"><label for="ae">Announcement (EN)</label><textarea class="input" id="ae" name="announcement_en" rows="2"><?= e((string) ($ann['en'] ?? '')) ?></textarea></div>
    <div class="field full"><label for="st">Tabel konversi skor (JSON)</label><textarea class="input mono" id="st" name="score_table" rows="14"><?= e($scoreTable) ?></textarea>
      <span class="hint">points = jumlah benar (raw). Nilai di antara titik diinterpolasi linear. Structure maksimal 40, jadi titik 45 & 50 boleh null.</span></div>
  </div>
  <div><button class="btn btn-primary"><span>Simpan pengaturan</span><span class="btn-arrow" aria-hidden="true"><?= icon('save', 16) ?></span></button></div>
</form>
