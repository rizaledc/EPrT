<?php
/** @var int $balance @var array $packages @var array $orders @var array $ledger @var bool $canBuy */
$base = $packages[0]['price'] / max(1, $packages[0]['coins']);
?>
<section class="container container-narrow page-head">
  <span class="eyebrow"><?= e(t('coins.eyebrow')) ?></span>
  <h1><?= e(t('coins.h1a')) ?> <span class="hl"><?= e(t('coins.h1b')) ?></span></h1>
  <p class="muted prose"><?= e(t('coins.what')) ?></p>
</section>
<section class="container container-narrow stack">
  <div class="card dots coin-balance">
    <div class="row-between">
      <div>
        <span class="eyebrow"><?= e(t('coins.balance')) ?></span>
        <div class="score-big" data-coin-balance><?= (int) $balance ?> <span class="coin-unit"><?= e(t('coins.unit')) ?></span></div>
      </div>
      <a class="btn btn-dark" href="<?= e(url('rekomendasi')) ?>"><?= icon('sparkle', 18) ?> <?= e(t('coins.to_advice')) ?></a>
    </div>
  </div>

  <div class="card" id="beli">
    <h2><?= e(t('coins.packages_h')) ?></h2>
    <?php if (!$canBuy): ?><div class="announce mb-16"><?= icon('clock', 18) ?> <?= e(t('coins.closed')) ?></div><?php endif; ?>
    <div class="grid-3 coin-packs">
      <?php foreach ($packages as $p): if (!$p['active']) { continue; }
        $per = $p['price'] / $p['coins']; $save = $base > 0 ? (int) floor((1 - $per / $base) * 100) : 0; ?>
        <form class="card card-sm coin-pack" method="post" action="<?= e(url('koin/beli')) ?>">
          <?= csrf_field() ?><input type="hidden" name="package" value="<?= e($p['id']) ?>">
          <div class="row-between"><span class="chip chip-ink"><?= icon('coin', 14) ?> <?= e(t('coins.pkg', ['n' => $p['coins']])) ?></span><?php if ($save > 0): ?><span class="chip chip-lime chip-xs"><?= e(t('coins.save_pct', ['p' => $save])) ?></span><?php endif; ?></div>
          <div class="display coin-price"><?= e(Coins::rupiah($p['price'])) ?></div>
          <p class="small muted"><?= e(t('coins.per', ['p' => Coins::rupiah((int) round($per))])) ?></p>
          <button class="btn btn-primary btn-block" type="submit" <?= $canBuy ? '' : 'disabled' ?> data-once><span><?= e(t('coins.buy')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-up-right', 16) ?></span></button>
        </form>
      <?php endforeach; ?>
    </div>
    <p class="small muted mt-16 mb-0"><?= icon('shield', 14) ?> <?= e(t('coins.pay_note')) ?></p>
  </div>

  <div class="card" id="pesanan">
    <h2><?= e(t('coins.orders_h')) ?></h2>
    <?php if (!$orders): ?><p class="muted mb-0"><?= e(t('coins.empty_orders')) ?></p><?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th><?= e(t('coins.col.date')) ?></th><th><?= e(t('coins.col.item')) ?></th><th class="num"><?= e(t('coins.col.amount')) ?></th><th><?= e(t('coins.col.status')) ?></th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= e(fmt_date($o['created_at'])) ?><br><span class="small muted mono"><?= e($o['code']) ?></span></td>
          <td><?= e(($o['product'] ?? 'coins') === 'premium' ? t('premium.item') : t('coins.pkg', ['n' => (int) $o['coins']])) ?></td>
          <td class="num mono"><?= e(Coins::rupiah((int) $o['amount'])) ?></td>
          <td><span class="chip chip-xs <?= $o['status'] === 'paid' ? 'chip-good' : ($o['status'] === 'pending' ? 'chip-lime' : 'chip-outline') ?>"><?= e(t('coins.st.' . $o['status'])) ?></span></td>
          <td><?php if ($o['status'] === 'pending' && $o['pay_link']): ?><a href="<?= e($o['pay_link']) ?>" target="_blank" rel="noopener"><?= e(t('coins.pay_now')) ?></a> · <?php endif; ?><a href="<?= e(url('koin/pesanan/' . $o['code'])) ?>"><?= e(t('coins.detail')) ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><?= e(t('coins.ledger_h')) ?></h2>
    <?php if (!$ledger): ?><p class="muted mb-0"><?= e(t('coins.empty_ledger')) ?></p><?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th><?= e(t('coins.col.date')) ?></th><th><?= e(t('coins.col.note')) ?></th><th class="num"><?= e(t('coins.col.change')) ?></th><th class="num"><?= e(t('coins.col.balance')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($ledger as $l): ?>
        <tr>
          <td><?= e(fmt_date($l['created_at'])) ?></td>
          <td><?= e(t('coins.reason.' . $l['reason'])) ?><?= $l['reason'] === 'purchase' && $l['ref'] ? ' <span class="small muted mono">' . e($l['ref']) . '</span>' : '' ?></td>
          <td class="num mono" style="color:<?= (int) $l['delta'] >= 0 ? 'var(--good)' : 'var(--bad)' ?>"><?= (int) $l['delta'] > 0 ? '+' : '' ?><?= (int) $l['delta'] ?></td>
          <td class="num mono"><?= (int) $l['balance_after'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
</section>
