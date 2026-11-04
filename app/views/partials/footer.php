<?php
$here = current_uri();
$compact = !empty($compactSponsors);
?>
<footer class="footer">
  <div class="container">
    <?php if ($compact): ?>
      <div class="sponsor-strip mb-24"><span class="eyebrow"><?= e(t('sponsor.eyebrow')) ?></span>
        <?php foreach (Sponsor::all() as $s): ?>
          <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener sponsored"><?= e($s['name']) ?> ↗</a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="footer-grid">
      <div>
        <a class="logo" href="<?= e(url('/')) ?>"><?= partial('logo') ?></a>
        <p class="muted mt-16 small prose"><?= e(t('footer.tagline')) ?></p>
      </div>
      <div>
        <h4><?= e(t('footer.pages')) ?></h4>
        <ul>
          <li><a href="<?= e(url('latihan')) ?>"><?= e(t('nav.practice')) ?></a></li>
          <li><a href="<?= e(url('coba')) ?>"><?= e(t('footer.try')) ?></a></li>
          <li><a href="<?= e(url('kamus')) ?>"><?= e(t('nav.glossary')) ?></a></li>
          <li><a href="<?= e(url('/#faq')) ?>">FAQ</a></li>
        </ul>
      </div>
      <div>
        <h4><?= e(t('footer.account')) ?></h4>
        <ul>
          <?php if (current_user()): ?>
            <li><a href="<?= e(url('akun')) ?>"><?= e(t('nav.account')) ?></a></li>
            <li><a href="<?= e(url('riwayat')) ?>"><?= e(t('nav.history')) ?></a></li>
            <li><form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?><button class="btn-ghost" style="border:0;padding:0;background:none;font-weight:600"><?= e(t('nav.logout')) ?></button></form></li>
          <?php else: ?>
            <li><a href="<?= e(url('daftar')) ?>"><?= e(t('nav.register')) ?></a></li>
            <li><a href="<?= e(url('masuk')) ?>"><?= e(t('nav.login')) ?></a></li>
            <li><a href="<?= e(url('lupa-password')) ?>"><?= e(t('auth.forgot_link')) ?></a></li>
          <?php endif; ?>
        </ul>
        <div class="lang-toggle mt-16" role="group" aria-label="<?= e(t('nav.lang')) ?>">
          <a href="<?= e(url('bahasa/id?next=' . rawurlencode($here))) ?>" lang="id" aria-current="<?= active_lang() === 'id' ? 'true' : 'false' ?>">ID</a>
          <a href="<?= e(url('bahasa/en?next=' . rawurlencode($here))) ?>" lang="en" aria-current="<?= active_lang() === 'en' ? 'true' : 'false' ?>">EN</a>
        </div>
      </div>
    </div>
    <div class="disclaimer">
      <p><?= e(t('footer.disclaimer')) ?></p>
      <span>© 2026 PerumahanInggris</span>
    </div>
  </div>
</footer>
