<?php
$u = current_user();
$path = request_path();
$isHome = $path === '/';
$here = current_uri();
$link = fn(string $anchor) => $isHome ? '#' . $anchor : url('/#' . $anchor);
// Item menu utama, sama untuk tamu dan member: Beranda · Kurikulum · Cara Kerja · Kamus · FAQ
$items = [
    ['href' => url('/'), 'label' => t('nav.home'), 'home' => true, 'active' => $isHome, 'spy' => 'top'],
    ['href' => $link('kurikulum'), 'label' => t('nav.curriculum'), 'spy' => 'kurikulum'],
    ['href' => $link('cara-kerja'), 'label' => t('nav.how'), 'spy' => 'cara-kerja'],
    ['href' => url('kamus'), 'label' => t('nav.glossary'), 'active' => $path === '/kamus'],
    ['href' => $link('faq'), 'label' => 'FAQ', 'spy' => 'faq'],
];
$attrs = function (array $it) use ($isHome): string {
    $a = '';
    if (!empty($it['home'])) {
        $a .= ' data-home-link';
    }
    if ($isHome && !empty($it['spy'])) {
        $a .= ' data-spy="' . e($it['spy']) . '"';
    }
    if (!empty($it['active'])) {
        $a .= ' aria-current="page"';
    }
    return $a;
};
// Tujuan tombol utama
$cta = $u ? (($t = Practice::continueTarget((int) $u['id'])) ? Rumah::url($t) : url('latihan')) : url('latihan');
$ctaLabel = $u ? t('nav.continue') : t('nav.start');
$accountItems = [];
if ($u) {
    $accountItems = [
        [url('akun'), t('nav.account'), $path === '/akun'],
        [url('riwayat'), t('nav.history'), $path === '/riwayat'],
        [url('rapor/akhir'), t('nav.report'), str_starts_with($path, '/rapor')],
    ];
    if ($u['role'] === 'admin') {
        $accountItems[] = [url('admin'), t('nav.admin'), str_starts_with($path, '/admin')];
    }
}
$initial = $u ? mb_strtoupper(mb_substr($u['username'], 0, 1)) : '';
?>
<header class="nav">
  <div class="container nav-inner">
    <a class="logo" href="<?= e(url('/')) ?>" data-home-link aria-label="PerumahanInggris, <?= e(t('nav.home')) ?>"><?= partial('logo') ?></a>
    <nav class="nav-links" aria-label="<?= e(t('nav.main')) ?>">
      <?php foreach ($items as $it): ?>
        <a href="<?= e($it['href']) ?>"<?= $attrs($it) ?>><?= e($it['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-right">
      <div class="lang-toggle hide-m" role="group" aria-label="<?= e(t('nav.lang')) ?>">
        <a href="<?= e(url('bahasa/id?next=' . rawurlencode($here))) ?>" lang="id" data-lang-switch aria-current="<?= active_lang() === 'id' ? 'true' : 'false' ?>">ID</a>
        <a href="<?= e(url('bahasa/en?next=' . rawurlencode($here))) ?>" lang="en" data-lang-switch aria-current="<?= active_lang() === 'en' ? 'true' : 'false' ?>">EN</a>
      </div>
      <?= btn_primary($ctaLabel, $cta, 'btn-sm nav-cta') ?>
      <?php if ($u): ?>
        <div class="acct hide-m" data-acct>
          <button type="button" class="acct-btn" aria-haspopup="true" aria-expanded="false" aria-controls="acct-menu" data-acct-btn>
            <span class="avatar" aria-hidden="true"><?= e($initial) ?></span>
            <span class="sr-only"><?= e(t('nav.account_menu')) ?>: <?= e($u['username']) ?></span>
            <?= icon('chevron-down', 16) ?>
          </button>
          <div class="acct-menu" id="acct-menu" role="menu" hidden data-acct-menu>
            <div class="acct-head"><span class="avatar" aria-hidden="true"><?= e($initial) ?></span><strong><?= e($u['username']) ?></strong></div>
            <?php foreach ($accountItems as [$href, $label, $active]): ?>
              <a role="menuitem" href="<?= e($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
            <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?><button type="submit" role="menuitem"><?= e(t('nav.logout')) ?></button></form>
          </div>
        </div>
      <?php endif; ?>
      <button class="icon-btn nav-menu-btn" type="button" aria-expanded="false" aria-controls="mobile-menu" data-menu-btn><span class="sr-only"><?= e(t('nav.menu')) ?></span><span class="i-open"><?= icon('menu') ?></span><span class="i-close"><?= icon('x') ?></span></button>
    </div>
  </div>
  <div class="mobile-menu" id="mobile-menu" data-open="false">
    <nav class="mm-links" aria-label="<?= e(t('nav.main')) ?>">
      <?php foreach ($items as $it): ?>
        <a href="<?= e($it['href']) ?>"<?= $attrs($it) ?>><span><?= e($it['label']) ?></span><?= icon('arrow-right', 18) ?></a>
      <?php endforeach; ?>
    </nav>
    <hr class="mm-sep">
    <div class="mm-lang">
      <span class="mm-label" id="mm-lang-label"><?= e(t('nav.lang')) ?></span>
      <div class="seg" role="group" aria-labelledby="mm-lang-label">
        <a href="<?= e(url('bahasa/id?next=' . rawurlencode($here))) ?>" lang="id" data-lang-switch aria-current="<?= active_lang() === 'id' ? 'true' : 'false' ?>">Indonesia</a>
        <a href="<?= e(url('bahasa/en?next=' . rawurlencode($here))) ?>" lang="en" data-lang-switch aria-current="<?= active_lang() === 'en' ? 'true' : 'false' ?>">English</a>
      </div>
    </div>
    <div class="mm-actions">
      <a class="btn btn-primary" href="<?= e($cta) ?>"><?= e($ctaLabel) ?></a>
    </div>
    <?php if ($u): ?>
      <hr class="mm-sep mm-sep-acct">
      <div class="mm-acct">
        <div class="acct-head"><span class="avatar" aria-hidden="true"><?= e($initial) ?></span><strong><?= e($u['username']) ?></strong></div>
        <nav class="mm-links" aria-label="<?= e(t('nav.account_menu')) ?>">
          <?php foreach ($accountItems as [$href, $label, $active]): ?>
            <a href="<?= e($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>><span><?= e($label) ?></span><?= icon('arrow-right', 18) ?></a>
          <?php endforeach; ?>
        </nav>
        <form method="post" action="<?= e(url('keluar')) ?>" class="mm-logout"><?= csrf_field() ?><button type="submit" class="btn btn-secondary"><?= e(t('nav.logout')) ?></button></form>
      </div>
    <?php endif; ?>
  </div>
</header>
<div class="menu-overlay" data-menu-overlay hidden></div>
