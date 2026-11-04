// Kamus Istilah: pencarian + filter grup
import { $, $$ } from './app.js';

const search = $('[data-g-search]');
const items = $$('[data-g-item]');
const count = $('[data-g-count]');
let group = 'all';
const lang = document.documentElement.lang === 'en' ? 'en' : 'id';

function apply() {
  const qv = (search ? search.value : '').trim().toLowerCase();
  let n = 0;
  items.forEach((it) => {
    const ok = (group === 'all' || it.dataset.grp === group) && (!qv || it.dataset.text.includes(qv));
    it.hidden = !ok;
    if (ok) n++;
  });
  if (count) count.textContent = lang === 'en' ? `${n} terms` : `${n} istilah`;
}
if (search) search.addEventListener('input', apply);
$$('[data-g-filter]').forEach((b) => b.addEventListener('click', () => {
  group = b.dataset.gFilter;
  $$('[data-g-filter]').forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
  apply();
}));
if (location.hash) {
  const t = document.getElementById(location.hash.slice(1));
  if (t) t.hidden = false;
}
apply();
