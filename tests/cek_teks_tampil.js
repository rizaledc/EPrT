// Pemeriksa teks yang tampil pada halaman yang benar-benar dirender:
//   1. tanda kutip penanda (" ' “ ” ‘ ’, kecuali apostrof di dalam kata);
//   2. istilah lama Rumah/Blok/House/Block (kecuali nama merek PerumahanInggris).
// Membuka halaman tamu, member, dan admin dalam bahasa Indonesia dan Inggris, lalu memeriksa
// teks yang tampil (termasuk atribut title/placeholder/aria-label dan isi <template>).
// Teks soal Inggris dikecualikan: elemen bertanda [data-q], .q-prompt, .options, .passage-body,
// .transcript, .transcript-box, [data-evidence].
//
// Pemakaian:
//   BASE=http://127.0.0.1:8000 MEMBER=user:pass ADMIN=user:pass node tests/cek_teks_tampil.js
// Keluar dengan kode 1 jika ada temuan.
const { chromium } = require('playwright');

const BASE = (process.env.BASE || 'http://127.0.0.1:8000').replace(/\/$/, '');
const MEMBER = (process.env.MEMBER || '').split(':');
const ADMIN = (process.env.ADMIN || '').split(':');

const GUEST = ['/', '/kamus', '/daftar', '/masuk', '/lupa-password', '/tidak-ada', '/coba'];
const DAYS = [['a', [1, 2, 3, 4]], ['b', [5, 6, 7, 8]], ['c', [9, 10, 11]], ['d', [12, 13, 14]]]
  .flatMap(([l, ns]) => ns.map((n) => `/latihan/level-${l}/hari-${n}`));
const MEMBER_PAGES = ['/', '/latihan', ...DAYS, '/simulasi/1', '/simulasi/2', '/riwayat', '/rapor/akhir', '/akun', '/kamus'];
const ADMIN_PAGES = ['/admin', '/admin/pengguna', '/admin/soal', '/admin/grup', '/admin/impor', '/admin/kamus',
  '/admin/sponsor', '/admin/pengaturan', '/admin/log'];

function scan() {
  // textarea/input berisi data yang diedit (mis. JSON konversi skor), bukan teks antarmuka
  const EXEMPT = '[data-q], .q-prompt, .options, .passage-body, .transcript, .transcript-box, [data-evidence], script, style, noscript, code, textarea, input, select';
  const hitsOf = (s) => {
    const out = [];
    const chars = [...s];
    chars.forEach((c, i) => {
      if (!'"“”‘\'’'.includes(c)) return;
      if (c === "'" || c === '’') {
        const p = chars[i - 1] || '', n = chars[i + 1] || '';
        if (/\p{L}/u.test(p) && /\p{L}/u.test(n)) return; // apostrof di dalam kata
      }
      out.push(chars.slice(Math.max(0, i - 25), i + 25).join('').replace(/\s+/g, ' ').trim());
    });
    return out;
  };
  const textOf = (root) => {
    const clone = root.cloneNode(true);
    clone.querySelectorAll(EXEMPT).forEach((e) => e.remove());
    return clone.textContent || '';
  };
  const termsOf = (s) => {
    const out = [];
    const re = /\b(rumah|blok|houses?|blocks?)\b/gi;
    let m;
    while ((m = re.exec(s))) out.push('istilah ' + m[0] + ': ' + s.slice(Math.max(0, m.index - 25), m.index + 25).replace(/\s+/g, ' ').trim());
    return out;
  };
  const found = [];
  const check = (label, txt) => {
    const h = hitsOf(txt);
    if (h.length) found.push(label + ': kutip …' + h[0] + '…');
    termsOf(txt).forEach((t) => found.push(label + ': ' + t));
  };
  check('teks', textOf(document.body));
  document.querySelectorAll('template').forEach((t, i) => {
    const wrap = document.createElement('div');
    wrap.appendChild(t.content.cloneNode(true));
    check('template#' + i, textOf(wrap));
  });
  document.querySelectorAll('[title], [placeholder], [aria-label], [data-confirm], [alt]').forEach((el) => {
    if (el.closest(EXEMPT)) return;
    for (const a of ['title', 'placeholder', 'aria-label', 'data-confirm', 'alt']) {
      const v = el.getAttribute(a);
      if (v) check('atribut ' + a, v);
    }
  });
  check('judul halaman', document.title);
  document.querySelectorAll('meta[name="description"], meta[property^="og:"], meta[name^="twitter:"]').forEach((m) => {
    if (!/url|image/.test(m.getAttribute('property') || m.getAttribute('name') || '')) check('meta ' + (m.getAttribute('property') || m.getAttribute('name')), m.getAttribute('content') || '');
  });
  return found;
}

async function login(ctx, [u, p]) {
  const page = await ctx.newPage();
  await page.goto(BASE + '/masuk');
  await page.fill('#username', u);
  await page.fill('#password', p);
  await Promise.all([page.waitForLoadState(), page.click('button[type=submit]')]);
  await page.close();
}

(async () => {
  const browser = await chromium.launch();
  const problems = [];
  let pages = 0;
  for (const lang of ['id', 'en']) {
    for (const [role, list, cred] of [['tamu', GUEST, null], ['member', MEMBER_PAGES, MEMBER], ['admin', ADMIN_PAGES, ADMIN]]) {
      if (cred && !cred[0]) { console.log(`(lewati ${role}: kredensial tidak diisi)`); continue; }
      const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
      if (cred) await login(ctx, cred);
      const page = await ctx.newPage();
      await page.goto(`${BASE}/bahasa/${lang}?next=/`);
      for (const u of list) {
        const t0 = Date.now();
        await page.goto(BASE + u, { waitUntil: 'load' });
        await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
        if (process.env.VERBOSE) console.log(`  ${lang} ${role} ${u} ${Date.now() - t0}ms`);
        const r = await page.evaluate(scan);
        pages++;
        r.forEach((x) => problems.push(`[${lang}] ${role} ${u} ${x}`));
        if (u === '/coba') {
          // Jawab beberapa soal agar kartu pembahasan (structure & reading) ikut diperiksa
          await page.click('[data-start-btn]');
          await page.waitForSelector('.option');
          for (let i = 0; i < 10; i++) {
            const opt = page.locator('.option:not([disabled])').first();
            if (await opt.count()) {
              await opt.click();
              await page.waitForSelector('.explain', { timeout: 5000 }).catch(() => {});
              (await page.evaluate(scan)).forEach((x) => problems.push(`[${lang}] tamu /coba soal ${i + 1} ${x}`));
            }
            const next = page.locator('[data-next]');
            if (i < 9 && await next.isEnabled().catch(() => false)) await next.click();
          }
        }
      }
      // Halaman hasil pertama dari riwayat, bila ada
      if (role === 'member') {
        await page.goto(BASE + '/riwayat', { waitUntil: 'networkidle' });
        const href = await page.locator('a[href*="/hasil/"]').first().getAttribute('href').catch(() => null);
        if (href) {
          await page.goto(new URL(href, BASE).href, { waitUntil: 'networkidle' });
          (await page.evaluate(scan)).forEach((x) => problems.push(`[${lang}] member ${href} ${x}`));
          pages++;
        }
      }
      await ctx.close();
    }
  }
  await browser.close();
  console.log(`Halaman diperiksa: ${pages}`);
  if (problems.length) {
    console.log(problems.join('\n'));
    console.log(`GAGAL: ${problems.length} temuan.`);
    process.exit(1);
  }
  console.log('LULUS: tidak ada tanda kutip penanda maupun istilah Rumah/Blok di halaman yang dirender.');
})();
