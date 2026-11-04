// PerumahanInggris · skrip umum (tanpa build step)
export const $ = (s, r = document) => r.querySelector(s);
export const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

export function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

/** fetch JSON dengan CSRF; body → POST */
export async function api(url, body) {
  const opt = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };
  if (body !== undefined) {
    opt.method = 'POST';
    opt.headers['Content-Type'] = 'application/json';
    opt.headers['X-CSRF-Token'] = csrf();
    opt.body = JSON.stringify(body);
  }
  let res;
  for (let attempt = 0; attempt < 3; attempt++) {
    try { res = await fetch(url, opt); break; } catch (e) { if (attempt === 2) throw e; await new Promise((r) => setTimeout(r, 600 * (attempt + 1))); }
  }
  let data = null;
  try { data = await res.json(); } catch { data = null; }
  if (res.status === 401) { location.href = '/masuk?next=' + encodeURIComponent(location.pathname); }
  if (!res.ok || !data || data.ok === false) {
    const err = new Error((data && data.error) || 'request_failed');
    err.data = data; err.status = res.status;
    throw err;
  }
  return data;
}

/**
 * POST dengan kirim ulang otomatis di latar belakang.
 * Dicoba ulang: gangguan jaringan, batas laju (429), dan error server (5xx).
 * Tidak dicoba ulang: kesalahan validasi/izin (4xx lain), karena hasilnya tidak akan berubah.
 */
export async function apiRetry(url, body, { delays = [1000, 2000, 4000, 8000, 16000], onRetry } = {}) {
  for (let i = 0; ; i++) {
    try {
      return await api(url, body);
    } catch (err) {
      const retryable = err.status === undefined || err.status === 429 || err.status >= 500;
      if (!retryable || i >= delays.length) throw err;
      if (onRetry) onRetry(i + 1, delays.length);
      await new Promise((r) => setTimeout(r, delays[i]));
    }
  }
}

export function toast(message, type = 'info') {
  let wrap = $('.flash-wrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.className = 'flash-wrap';
    wrap.setAttribute('role', 'status');
    wrap.setAttribute('aria-live', 'polite');
    document.body.appendChild(wrap);
  }
  const f = document.createElement('div');
  f.className = 'flash ' + type;
  f.innerHTML = '<span>' + escapeHtml(message) + '</span>';
  wrap.appendChild(f);
  setTimeout(() => f.remove(), 4500);
}

/** Sorot kalimat bukti di dalam teks reading (boleh melewati tag <strong>). */
export function highlightEvidence(container, quote) {
  if (!container || !quote) return;
  const raw = container.dataset.raw || container.innerHTML;
  container.dataset.raw = raw;
  const tokens = escapeHtml(quote.replace(/\*\*/g, '').trim()).split(/\s+/).map((t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
  const tag = '(?:<\\/?strong>)?';
  const re = new RegExp(tag + tokens.join('(?:\\s|' + tag + ')+') + tag, 'i');
  container.innerHTML = raw.replace(re, (m) => '<mark>' + m + '</mark>');
  const mk = container.querySelector('mark');
  if (mk && container.scrollHeight > container.clientHeight) mk.scrollIntoView({ block: 'center', behavior: 'smooth' });
}

/* ---------- Perilaku global ---------- */
document.documentElement.classList.remove('no-js');

// Menu HP: overlay, kunci gulir, tutup lewat Esc / ketuk di luar
const menuBtn = $('[data-menu-btn]');
const menu = $('#mobile-menu');
const overlay = $('[data-menu-overlay]');
let menuY = 0; // posisi gulir saat menu dibuka
function setMenu(open) {
  if (!menu || !menuBtn) return;
  if (open && menu.dataset.open !== 'true') menuY = window.scrollY;
  menu.dataset.open = open ? 'true' : 'false';
  menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  if (overlay) overlay.hidden = !open;
  document.documentElement.classList.toggle('menu-lock', open);
}
if (menuBtn && menu) {
  menuBtn.addEventListener('click', () => setMenu(menu.dataset.open !== 'true'));
  if (overlay) overlay.addEventListener('click', () => setMenu(false));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.dataset.open === 'true') { setMenu(false); menuBtn.focus(); }
  });
  $$('.mm-links a, .mm-actions a', menu).forEach((a) => a.addEventListener('click', () => setMenu(false)));
  window.addEventListener('resize', () => { if (window.innerWidth > 960 && menu.dataset.open === 'true') setMenu(false); });
}
// Ganti bahasa tanpa menutup menu dan tanpa loncat ke atas
const LANG_KEY = 'pi-lang-restore';
$$('[data-lang-switch]').forEach((a) => a.addEventListener('click', () => {
  try {
    sessionStorage.setItem(LANG_KEY, JSON.stringify(menu && menu.dataset.open === 'true' ? { y: menuY, open: true } : { y: window.scrollY, open: false }));
  } catch (e) { /* penyimpanan tidak tersedia */ }
}));
try {
  const saved = JSON.parse(sessionStorage.getItem(LANG_KEY) || 'null');
  if (saved) {
    sessionStorage.setItem(LANG_KEY, 'null');
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    window.scrollTo({ top: saved.y || 0, left: 0, behavior: 'instant' });
    if (saved.open) setMenu(true);
  }
} catch (e) { /* penyimpanan tidak tersedia */ }

