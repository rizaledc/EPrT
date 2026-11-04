<div class="table-wrap"><table class="table">
  <thead><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Target</th><th>Detail</th></tr></thead>
  <tbody><?php foreach ($rows as $r): ?>
    <tr><td><?= e(fmt_date($r['created_at'])) ?></td><td><?= e((string) $r['username']) ?></td><td><code><?= e($r['action']) ?></code></td><td><?= e((string) $r['target']) ?></td><td class="small mono"><?= e((string) $r['meta']) ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div>
