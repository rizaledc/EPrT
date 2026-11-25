<?php /** @var array $u */ $letters = ['A','B','C','D']; ?>
<?php if ($once): ?><div class="card card-hl mb-24"><p class="mb-8"><strong><?= e($once['label']) ?></strong></p><div class="recovery-code" id="once"><?= e($once['value']) ?></div>
  <button type="button" class="btn btn-secondary btn-sm mt-16" data-copy="#once"><?= icon('copy', 16) ?> <span data-copy-label>Salin</span></button></div><?php endif; ?>
<div class="grid-2">
  <div class="card card-sm">
    <h3><?= e($u['username']) ?> <span class="chip chip-xs <?= $u['status'] === 'active' ? 'chip-good' : 'chip-bad' ?>"><?= e($u['status']) ?></span>
      <?php if ($suspicious): ?><span class="chip chip-xs chip-bad" title="<?= e(implode(', ', $suspicious)) ?>">Pemakaian janggal</span><?php endif; ?></h3>
    <?php if ($suspicious): ?><p class="small" style="color:var(--bad)">Pemakaian janggal: <?= e(implode(', ', $suspicious)) ?>.</p><?php endif; ?>
    <p class="small mb-0">Role: <?= e($u['role']) ?> · Plan: <?= e($u['plan']) ?> · Bahasa: <?= e(strtoupper($u['lang'])) ?> · Target: <?= (int) $u['target_score'] ?><br>
    Daftar: <?= e(fmt_date($u['created_at'])) ?> · Terakhir aktif: <?= e(fmt_date($u['last_active_at'])) ?><br>
    Perkiraan skor: <strong><?= $estimate['total'] ?? '-' ?></strong> <?= $estimate['total'] ? '(' . $estimate['low'] . '-' . $estimate['high'] . ')' : '' ?> · <?= $estimate['answers'] ?> jawaban
    <?= (int) $u['must_change_password'] ? '<br><span class="chip chip-xs chip-lime">wajib ganti password</span>' : '' ?></p>
  </div>
  <div class="card card-sm stack">
    <h3>Aksi</h3>
    <div class="row">
      <form method="post" action="<?= e(url('admin/pengguna/' . $u['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="reset_password"><button class="btn btn-dark btn-sm" data-confirm="Reset password <?= e($u['username']) ?>?">Reset password</button></form>
      <form method="post" action="<?= e(url('admin/pengguna/' . $u['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="recovery"><button class="btn btn-secondary btn-sm">Kode pemulihan baru</button></form>
      <form method="post" action="<?= e(url('admin/pengguna/' . $u['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="revoke_sessions"><button class="btn btn-secondary btn-sm" data-confirm="Cabut semua sesi <?= e($u['username']) ?>?">Cabut semua sesi (<?= (int) $activeSessions ?> aktif)</button></form>
      <form method="post" action="<?= e(url('admin/pengguna/' . $u['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_status"><button class="btn btn-secondary btn-sm"><?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
    </div>
    <form class="row" method="post" action="<?= e(url('admin/pengguna/' . $u['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="plan">
      <input class="input" style="max-width:160px;min-height:40px" name="plan" value="<?= e($u['plan']) ?>" aria-label="Plan"><button class="btn btn-secondary btn-sm">Ganti plan</button></form>
    <?php if ($u['role'] !== 'admin'): ?>
    <form class="row" method="post" action="<?= e(url('admin/pengguna/' . $u['id'] . '/aksi')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <input class="input" style="max-width:200px;min-height:40px" name="confirm" placeholder="ketik <?= e($u['username']) ?>" aria-label="Konfirmasi username"><button class="btn btn-danger btn-sm">Hapus</button></form>
    <?php endif; ?>
  </div>
</div>

<h2 class="mt-32">Peta progres</h2>
<div class="houses">
  <?php foreach (Rumah::all() as $r): if ($r['kind'] === 'sample') continue; $p = $progress[(int) $r['id']] ?? null; $st = Practice::rumahState($p); ?>
    <div class="house-card state-<?= $st ?>"><span class="house-num"><?= $r['kind'] === 'simulation' ? 'S' . $r['number'] : $r['number'] ?></span><?= partial('house') ?>
      <h3><?= e($r['title_id']) ?></h3><div class="meta"><?= $p ? (int) $p['answered_distinct'] . ' unik · terakhir ' . ($p['last_pct'] ?? '-') . '% · terbaik ' . ($p['best_pct'] ?? '-') . '%' : 'Belum mulai' ?></div></div>
  <?php endforeach; ?>
</div>

