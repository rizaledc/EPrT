<?php
/** @var string $content */
$title = isset($title) && $title !== '' ? $title . ' · PerumahanInggris' : t('meta.title');
$description = $description ?? t('meta.description');
$bodyClass = $bodyClass ?? '';
$path = request_path();
$canonical = abs_url($path === '/' ? '/' : $path);
$flashes = take_flashes();
?><!doctype html>
<html lang="<?= e(active_lang()) ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if (!empty($noindex) || NOINDEX): ?><meta name="robots" content="noindex, nofollow">
<?php else: ?><link rel="canonical" href="<?= e($canonical) ?>">
<link rel="alternate" hreflang="id" href="<?= e($canonical . '?lang=id') ?>">
<link rel="alternate" hreflang="en" href="<?= e($canonical . '?lang=en') ?>">
<link rel="alternate" hreflang="x-default" href="<?= e($canonical) ?>">
<?php endif; ?>
<meta name="theme-color" content="#E8336F">
<meta property="og:type" content="website">
<meta property="og:site_name" content="PerumahanInggris">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e(abs_url('assets/img/og-image.png')) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="<?= active_lang() === 'en' ? 'en_US' : 'id_ID' ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=JetBrains+Mono:wght@500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<script type="module" src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php foreach (($scripts ?? []) as $s): ?><script type="module" src="<?= e(url('assets/js/' . $s . '.js')) ?>"></script>
<?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main"><?= e(t('a11y.skip')) ?></a>
<?php if (empty($hideNav)) echo partial('nav'); ?>
<?php if ($flashes): ?>
<div class="flash-wrap" role="status" aria-live="polite">
<?php foreach ($flashes as $f): ?>
  <div class="flash <?= e($f['type']) ?>" data-flash><span><?= e($f['message']) ?></span><button type="button" aria-label="<?= e(t('common.close')) ?>" data-flash-close><?= icon('x', 18) ?></button></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?php if (empty($hideFooter)) echo partial('footer', ['compactSponsors' => $compactSponsors ?? false]); ?>
<?php if (!empty($pageData)): ?>
<script type="application/json" id="page-data"><?= json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
</body>
</html>
