// Panduan password live, tombol mata, validasi register (diulang di server)
import { $, $$ } from './app.js';

const RULES = {
  length: (p) => p.length >= 10 && p.length <= 72,
  upper: (p) => /[A-Z]/.test(p),
  lower: (p) => /[a-z]/.test(p),
  digit: (p) => /[0-9]/.test(p),
  symbol: (p) => /[^A-Za-z0-9]/.test(p),
};
const LABELS = {
  id: ['Lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'],
  en: ['Weak', 'Weak', 'Fair', 'Strong', 'Very strong'],
};
const STATE = { id: ['belum terpenuhi', 'terpenuhi'], en: ['not met', 'met'] };
const lang = document.documentElement.lang === 'en' ? 'en' : 'id';

function score(p) {
  if (!p) return 0;
  const met = Object.values(RULES).filter((f) => f(p)).length;
  let s = met <= 2 ? 1 : met === 3 || met === 4 ? 2 : 3;
  if (met === 5 && (p.length >= 14 || new Set(p).size >= 12)) s = 4;
  if (/^(.)\1+$/.test(p) || /(1234|abcd|qwer|password|sandi)/i.test(p)) s = Math.min(s, 1);
  return s;
}

// Tombol mata
$$('[data-pw-eye]').forEach((btn) => {
  btn.addEventListener('click', () => {
    const input = btn.parentElement.querySelector('input');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.querySelector('.eye-on').classList.toggle('hidden', show);
    btn.querySelector('.eye-off').classList.toggle('hidden', !show);
  });
});

// Checklist & meter
$$('[data-pw-input]').forEach((input) => {
  const guide = document.getElementById(input.id + '-guide');
  if (!guide) return;
  const update = () => {
    const p = input.value;
    $$('[data-rule]', guide).forEach((li) => {
      const ok = RULES[li.dataset.rule](p);
      li.classList.toggle('ok', ok);
      const st = $('[data-rule-state]', li);
      if (st) st.textContent = STATE[lang][ok ? 1 : 0];
    });
    const lv = score(p);
    $('[data-pw-meter]', guide).dataset.level = String(lv);
    $('[data-pw-label]', guide).textContent = p ? LABELS[lang][lv] : LABELS[lang][0];
    input.dispatchEvent(new CustomEvent('pw:update', { bubbles: true }));
  };
  input.addEventListener('input', update);
  update();
});

// Konfirmasi password
$$('[data-pw-confirm]').forEach((c) => {
  const src = document.getElementById(c.dataset.pwConfirm);
  const msg = c.closest('.field').querySelector('[data-pw-mismatch]');
  const check = () => {
    const bad = c.value !== '' && c.value !== src.value;
    msg && msg.classList.toggle('hidden', !bad);
    c.setAttribute('aria-invalid', bad ? 'true' : 'false');
  };
  c.addEventListener('input', check);
  src.addEventListener('input', check);
});

// Form register: tombol aktif hanya jika semua syarat terpenuhi
const form = $('[data-register-form]');
if (form) {
  const u = $('[data-username]', form);
  const pw = $('#password', form);
  const pc = $('#password_confirm', form);
  const agree = $('[data-agree]', form);
  const submit = $('[data-register-submit]', form);
  const hint = $('[data-username-hint]', form);
  const reserved = ['admin', 'mimin', 'root', 'perumahaninggris'];
  let userOk = false;
  let timer = null;
  const userLocal = () => /^[A-Za-z_][A-Za-z0-9_]{3,19}$/.test(u.value) && !reserved.includes(u.value.toLowerCase());
  const validate = () => {
    const p = pw.value;
    const rulesOk = Object.values(RULES).every((f) => f(p));
    const notUser = !u.value || !p.toLowerCase().includes(u.value.toLowerCase());
    const notExample = p !== 'Rumah!Biru7Kopi';
    submit.disabled = !(userLocal() && userOk && rulesOk && notUser && notExample && pc.value === p && agree.checked);
  };
  u.addEventListener('input', () => {
    userOk = false;
    validate();
    clearTimeout(timer);
    if (!userLocal()) { u.setAttribute('aria-invalid', u.value ? 'true' : 'false'); return; }
    timer = setTimeout(async () => {
      try {
        const r = await fetch(form.action.replace(/daftar$/, 'api/username-check') + '?u=' + encodeURIComponent(u.value), { headers: { Accept: 'application/json' } });
        const d = await r.json();
        userOk = !!d.valid;
        if (hint && d.message) hint.textContent = d.message;
        u.setAttribute('aria-invalid', userOk ? 'false' : 'true');
      } catch { userOk = true; }
      validate();
    }, 350);
  });
  [pw, pc].forEach((x) => x.addEventListener('input', validate));
  agree.addEventListener('change', validate);
  if (u.value) u.dispatchEvent(new Event('input'));
  validate();
}

// Halaman kode pemulihan
const saved = $('[data-saved-check]');
if (saved) {
  const btn = $('[data-saved-submit]');
  saved.addEventListener('change', () => { btn.disabled = !saved.checked; });
}
