<!doctype html>
<html lang="<?= e(active_lang()) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>500 · PerumahanInggris</title><meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<section class="error-page">
  <div>
    <div class="code">500</div>
    <h1 class="mt-16" style="font-size:clamp(28px,4vw,44px)"><?= e(t('error.500.h')) ?></h1>
    <p class="muted"><?= e(t('error.500.p')) ?></p>
    <?php if (!empty($error)): ?><pre style="text-align:left;white-space:pre-wrap;max-width:900px;font-size:12px;background:#fff;padding:16px;border-radius:12px"><?= e($error->getMessage() . "\n" . $error->getFile() . ':' . $error->getLine() . "\n" . $error->getTraceAsString()) ?></pre><?php endif; ?>
    <div class="row mt-24" style="justify-content:center"><a class="btn btn-primary" href="<?= e(url('/')) ?>"><span><?= e(t('error.home')) ?></span><span class="btn-arrow" aria-hidden="true">→</span></a></div>
  </div>
</section>
</body>
</html>
