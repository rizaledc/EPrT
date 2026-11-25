<?php
/** @var array $packages @var array $orders @var array $analyses @var array $month @var array $sales */
$idr = fn(float $usd) => 'Rp' . number_format($usd * $usdIdr, 1, ',', '.');
$avg = (int) $month['n'] > 0 ? (float) $month['cost'] / (int) $month['n'] : 0.0;
?>
<div class="grid-2">
  <div class="card card-sm">
    <h3>Status konfigurasi</h3>
    <ul class="small" style="padding-left:18px;margin:0">
      <li>OpenRouter API key: <?= $orKey ? '<span class="chip chip-xs chip-good">terisi</span>' : '<span class="chip chip-xs chip-bad">kosong</span>' ?> · model aktif <span class="mono"><?= e($modelNow) ?></span></li>
      <li>Mayar API key: <?= $mayarKey ? '<span class="chip chip-xs chip-good">terisi</span>' : '<span class="chip chip-xs chip-bad">kosong</span>' ?> · mode <?= $sandbox ? '<span class="chip chip-xs chip-lime">sandbox</span>' : '<span class="chip chip-xs chip-good">produksi</span>' ?></li>
      <li>Token webhook: <?= $hasToken ? '<span class="chip chip-xs chip-good">terisi</span>' : '<span class="chip chip-xs chip-bad">kosong</span>' ?></li>
    </ul>
    <p class="small mt-16 mb-8">URL webhook untuk dashboard Mayar (Integration, Webhook):</p>
    <div class="row"><code class="small" id="hook" style="word-break:break-all"><?= e($hookUrl) ?></code>
      <button type="button" class="btn btn-secondary btn-sm" data-copy="#hook"><?= icon('copy', 14) ?> <span data-copy-label>Salin</span></button></div>
    <p class="small muted mt-8 mb-0">Kunci API diisi di app/config.php di server (openrouter.api_key, mayar.api_key, mayar.sandbox, mayar.webhook_token), tidak lewat halaman ini.</p>
  </div>
  <div class="card card-sm">
    <h3>Bulan ini</h3>
    <p class="small mb-0">Penjualan: <strong><?= (int) $sales['n'] ?></strong> pesanan · <?= (int) $sales['coins'] ?> koin · <strong><?= e(Coins::rupiah((int) $sales['amount'])) ?></strong><br>
    Analisis AI: <strong><?= (int) $month['n'] ?></strong> · biaya total $<?= number_format((float) $month['cost'], 4) ?> (sekitar <?= e($idr((float) $month['cost'])) ?>)<br>
    Rata-rata per analisis: $<?= number_format($avg, 5) ?> (sekitar <?= e($idr($avg)) ?>, kurs Rp<?= number_format($usdIdr, 0, ',', '.') ?>) · token <?= number_format((int) $month['pt']) ?> masuk / <?= number_format((int) $month['ct']) ?> keluar</p>
  </div>
</div>

<form class="card mt-24 form" method="post" action="<?= e(url('admin/koin')) ?>">
  <?= csrf_field() ?><input type="hidden" name="action" value="settings">
  <h2>Paket Premium, koin, dan AI</h2>
  <div class="table-wrap"><table class="table"><thead><tr><th>Paket</th><th>Jumlah koin</th><th>Harga (Rp)</th><th>Aktif</th></tr></thead><tbody>
    <?php foreach ($packages as $i => $p): ?>
      <tr><td class="mono"><?= e($p['id']) ?></td>
        <td><input class="input" style="max-width:110px;min-height:40px" type="number" min="1" max="1000" name="pkg_coins[<?= $i ?>]" value="<?= (int) $p['coins'] ?>" aria-label="Jumlah koin paket <?= $i + 1 ?>"></td>
        <td><input class="input" style="max-width:160px;min-height:40px" type="number" min="1000" step="500" name="pkg_price[<?= $i ?>]" value="<?= (int) $p['price'] ?>" aria-label="Harga paket <?= $i + 1 ?>"></td>
        <td><input type="checkbox" name="pkg_active[<?= $i ?>]" value="1" <?= $p['active'] ? 'checked' : '' ?> aria-label="Paket <?= $i + 1 ?> aktif"></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <h3 class="mt-24">Premium</h3>
  <p class="small muted">Harga sekarang <strong><?= e(Coins::rupiah($premiumPrice)) ?></strong> (tingkat dari halaman Harga) · pembeli tercatat <?= (int) $premiumSold ?> · Premium aktif <?= (int) $premiumActive ?> pengguna</p>
  <div class="grid-2">
    <label class="row small"><input type="checkbox" name="premium_sales_open" value="1" <?= $premiumOpen ? 'checked' : '' ?>> Pembelian Premium dibuka</label>
    <label class="row small"><input type="checkbox" name="premium_required" value="1" <?= $premiumRequired ? 'checked' : '' ?>> Kunci Level B sampai D, Simulasi, dan Rapor untuk pengguna tanpa Premium (matikan = masa uji coba, semua terbuka)</label>
    <label class="row small"><input type="checkbox" name="premium_grandfather" value="1" <?= $premiumGrandfather ? 'checked' : '' ?>> Pengguna awal tetap gratis: akun yang dibuat sebelum penguncian dinyalakan<?= $premiumCutoff !== '' ? ' (sebelum ' . e(fmt_date($premiumCutoff)) . ')' : '' ?> tetap membuka semua Level</label>
    <div class="field"><label for="premium_bonus_coins">Bonus koin AI untuk pembeli Premium</label><input class="input" id="premium_bonus_coins" name="premium_bonus_coins" type="number" min="0" max="20" value="<?= (int) $premiumBonus ?>"></div>
    <div class="field"><label for="premium_sold_extra">Pembeli di luar situs (ditambahkan ke hitungan tingkat harga)</label><input class="input" id="premium_sold_extra" name="premium_sold_extra" type="number" min="0" value="<?= (int) $premiumExtra ?>"></div>
  </div>
  <h3 class="mt-24">Koin dan AI</h3>
  <div class="grid-2">
    <label class="row small"><input type="checkbox" name="sales_open" value="1" <?= $salesOpen ? 'checked' : '' ?>> Pembelian koin dibuka</label>
    <label class="row small"><input type="checkbox" name="ai_enabled" value="1" <?= $aiEnabled ? 'checked' : '' ?>> Analisis AI aktif</label>
    <div class="field"><label for="ai_daily_limit">Batas analisis per pengguna per hari</label><input class="input" id="ai_daily_limit" name="ai_daily_limit" type="number" min="1" max="50" value="<?= (int) $aiLimit ?>"></div>
    <div class="field"><label for="ai_model">Model OpenRouter (kosong = dari config, bawaan anthropic/claude-haiku-5.5)</label><input class="input mono" id="ai_model" name="ai_model" value="<?= e($aiModel) ?>" placeholder="<?= e($modelNow) ?>"></div>
  </div>
  <div><button class="btn btn-dark" type="submit">Simpan</button></div>
