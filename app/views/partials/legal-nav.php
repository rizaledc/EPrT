<?php
$path = request_path();
$links = [
    ['harga', t('legal.pricing')], ['syarat-ketentuan', t('legal.terms')], ['privasi', t('legal.privacy')],
    ['pengembalian-dana', t('legal.refund')], ['kontak', t('legal.contact')],
];
?>
<nav class="legal-nav" aria-label="<?= e(t('legal.nav')) ?>">
  <?php foreach ($links as [$p, $label]): ?>
    <a href="<?= e(url($p)) ?>"<?= $path === '/' . $p ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
