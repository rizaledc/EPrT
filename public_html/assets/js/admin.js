// Panel admin: menu HP + dialog konfirmasi di dalam halaman
import { $, $$ } from './app.js';

const side = $('[data-admin-side]');
const btn = $('[data-admin-menu]');
if (side && btn) btn.addEventListener('click', () => { side.dataset.open = side.dataset.open === 'true' ? 'false' : 'true'; });

// Konfirmasi memakai <dialog>, bukan confirm()
const dlg = document.createElement('dialog');
dlg.className = 'dlg';
dlg.innerHTML = '<div class="dlg-body"><h2>Yakin?</h2><p class="muted" data-msg></p><div class="dlg-actions"><button type="button" class="btn btn-ghost" data-no>Batal</button><button type="button" class="btn btn-danger" data-yes>Ya, lanjutkan</button></div></div>';
document.body.appendChild(dlg);
let pending = null;
$$('[data-confirm]').forEach((b) => b.addEventListener('click', (ev) => {
  if (b.dataset.confirmed === '1') return;
  ev.preventDefault();
  pending = b;
  $('[data-msg]', dlg).textContent = b.dataset.confirm;
  dlg.showModal();
}));
$('[data-no]', dlg).addEventListener('click', () => { dlg.close(); pending = null; });
$('[data-yes]', dlg).addEventListener('click', () => {
  dlg.close();
  if (!pending) return;
  pending.dataset.confirmed = '1';
  pending.click();
});
