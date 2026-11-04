<?php
/** @var array $sponsors @var array $demo */
$letters = ['A', 'B', 'C', 'D'];
$blokCards = [
    ['code' => 'A', 'cls' => '', 'range' => '1-4', 'n' => 120],
    ['code' => 'B', 'cls' => 'card-hl', 'range' => '5-8', 'n' => 120],
    ['code' => 'C', 'cls' => '', 'range' => '9-11', 'n' => 90],
    ['code' => 'D', 'cls' => 'card-pink', 'range' => '12-14', 'n' => 90],
];
?>
<!-- 2 · Hero -->
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow"><?= e(t('landing.hero.eyebrow')) ?></span>
      <h1><span class="line"><?= e(t('landing.hero.h1a')) ?></span><span class="line"><?= e(t('landing.hero.h1b')) ?></span><span class="line hl"><?= e(t('landing.hero.h1c')) ?></span></h1>
      <p class="hero-sub"><?= e(t('landing.hero.sub_a')) ?> <span class="stabilo"><?= e(t('landing.hero.sub_hl')) ?></span><?= e(t('landing.hero.sub_b')) ?></p>
      <div class="hero-cta">
        <?= btn_primary(t('landing.hero.cta'), url(current_user() ? 'latihan' : 'daftar')) ?>
        <a class="link-arrow" href="<?= e(url('coba')) ?>"><?= e(t('landing.hero.try')) ?> <?= icon('arrow-right', 16) ?></a>
      </div>
    </div>
    <div class="hero-art"><?= partial('hero-art') ?></div>
    <div class="hero-side" aria-hidden="true"><span>LISTENING</span><span>STRUCTURE</span><span>READING</span></div>
  </div>
</section>

<!-- 3 · Marquee -->
<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span><?= str_replace('•', '<i>•</i>', e(t('landing.marquee'))) ?></span><span><?= str_replace('•', '<i>•</i>', e(t('landing.marquee'))) ?></span>
    <?php endfor; ?>
  </div>
</div>

<!-- 4 · Statistik -->
<section class="container" aria-label="<?= e(t('landing.stats.label')) ?>">
  <div class="stats">
    <div class="stat reveal"><div class="big">14</div><div class="eyebrow"><?= e(t('landing.stats.rumah')) ?></div></div>
    <div class="stat reveal"><div class="big">420<sup>+</sup></div><div class="eyebrow"><?= e(t('landing.stats.questions')) ?></div></div>
    <div class="stat reveal"><div class="big">2</div><div class="eyebrow"><?= e(t('landing.stats.sims')) ?></div></div>
    <div class="stat reveal"><div class="big">2</div><div class="eyebrow"><?= e(t('landing.stats.langs')) ?></div></div>
  </div>
</section>

<!-- 5 · Kenapa kami -->
<section class="section container" id="kenapa">
  <div class="section-head reveal">
    <span class="eyebrow"><?= e(t('landing.why.eyebrow')) ?></span>
    <h2><?= e(t('landing.why.h2a')) ?> <span class="hl"><?= e(t('landing.why.h2b')) ?></span></h2>
  </div>
  <div class="why">
    <?php foreach ([['pen', 'detail'], ['book', 'terms'], ['headphones', 'audio'], ['save', 'progress']] as [$ic, $k]): ?>
      <div class="why-item reveal">
        <div class="ico"><?= icon($ic, 24) ?></div>
        <h3><?= e(t("landing.why.$k.t")) ?></h3>
        <p><?= e(t("landing.why.$k.d")) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- 6 · Kurikulum -->
<section class="section container" id="kurikulum">
  <div class="section-head reveal">
    <span class="eyebrow"><?= e(t('landing.cur.eyebrow')) ?></span>
    <h2><?= e(t('landing.cur.h2a')) ?> <span class="stabilo"><?= e(t('landing.cur.h2b')) ?></span></h2>
    <p class="muted"><?= e(t('landing.cur.sub')) ?></p>
  </div>
  <div class="blok-cards">
    <?php foreach ($blokCards as $b): $k = strtolower($b['code']); ?>
      <article class="card blok-card <?= $b['cls'] ?> reveal">
        <?= partial('pin') ?>
        <div class="row-between"><span class="blok-letter"><?= $b['code'] ?></span><span class="chip"><?= e(t('landing.cur.qcount', ['n' => $b['n']])) ?></span></div>
        <h3 class="mt-16"><?= e(t("landing.cur.$k.name")) ?></h3>
        <p class="muted small mb-0"><?= e(t('landing.cur.range', ['r' => $b['range']])) ?></p>
        <ul>
          <?php foreach ([1, 2, 3] as $i): ?><li><?= icon('check', 16) ?><span><?= e(t("landing.cur.$k.t$i")) ?></span></li><?php endforeach; ?>
        </ul>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- 7 · Contoh pembahasan -->