// Beranda & logo: di landing, gulir halus ke atas (hormati prefers-reduced-motion)
const onHome = () => location.pathname.replace(/\/+$/, '') === (new URL($('[data-home-link]')?.href || '/', location.href)).pathname.replace(/\/+$/, '');
$$('[data-home-link]').forEach((a) => a.addEventListener('click', (e) => {
  if (!onHome()) return;
  e.preventDefault();
  if (typeof setMenu === 'function') setMenu(false);
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
  if (location.hash) history.replaceState(null, '', location.pathname + location.search);
}));

// Penanda menu aktif di landing: Beranda saat di atas, lalu Kurikulum / Cara Kerja / FAQ sesuai bagian yang terlihat
const spyLinks = $$('[data-spy]');
if (spyLinks.length) {
  const ids = [...new Set(spyLinks.map((a) => a.dataset.spy).filter((id) => id !== 'top'))];
  const sections = ids.map((id) => document.getElementById(id)).filter(Boolean);
  const setActive = (id) => spyLinks.forEach((a) => {
    if (a.dataset.spy === id) a.setAttribute('aria-current', id === 'top' ? 'page' : 'location');
    else a.removeAttribute('aria-current');
  });
  const update = () => {
    const line = window.innerHeight * 0.35;
    let current = 'top';
    for (const s of sections) {
      if (s.getBoundingClientRect().top <= line) current = s.id;
    }
    // Bagian setelah FAQ (CTA, footer) tetap menandai FAQ hanya jika FAQ masih terlihat
    const last = sections[sections.length - 1];
    if (current === last?.id && last.getBoundingClientRect().bottom < 0) current = '';
    setActive(current);
  };
  let ticking = false;
  window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => { update(); ticking = false; });
  }, { passive: true });
  update();
}

// Menu akun (avatar)
const acct = $('[data-acct]');
if (acct) {
  const btn = $('[data-acct-btn]', acct);
  const panel = $('[data-acct-menu]', acct);
  const setAcct = (open) => {
    panel.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) $('a, button', panel)?.focus();
  };
  btn.addEventListener('click', () => setAcct(panel.hidden));
  document.addEventListener('click', (e) => { if (!panel.hidden && !acct.contains(e.target)) setAcct(false); });
  document.addEventListener('keydown', (e) => {
    if (panel.hidden) return;
    if (e.key === 'Escape') { setAcct(false); btn.focus(); }
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      const items = $$('a, button', panel);
      const i = items.indexOf(document.activeElement);
      const n = e.key === 'ArrowDown' ? (i + 1) % items.length : (i - 1 + items.length) % items.length;
      items[n].focus();
      e.preventDefault();
    }
  });
}

// Salin email di halaman Kontak
const copyBtn = $('[data-copy-btn]');
if (copyBtn) {
  const text = $('[data-copy-text]')?.textContent.trim() || '';
  const status = $('[data-copy-status]');
  copyBtn.addEventListener('click', async () => {
    let ok = false;
    try { await navigator.clipboard.writeText(text); ok = true; } catch (e) {
      // Cadangan untuk browser tanpa Clipboard API
      const ta = document.createElement('textarea');
      ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
      ta.parentNode.removeChild(ta);
    }
    if (ok && status) status.textContent = copyBtn.dataset.copied;
  });
}

// Flash
$$('[data-flash-close]').forEach((b) => b.addEventListener('click', () => b.closest('[data-flash]').remove()));
setTimeout(() => $$('[data-flash]').forEach((f) => f.remove()), 7000);

// Animasi masuk sekali saat scroll
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const reveals = $$('.reveal');
if (!reduce && 'IntersectionObserver' in window) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, { rootMargin: '0px 0px -40px 0px' });
  reveals.forEach((el) => io.observe(el));
} else {
  reveals.forEach((el) => el.classList.add('in'));
}

// Tooltip istilah: ketuk untuk buka/tutup di layar sentuh
document.addEventListener('click', (ev) => {
  const t = ev.target.closest('[data-term-tip]');
  $$('[data-term-tip][data-open="true"]').forEach((x) => { if (x !== t) x.dataset.open = 'false'; });
  if (t && !ev.target.closest('a')) { t.dataset.open = t.dataset.open === 'true' ? 'false' : 'true'; }
});
document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') $$('[data-term-tip][data-open="true"]').forEach((x) => { x.dataset.open = 'false'; }); });

// Tombol salin
$$('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
  const src = $(b.dataset.copy);
  if (!src) return;
  const text = src.textContent.trim();
  try { await navigator.clipboard.writeText(text); } catch {
    const r = document.createRange(); r.selectNodeContents(src); const s = getSelection(); s.removeAllRanges(); s.addRange(r); document.execCommand('copy');
  }
  const l = $('[data-copy-label]', b);
  if (l) { const old = l.textContent; l.textContent = '✓'; setTimeout(() => { l.textContent = old; }, 1500); }
}));

// Tab riwayat (hash #kesalahan)
const tabs = $$('[data-tab]');
if (tabs.length) {
  const show = (name) => {
    tabs.forEach((t) => t.setAttribute('aria-current', t.dataset.tab === name ? 'true' : 'false'));
    $$('[data-panel]').forEach((p) => { p.hidden = p.dataset.panel !== name; });
  };
  tabs.forEach((t) => t.addEventListener('click', (ev) => { ev.preventDefault(); show(t.dataset.tab); history.replaceState(null, '', '#' + t.dataset.tab); }));
  const h = location.hash.replace('#', '');
  if (tabs.some((t) => t.dataset.tab === h)) show(h);
}
