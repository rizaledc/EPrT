<div class="table-wrap"><table class="table">
  <thead><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Target</th><th>Detail</th></tr></thead>
  <tbody><?php foreach ($rows as $r): ?>
    <tr><td><?= e(fmt_date($r['created_at'])) ?></td><td><?= e((string) $r['username']) ?></td><td><code><?= e($r['action']) ?></code></td><td><?= e((string) $r['target']) ?></td><td class="small mono"><?php $m = json_decode((string) $r['meta'], true); echo e(is_array($m) ? implode(', ', array_map(fn($k, $v) => $k . ': ' . (is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)), array_keys($m), $m)) : (string) $r['meta']); ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div>