<section class="section container" id="contoh">
  <div class="section-head reveal">
    <span class="eyebrow"><?= e(t('landing.demo.eyebrow')) ?></span>
    <h2><?= e(t('landing.demo.h2a')) ?> <span class="hl"><?= e(t('landing.demo.h2b')) ?></span></h2>
  </div>
  <div class="demo-grid" data-demo>
    <div class="card dots q-card reveal">
      <div class="row-between mb-16"><span class="chip chip-structure">Written Expression</span><span class="tiny muted"><?= e(t('landing.demo.instruction')) ?></span></div>
      <p class="q-prompt"><?= render_question_text($demo['prompt']) ?></p>
      <div class="options" role="group" aria-label="<?= e(t('session.options')) ?>">
        <?php foreach ($demo['options'] as $i => $opt): ?>
          <button type="button" class="option" data-demo-opt="<?= $i ?>" aria-pressed="false">
            <span class="letter"><?= $letters[$i] ?></span><span><?= e($opt) ?></span>
            <span class="opt-mark ok"><?= icon('check', 20) ?></span><span class="opt-mark no"><?= icon('x', 20) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
      <p class="tiny muted mt-16 mb-0"><?= e(t('landing.demo.hint')) ?></p>
    </div>
    <div data-demo-out aria-live="polite">
      <div class="card card-hl reveal">
        <h3><?= e(t('landing.demo.wait_t')) ?></h3>
        <p class="muted mb-0"><?= e(t('landing.demo.wait_d')) ?></p>
      </div>
    </div>
    <?php foreach ([0, 1, 2, 3] as $i): ?>
      <template data-demo-tpl="<?= $i ?>"><?= Question::render($demo, $i) ?>
        <div class="row mt-24"><?= btn_primary(t('landing.demo.more'), url('coba')) ?></div></template>
    <?php endforeach; ?>
  </div>
</section>

<!-- 8 · Format tes -->
<section class="section container" id="format">
  <div class="section-head reveal">
    <span class="eyebrow"><?= e(t('landing.format.eyebrow')) ?></span>
    <h2><?= e(t('landing.format.h2a')) ?> <span class="hl"><?= e(t('landing.format.h2b')) ?></span></h2>
  </div>
  <table class="format-table reveal">
    <caption class="sr-only"><?= e(t('landing.format.caption')) ?></caption>
    <tbody>
      <tr class="listening"><td>Listening Comprehension</td><td class="num">50 <?= e(t('common.questions')) ?></td><td class="num">45 <?= e(t('common.minutes')) ?></td></tr>
      <tr class="structure"><td>Structure &amp; Written Expression</td><td class="num">40 <?= e(t('common.questions')) ?></td><td class="num">25 <?= e(t('common.minutes')) ?></td></tr>
      <tr class="reading"><td>Reading Comprehension</td><td class="num">50 <?= e(t('common.questions')) ?></td><td class="num">55 <?= e(t('common.minutes')) ?></td></tr>
      <tr class="total"><td><?= e(t('landing.format.range')) ?></td><td class="num">140 <?= e(t('common.questions')) ?></td><td class="num">310-677</td></tr>
    </tbody>
  </table>
</section>

<!-- 9 · Cara kerja -->
<section class="section container" id="cara-kerja">
  <div class="section-head reveal">
    <span class="eyebrow"><?= e(t('landing.how.eyebrow')) ?></span>
    <h2><?= e(t('landing.how.h2a')) ?> <span class="hl"><?= e(t('landing.how.h2b')) ?></span></h2>
  </div>
  <div class="steps">
    <?php foreach ([1, 2, 3] as $i): ?>
      <div class="step reveal">
        <div class="step-num">0<?= $i ?></div>
        <h3><?= e(t("landing.how.s$i.t")) ?></h3>
        <p class="muted"><?= e(t("landing.how.s$i.d")) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- 10 · FAQ -->