<h2 class="mt-32">Percobaan</h2>
<div class="table-wrap"><table class="table">
  <thead><tr><th>ID</th><th>Mulai</th><th>Hari</th><th>Seksi</th><th>Mode</th><th class="num">Skor</th><th class="num">Durasi</th><th></th></tr></thead>
  <tbody><?php foreach ($attempts as $a): ?>
    <tr><td><?= $a['id'] ?></td><td><?= e(fmt_date($a['started_at'])) ?></td><td><?= e($a['title_id']) ?></td><td><?= e($a['section']) ?></td><td><?= e($a['mode']) ?></td>
    <td class="num mono"><?= $a['finished_at'] ? $a['correct'] . '/' . $a['total'] : 'berjalan' ?></td><td class="num mono"><?= fmt_duration((int) $a['time_spent_s']) ?></td>
    <td><?php if ($a['finished_at']): ?><a href="<?= e(url('hasil/' . $a['id'])) ?>">hasil</a><?php endif; ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div>

<h2 class="mt-32">Jawaban per soal (300 terbaru)</h2>
<div class="table-wrap" style="max-height:480px"><table class="table">
  <thead><tr><th>Waktu</th><th>Soal</th><th>Hari</th><th>Pilihan</th><th>Hasil</th><th class="num">Durasi</th><th>Catatan</th></tr></thead>
  <tbody><?php foreach ($answers as $a): ?>
    <tr><td><?= e(fmt_date($a['created_at'])) ?></td><td><a href="<?= e(url('admin/soal/' . $a['question_id'])) ?>">#<?= $a['question_id'] ?></a> <?= e($a['type']) ?></td><td><?= e($a['slug']) ?></td>
    <td class="mono"><?= $a['chosen_index'] === null ? '-' : $letters[(int) $a['chosen_index']] ?> (kunci <?= $letters[(int) $a['correct_index']] ?>)</td>
    <td><?= $a['chosen_index'] === null ? 'kosong' : ((int) $a['is_correct'] ? '<span class="chip chip-xs chip-good">benar</span>' : '<span class="chip chip-xs chip-bad">salah</span>') ?></td>
    <td class="num mono"><?= round($a['time_ms'] / 1000) ?>s</td><td class="small"><?= (int) $a['flagged'] ? 'ragu ' : '' ?><?= (int) $a['audio_missing'] ? 'audio_missing' : '' ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div>

<div class="grid-2 mt-32">
  <div>
    <h2>Buku Kesalahan</h2>
    <?php if (!$book): ?><p class="muted">Kosong.</p><?php endif; ?>
    <?php foreach ($book as $g): ?><div class="row-between card card-sm mb-16"><span class="chip chip-<?= e($g['section']) ?>"><?= e($g['tag']) ?></span><span class="mono"><?= count($g['questions']) ?> soal · <?= $g['wrong'] ?>× salah</span></div><?php endforeach; ?>
  </div>
  <div>
    <h2>Sesi perangkat (30 terbaru)</h2>
    <div class="table-wrap"><table class="table"><thead><tr><th>Perangkat</th><th>Masuk</th><th>Terakhir aktif</th><th>IP (disamarkan)</th><th>Status</th></tr></thead><tbody>
      <?php if (!$sessions): ?><tr><td colspan="5" class="muted">Belum ada sesi.</td></tr><?php endif; ?>
      <?php foreach ($sessions as $s): ?><tr><td><?= e($s['device_label']) ?><?= (int) $s['remember'] ? ' <span class="chip chip-xs">ingat saya</span>' : '' ?></td><td><?= e(fmt_date($s['created_at'])) ?></td><td><?= e(fmt_date($s['last_seen_at'])) ?></td><td class="mono"><?= e(substr($s['ip_hash'], 0, 10)) ?>…</td>
        <td><?= $s['revoked_at'] ? 'dicabut (' . e(['limit' => 'batas perangkat', 'logout' => 'keluar', 'user' => 'dikeluarkan pengguna', 'admin' => 'admin', 'admin_reset' => 'reset password', 'password' => 'ganti password', 'expired' => 'kedaluwarsa', 'disabled' => 'akun nonaktif'][$s['revoke_reason']] ?? (string) $s['revoke_reason']) . ')' : '<span class="chip chip-xs chip-good">aktif</span>' ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div>
    <h2>Riwayat login</h2>
    <div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>IP (disamarkan)</th><th>Hasil</th></tr></thead><tbody>
      <?php foreach ($logins as $l): ?><tr><td><?= e(fmt_date($l['created_at'])) ?></td><td class="mono"><?= e(substr($l['ip_hash'], 0, 10)) ?>…</td><td><?= (int) $l['success'] ? 'berhasil' : 'gagal' ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</div>
