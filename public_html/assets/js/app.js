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

// Menu HP
const menuBtn = $('[data-menu-btn]');
const menu = $('#mobile-menu');
if (menuBtn && menu) {
  menuBtn.addEventListener('click', () => {
    const open = menu.dataset.open !== 'true';
    menu.dataset.open = open ? 'true' : 'false';
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  $$('a', menu).forEach((a) => a.addEventListener('click', () => { menu.dataset.open = 'false'; menuBtn.setAttribute('aria-expanded', 'false'); }));
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
