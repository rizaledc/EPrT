<?php $q = $_GET; $sortLink = function ($col, $label) use ($q) { $dir = (($q['sort'] ?? '') === $col && ($q['dir'] ?? 'desc') === 'desc') ? 'asc' : 'desc'; return '<a href="?' . e(http_build_query(array_merge($q, ['sort' => $col, 'dir' => $dir]))) . '">' . e($label) . ' ↕</a>'; }; ?>
<form class="filters" method="get">
  <input class="input" type="search" name="q" placeholder="Cari username" value="<?= e($q['q'] ?? '') ?>">
  <select class="input" name="status"><option value="">Semua status</option><option value="active" <?= ($q['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option><option value="disabled" <?= ($q['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Nonaktif</option></select>
  <select class="input" name="blok"><option value="">Level selesai: semua</option><?php foreach (['A','B','C','D'] as $b): ?><option value="<?= $b ?>" <?= ($q['blok'] ?? '') === $b ? 'selected' : '' ?>>Level <?= $b ?> selesai</option><?php endforeach; ?></select>
  <button class="btn btn-dark btn-sm">Terapkan</button>
  <a class="btn btn-secondary btn-sm" href="<?= e(url('admin/pengguna.csv') . '?' . http_build_query($q)) ?>"><?= icon('download', 16) ?> Export CSV</a>
</form>
<p class="small muted"><?= count($users) ?> pengguna</p>
<div class="table-wrap"><table class="table">
  <thead><tr><th><?= $sortLink('username', 'Username') ?></th><th><?= $sortLink('created_at', 'Daftar') ?></th><th><?= $sortLink('last_active_at', 'Terakhir aktif') ?></th><th class="num"><?= $sortLink('done', 'Hari') ?></th><th>Level</th><th>Simulasi</th><th class="num">Perkiraan</th><th>Bahasa</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><a href="<?= e(url('admin/pengguna/' . $u['id'])) ?>"><strong><?= e($u['username']) ?></strong></a><?= $u['role'] === 'admin' ? ' <span class="chip chip-xs chip-ink">admin</span>' : '' ?></td>
      <td><?= e(fmt_date($u['created_at'], false)) ?></td>
      <td><?= e(fmt_date($u['last_active_at'])) ?></td>
      <td class="num mono"><?= (int) $u['done'] ?>/14</td>
      <td class="mono"><?= e($u['bloks'] ?: '-') ?></td>
      <td class="small"><?php foreach ($u['sims'] as $s): ?>S<?= $s['number'] ?> <?= strtoupper($s['section'][0]) ?> <?= $s['correct'] ?>/<?= $s['total'] ?><br><?php endforeach; ?><?= $u['sims'] ? '' : '-' ?></td>
      <td class="num mono"><?= $u['estimate'] ?? '-' ?></td>
      <td><?= e(strtoupper($u['lang'])) ?></td>
      <td><span class="chip chip-xs <?= $u['status'] === 'active' ? 'chip-good' : 'chip-bad' ?>"><?= e($u['status']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