<section class="section container" id="faq">
  <div class="section-head reveal">
    <span class="eyebrow">FAQ</span>
    <h2><?= e(t('landing.faq.h2a')) ?> <span class="hl"><?= e(t('landing.faq.h2b')) ?></span></h2>
  </div>
  <div class="faq">
    <?php foreach ([1, 2, 3, 4, 5, 6] as $i): ?>
      <details class="reveal">
        <summary><?= e(t("landing.faq.q$i")) ?><?= icon('chevron-down', 20) ?></summary>
        <div class="faq-body"><?= e(t("landing.faq.a$i")) ?></div>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<!-- 11 · Didukung oleh -->
<?php if ($sponsors): ?>
<section class="section container" id="sponsor" aria-labelledby="sponsor-h">
  <span class="eyebrow" id="sponsor-h"><?= e(t('sponsor.eyebrow')) ?></span>
  <div class="mt-16 stack">
    <?php foreach ($sponsors as $i => $s): $desc = active_lang() === 'en' ? ($s['desc_en'] ?: $s['desc_id']) : $s['desc_id']; ?>
      <?php if ((int) $s['is_pinned'] === 1): ?>
        <article class="card card-hl dots sponsor-main reveal">
          <div>
            <span class="chip chip-ink"><?= icon('pin', 14) ?> <?= e(t('sponsor.main')) ?></span>
            <div class="sponsor-wordmark"><?php if ($s['logo_path']): ?><img src="<?= e(url($s['logo_path'])) ?>" alt="<?= e($s['name']) ?>" width="240" height="64" loading="lazy"><?php else: ?><?= e($s['name']) ?><?php endif; ?></div>
            <p class="mb-0"><?= e((string) $desc) ?></p>
          </div>
          <a class="btn btn-dark" href="<?= e($s['url']) ?>" target="_blank" rel="noopener sponsored"><?= e(t('sponsor.visit_named', ['name' => $s['name']])) ?> ↗</a>
        </article>
      <?php else: ?>
        <article class="card card-sm reveal">
          <div class="sponsor-sub">
            <div>
              <div class="sponsor-wordmark"><?php if ($s['logo_path']): ?><img src="<?= e(url($s['logo_path'])) ?>" alt="<?= e($s['name']) ?>" width="160" height="40" loading="lazy"><?php else: ?><?= e($s['name']) ?><?php endif; ?></div>
              <p class="muted small mb-0 mt-8"><?= e((string) $desc) ?></p>
            </div>
            <a class="link-arrow" href="<?= e($s['url']) ?>" target="_blank" rel="noopener sponsored"><?= e(t('sponsor.visit')) ?> ↗</a>
          </div>
        </article>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- 12 · CTA penutup -->
<section class="section container">
  <div class="cta-band reveal">
    <span class="eyebrow" style="color:#fff"><?= e(t('landing.cta.eyebrow')) ?></span>
    <h2 class="mt-16"><?= e(t('landing.cta.h2')) ?></h2>
    <a class="btn btn-white" href="<?= e(url(current_user() ? 'latihan' : 'daftar')) ?>"><span><?= e(t('nav.start')) ?></span><span class="btn-arrow" aria-hidden="true"><?= icon('arrow-right', 18) ?></span></a>
    <svg class="cta-house" viewBox="0 0 320 260" aria-hidden="true"><path d="M20 120 160 10l140 110Z" fill="#fff" stroke="#24101C" stroke-width="5" stroke-linejoin="round"/><rect x="45" y="110" width="230" height="160" rx="28" fill="#FFE3EC" stroke="#24101C" stroke-width="5"/><rect x="135" y="170" width="50" height="100" rx="8" fill="#D6F35B" stroke="#24101C" stroke-width="5"/><rect x="70" y="140" width="44" height="40" rx="8" fill="#fff" stroke="#24101C" stroke-width="5"/><rect x="206" y="140" width="44" height="40" rx="8" fill="#fff" stroke="#24101C" stroke-width="5"/></svg>
  </div>
</section>