</form>

<h2 class="mt-32">Pesanan terbaru</h2>
<div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Kode</th><th>Pengguna</th><th>Paket</th><th class="num">Harga</th><th>Status</th><th>Mayar ID</th><th></th></tr></thead><tbody>
  <?php if (!$orders): ?><tr><td colspan="8" class="muted">Belum ada pesanan.</td></tr><?php endif; ?>
  <?php foreach ($orders as $o): ?><tr>
    <td><?= e(fmt_date($o['created_at'])) ?></td><td class="mono small"><?= e($o['code']) ?></td>
    <td><a href="<?= e(url('admin/pengguna/' . $o['user_id'])) ?>"><?= e($o['username']) ?></a></td>
    <td><?= ($o['product'] ?? 'coins') === 'premium' ? 'Premium 1 th' : (int) $o['coins'] . ' koin' ?></td><td class="num mono"><?= e(Coins::rupiah((int) $o['amount'])) ?></td>
    <td><span class="chip chip-xs <?= $o['status'] === 'paid' ? 'chip-good' : ($o['status'] === 'pending' ? 'chip-lime' : 'chip-outline') ?>"><?= e($o['status']) ?></span><?= $o['paid_at'] ? '<br><span class="small muted">' . e(fmt_date($o['paid_at'])) . '</span>' : '' ?></td>
    <td class="mono small"><?= e((string) $o['mayar_id']) ?></td>
    <td><?php if ($o['status'] === 'pending' && $mayarKey): ?><form method="post" action="<?= e(url('admin/koin')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="check_order"><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>"><button class="btn btn-secondary btn-sm">Cek ke Mayar</button></form><?php endif; ?></td>
  </tr><?php endforeach; ?>
</tbody></table></div>

<h2 class="mt-32">Analisis AI terbaru</h2>
<div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Pengguna</th><th class="num">Target</th><th>Model</th><th class="num">Token masuk/keluar</th><th class="num">Biaya</th><th>Status</th></tr></thead><tbody>
  <?php if (!$analyses): ?><tr><td colspan="7" class="muted">Belum ada analisis.</td></tr><?php endif; ?>
  <?php foreach ($analyses as $a): ?><tr>
    <td><?= e(fmt_date($a['created_at'])) ?></td><td><a href="<?= e(url('admin/pengguna/' . $a['user_id'])) ?>"><?= e($a['username']) ?></a></td>
    <td class="num"><?= (int) $a['target'] ?></td><td class="mono small"><?= e($a['model']) ?></td>
    <td class="num mono"><?= (int) $a['prompt_tokens'] ?>/<?= (int) $a['completion_tokens'] ?></td>
    <td class="num mono">$<?= number_format((float) $a['cost_usd'], 5) ?><br><span class="small muted"><?= e($idr((float) $a['cost_usd'])) ?></span></td>
    <td><span class="chip chip-xs <?= $a['status'] === 'ok' ? 'chip-good' : ($a['status'] === 'failed' ? 'chip-bad' : 'chip-lime') ?>"><?= e($a['status']) ?></span><?= $a['error'] ? '<br><span class="small muted">' . e(mb_substr($a['error'], 0, 80)) . '</span>' : '' ?></td>
  </tr><?php endforeach; ?>
</tbody></table></div>
