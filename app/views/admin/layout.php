<?php
$path = request_path();
$nav = [
    ['admin', 'Dashboard', 'chart'], ['admin/pengguna', 'Pengguna', 'users'], ['admin/soal', 'Soal', 'list'],
    ['admin/grup', 'Grup & Teks', 'headphones'], ['admin/impor', 'Impor / Ekspor', 'upload'], ['admin/kamus', 'Kamus', 'book'],
    ['admin/sponsor', 'Sponsor', 'pin'], ['admin/pengaturan', 'Pengaturan', 'settings'], ['admin/log', 'Log admin', 'file'],
];
$flashes = take_flashes();
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Admin') ?> · Admin PerumahanInggris</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,800&family=JetBrains+Mono:wght@500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<script type="module" src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php foreach (($scripts ?? []) as $s): ?><script type="module" src="<?= e(url('assets/js/' . $s . '.js')) ?>"></script><?php endforeach; ?>
</head>
<body>
<div class="admin-shell">
  <aside class="admin-side" data-admin-side data-open="false">
    <a class="logo" href="<?= e(url('/')) ?>"><?= partial('logo') ?></a>
    <nav aria-label="Menu admin">
      <?php foreach ($nav as [$href, $label, $ic]):
        $active = $href === 'admin' ? $path === '/admin' : str_starts_with($path, '/' . $href); ?>
        <a href="<?= e(url($href)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= icon($ic, 18) ?> <?= e($label) ?></a>
      <?php endforeach; ?>
      <hr class="divider" style="margin:16px 0">
      <a href="<?= e(url('latihan')) ?>"><?= icon('home', 18) ?> Lihat situs</a>
      <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" style="width:100%;justify-content:flex-start"><?= icon('logout', 18) ?> Keluar</button></form>
    </nav>
  </aside>
  <main class="admin-main" id="main">
    <div class="admin-top">
      <div class="row"><button class="icon-btn admin-menu-btn" type="button" data-admin-menu aria-label="Menu"><?= icon('menu') ?></button><h1 class="mb-0"><?= e($title ?? '') ?></h1></div>
      <span class="small muted"><?= e(current_user()['username'] ?? '') ?></span>
    </div>
    <?php foreach ($flashes as $f): ?><div class="flash <?= e($f['type']) ?> mb-16" style="position:static" data-flash><span><?= e($f['message']) ?></span></div><?php endforeach; ?>
    <?= $content ?>
  </main>
</div>
</body>
</html>
