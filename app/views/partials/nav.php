<?php
$u = current_user();
$path = request_path();
$isHome = $path === '/';
$here = current_uri();
$link = fn(string $anchor) => $isHome ? '#' . $anchor : url('/#' . $anchor);
?>
<header class="nav">
  <div class="container nav-inner">
    <a class="logo" href="<?= e(url('/')) ?>" aria-label="PerumahanInggris, <?= e(t('nav.home')) ?>"><?= partial('logo') ?></a>
    <nav class="nav-links" aria-label="<?= e(t('nav.main')) ?>">
      <?php if ($u): ?>
        <a href="<?= e(url('latihan')) ?>" <?= str_starts_with($path, '/latihan') ? 'aria-current="page"' : '' ?>><?= e(t('nav.practice')) ?></a>
        <a href="<?= e(url('riwayat')) ?>" <?= $path === '/riwayat' ? 'aria-current="page"' : '' ?>><?= e(t('nav.history')) ?></a>
        <a href="<?= e(url('kamus')) ?>" <?= $path === '/kamus' ? 'aria-current="page"' : '' ?>><?= e(t('nav.glossary')) ?></a>
      <?php else: ?>
        <a href="<?= e($link('kurikulum')) ?>"><?= e(t('nav.curriculum')) ?></a>
        <a href="<?= e($link('cara-kerja')) ?>"><?= e(t('nav.how')) ?></a>
        <a href="<?= e(url('kamus')) ?>" <?= $path === '/kamus' ? 'aria-current="page"' : '' ?>><?= e(t('nav.glossary')) ?></a>
        <a href="<?= e($link('faq')) ?>">FAQ</a>
      <?php endif; ?>
    </nav>
    <div class="nav-right">
      <div class="lang-toggle hide-m" role="group" aria-label="<?= e(t('nav.lang')) ?>">
        <a href="<?= e(url('bahasa/id?next=' . rawurlencode($here))) ?>" lang="id" aria-current="<?= active_lang() === 'id' ? 'true' : 'false' ?>">ID</a>
        <a href="<?= e(url('bahasa/en?next=' . rawurlencode($here))) ?>" lang="en" aria-current="<?= active_lang() === 'en' ? 'true' : 'false' ?>">EN</a>
      </div>
      <?php if ($u): ?>
        <?php if ($u['role'] === 'admin'): ?><a class="btn btn-ghost btn-sm hide-m" href="<?= e(url('admin')) ?>">Admin</a><?php endif; ?>
        <a class="nav-user hide-m" href="<?= e(url('akun')) ?>"><span class="avatar"><?= e(mb_strtoupper(mb_substr($u['username'], 0, 1))) ?></span><?= e($u['username']) ?></a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm hide-m" href="<?= e(url('masuk')) ?>"><?= e(t('nav.login')) ?></a>
        <?= btn_primary(t('nav.start'), url('latihan'), 'btn-sm') ?>
      <?php endif; ?>
      <button class="icon-btn nav-menu-btn" type="button" aria-expanded="false" aria-controls="mobile-menu" data-menu-btn><span class="sr-only"><?= e(t('nav.menu')) ?></span><?= icon('menu') ?></button>
    </div>
  </div>
  <div class="mobile-menu" id="mobile-menu" data-open="false">
    <?php if ($u): ?>
      <a href="<?= e(url('latihan')) ?>"><?= e(t('nav.practice')) ?></a>
      <a href="<?= e(url('riwayat')) ?>"><?= e(t('nav.history')) ?></a>
      <a href="<?= e(url('kamus')) ?>"><?= e(t('nav.glossary')) ?></a>
      <a href="<?= e(url('akun')) ?>"><?= e(t('nav.account')) ?> (<?= e($u['username']) ?>)</a>
      <?php if ($u['role'] === 'admin'): ?><a href="<?= e(url('admin')) ?>">Admin</a><?php endif; ?>
    <?php else: ?>
      <a href="<?= e($link('kurikulum')) ?>"><?= e(t('nav.curriculum')) ?></a>
      <a href="<?= e($link('cara-kerja')) ?>"><?= e(t('nav.how')) ?></a>
      <a href="<?= e(url('kamus')) ?>"><?= e(t('nav.glossary')) ?></a>
      <a href="<?= e($link('faq')) ?>">FAQ</a>
      <a href="<?= e(url('masuk')) ?>"><?= e(t('nav.login')) ?></a>
    <?php endif; ?>
    <div class="row">
      <div class="lang-toggle" role="group" aria-label="<?= e(t('nav.lang')) ?>">
        <a href="<?= e(url('bahasa/id?next=' . rawurlencode($here))) ?>" lang="id" aria-current="<?= active_lang() === 'id' ? 'true' : 'false' ?>">ID</a>
        <a href="<?= e(url('bahasa/en?next=' . rawurlencode($here))) ?>" lang="en" aria-current="<?= active_lang() === 'en' ? 'true' : 'false' ?>">EN</a>
      </div>
    </div>
  </div>
</header>
