// Demo soal interaktif di landing page (tanpa login, sepenuhnya di klien)
import { $, $$ } from './app.js';

const demo = $('[data-demo]');
if (demo) {
  const out = $('[data-demo-out]', demo);
  const opts = $$('[data-demo-opt]', demo);
  const CORRECT = 1;
  opts.forEach((b) => b.addEventListener('click', () => {
    const i = Number(b.dataset.demoOpt);
    opts.forEach((o) => {
      const j = Number(o.dataset.demoOpt);
      o.disabled = true;
      o.setAttribute('aria-pressed', j === i ? 'true' : 'false');
      o.classList.toggle('correct', j === CORRECT);
      o.classList.toggle('wrong', j === i && i !== CORRECT);
    });
    const tpl = $(`template[data-demo-tpl="${i}"]`, demo);
    out.innerHTML = '';
    out.appendChild(tpl.content.cloneNode(true));
    const first = out.querySelector('.explain');
    if (first && window.innerWidth < 960) first.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }));
}

// Marquee: berhenti selama disentuh atau ditekan, lanjut saat dilepas
const marquee = $('.marquee');
if (marquee) {
  const pause = () => marquee.classList.add('is-paused');
  const resume = () => marquee.classList.remove('is-paused');
  marquee.addEventListener('pointerdown', pause);
  ['pointerup', 'pointercancel', 'pointerleave'].forEach((ev) => marquee.addEventListener(ev, resume));
}
