// Halaman hasil: sorot bukti reading saat pembahasan dibuka, buka item dari strip nomor
import { $$, highlightEvidence } from './app.js';

$$('[data-evidence-host]').forEach((host) => {
  const quote = host.dataset.evidenceHost;
  if (quote) highlightEvidence(host, quote);
});

function openFromHash() {
  if (!location.hash.startsWith('#q-')) return;
  const d = document.getElementById(location.hash.slice(1));
  if (d && d.tagName === 'DETAILS') { d.open = true; d.scrollIntoView({ block: 'start' }); }
}
window.addEventListener('hashchange', openFromHash);
openFromHash();
