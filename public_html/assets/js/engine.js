// PerumahanInggris · mesin sesi soal (Latihan, Simulasi, Review, Rumah Contoh)
import { api, apiRetry, $, $$, toast, highlightEvidence, escapeHtml } from './app.js';

const pageEl = document.getElementById('page-data');
const cfg = pageEl ? JSON.parse(pageEl.textContent) : null;
const root = document.querySelector('[data-engine]');

if (cfg && root) {
  const T = (k, vars = {}) => {
    let s = cfg.i18n[k] ?? k;
    for (const [a, b] of Object.entries(vars)) s = s.replaceAll('{' + a + '}', b);
    return s;
  };
  const LETTERS = ['A', 'B', 'C', 'D'];
  const mode = cfg.mode; // practice | simulation | review | sample
  const isSim = mode === 'simulation';
  const isSample = mode === 'sample';

  const el = {
    loading: $('[data-loading]'), start: $('[data-start]'), startBtn: $('[data-start-btn]'), startTitle: $('[data-start-title]'), startRules: $('[data-start-rules]'),
    layout: $('[data-layout]'), navcard: $('[data-navcard]'), nav: $('[data-nav]'), qnum: $('[data-qnum]'), prompt: $('[data-prompt]'), options: $('[data-options]'),
    explain: $('[data-explain]'), next: $('[data-next]'), prev: $('[data-prev]'), flag: $('[data-flag]'), flagLabel: $('[data-flag-label]'), finish: $('[data-finish]'),
    progress: $('[data-progress]'), bar: $('[data-bar]'), timer: $('[data-timer]'), timerText: $('[data-timer-text]'),
    passage: $('[data-passage]'), passageTitle: $('[data-passage-title]'), passageBody: $('[data-passage-body]'), passageToggle: $('[data-passage-toggle]'),
    audio: $('[data-audio]'), audioStatus: $('[data-audio-status]'), countdown: $('[data-countdown]'), speed: $('[data-speed]'), replay: $('[data-replay]'),
    readTranscript: $('[data-read-transcript]'), transcriptBox: $('[data-transcript-box]'),
    finishDlg: $('[data-finish-dialog]'), finishText: $('[data-finish-text]'), finishOk: $('[data-finish-ok]'), finishCancel: $('[data-finish-cancel]'),
    sampleDlg: $('[data-sample-dialog]'), sampleText: $('[data-sample-text]'),
    timerToggleWrap: $('[data-timer-toggle-wrap]'), timerToggle: $('[data-timer-toggle]'), secChip: $('[data-sec-chip]'),
  };

  const S = {
    questions: [], passages: {}, answers: {}, idx: 0, attempt: null,
    qStart: performance.now(), audioMissing: {}, playedTalk: new Set(), busy: false,
    remaining: null, practiceLeft: null, rate: 1, playToken: 0, finished: false, started: false,
  };

  /* ---------------- Utilitas ---------------- */
  const cur = () => S.questions[S.idx];
  const ans = (q) => S.answers[String(q.id)];
  const isAnswered = (q) => { const a = ans(q); return a && a.chosen !== null && a.chosen !== undefined; };
  const revealed = (q) => { const a = ans(q); return a && a.html; };
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const fmt = (s) => { const neg = s < 0; s = Math.abs(Math.round(s)); const m = Math.floor(s / 60); return (neg ? '+' : '') + m + ':' + String(s % 60).padStart(2, '0'); };
  const lsGet = (k, d) => { try { const v = localStorage.getItem(k); return v === null ? d : v; } catch { return d; } };
  const lsSet = (k, v) => { try { localStorage.setItem(k, v); } catch { /* abaikan */ } };

  /* ---------------- Muat data ---------------- */
  async function load() {
    try {
      const data = await api(cfg.endpoints.load);
      S.attempt = data.attempt;
      S.questions = data.questions || [];
      S.passages = data.passages || {};
      S.answers = Array.isArray(data.answers) ? {} : (data.answers || {});
      S.remaining = data.attempt.remaining_s;
      if (data.attempt.finished) { location.href = cfg.exitUrl; return; }
      if (!S.questions.length) { el.loading.textContent = T('session.loading'); return; }
      const firstOpen = S.questions.findIndex((q) => !isAnswered(q));
      S.idx = firstOpen === -1 ? S.questions.length - 1 : firstOpen;
      showStart(firstOpen > 0);
    } catch (e) {
      el.loading.textContent = T('session.offline');
    }
  }

  function showStart(resuming) {
    el.loading.hidden = true;
    el.start.hidden = false;
    if (resuming) el.startTitle.textContent = T('session.continue_auto');
    const rules = (isSim ? T('session.sim_rules') : T('session.practice_rules')).split('|');
    if (isSim) rules.unshift(T('session.timer_reminder').replace('{t}', fmt(S.remaining || 0)));
    el.startRules.innerHTML = rules.map((r) => `<li><span>${escapeHtml(r)}</span></li>`).join('');
    el.startBtn.addEventListener('click', begin, { once: true });
    el.startBtn.focus();
  }

  function begin() {
    S.started = true;
    el.start.hidden = true;
    el.layout.hidden = false;
    el.navcard.hidden = false;
    // "Buka" sintesis suara dengan gestur pengguna (iOS/Safari)
    try { if ('speechSynthesis' in window) { const u = new SpeechSynthesisUtterance(''); speechSynthesis.speak(u); } } catch { /* abaikan */ }
    setupTimer();
    render();
  }

  /* ---------------- Render ---------------- */
  function render() {
    const q = cur();
    if (!q) return;
    stopAudio();
    S.qStart = performance.now();
    el.explain.innerHTML = '';
    el.transcriptBox.hidden = true;
    el.transcriptBox.innerHTML = '';
    const total = S.questions.length;
    el.progress.textContent = `${S.idx + 1} / ${total}`;
    el.bar.style.width = ((Object.values(S.answers).filter((a) => a.chosen !== null && a.chosen !== undefined).length / total) * 100) + '%';
    el.qnum.textContent = T('session.question_of', { n: S.idx + 1, t: total });
    if (cfg.section === 'mixed' && el.secChip) {
      el.secChip.className = 'chip chip-' + q.section;
      el.secChip.textContent = q.section.charAt(0).toUpperCase() + q.section.slice(1);
      document.body.className = document.body.className.replace(/sec-\w+/, 'sec-' + q.section);
    }

    // Passage reading
    const p = q.passage ? S.passages[String(q.passage)] : null;
    if (p && p.section === 'reading') {
      el.passage.hidden = false;
      el.layout.classList.add('with-passage');
      if (el.passageBody.dataset.pid !== String(p.id)) {
        el.passageTitle.textContent = p.title || '';
        el.passageBody.innerHTML = p.html;
        el.passageBody.dataset.pid = String(p.id);
        el.passageBody.dataset.raw = p.html;
      } else {
        el.passageBody.innerHTML = el.passageBody.dataset.raw;
      }
    } else {
      el.passage.hidden = true;
      el.layout.classList.remove('with-passage');
    }

    // Teks soal
    if (q.section === 'listening') {
      el.prompt.innerHTML = q.listening.show_prompt ? `<p class="mb-0"><strong>${escapeHtml(q.listening.prompt)}</strong></p>` : `<p class="small muted mb-0">${escapeHtml(T('session.listen_first'))}</p>`;
    } else {
      el.prompt.innerHTML = q.html;
    }

    // Pilihan
    const a = ans(q);
    const locked = isLocked(q);
    el.options.innerHTML = q.options.map((opt, i) => `
      <button type="button" class="option" data-i="${i}" aria-pressed="${a && a.chosen === i ? 'true' : 'false'}" ${locked ? 'disabled' : ''}>
        <span class="letter">${LETTERS[i]}</span><span>${escapeHtml(opt)}</span>
        <span class="opt-mark ok" aria-hidden="true">✓</span><span class="opt-mark no" aria-hidden="true">✕</span>
      </button>`).join('');
    $$('.option', el.options).forEach((b) => b.addEventListener('click', () => choose(Number(b.dataset.i))));
    if (revealed(q)) applyReveal(q, a, false);

    // Tanda ragu
    const flagged = !!(a && a.flagged);
    el.flag.setAttribute('aria-pressed', flagged ? 'true' : 'false');
    el.flagLabel.textContent = flagged ? T('session.flagged') : T('session.flag');
    el.flag.hidden = isSample;

    // Navigasi
    const simListening = isSim && q.section === 'listening';
    el.prev.hidden = simListening || S.idx === 0;
    updateNextButton();
    renderNav();

    // Audio
    if (q.section === 'listening') {
      el.audio.hidden = false;
      el.speed.hidden = isSim;
      el.replay.hidden = true;
      el.readTranscript.hidden = true;
      el.countdown.hidden = true;
      if (!isAnswered(q) || isSim) playFor(q, false);
      else { el.audioStatus.textContent = T('session.audio_done'); el.replay.hidden = false; }
    } else {
      el.audio.hidden = true;
    }
  }

  function isLocked(q) {
    if (S.finished) return true;
    if (isSim) return q.section === 'listening' && isAnswered(q);
    return !!revealed(q);
  }

  function updateNextButton() {
    const last = S.idx === S.questions.length - 1;
    const q = cur();
    if (isSim && q.section === 'listening') { el.next.hidden = true; return; }
    el.next.hidden = false;
    el.next.innerHTML = last ? escapeHtml(T('session.see_result')) + ' →' : escapeHtml(T('session.next')) + ' →';
  }

  function renderNav() {
    const simListening = isSim && cur().section === 'listening';
    el.nav.innerHTML = S.questions.map((q, i) => {
      const a = ans(q);
      let cls = '';
      if (a && a.chosen !== null && a.chosen !== undefined) cls = a.html !== undefined && !isSim ? (a.is_correct ? 'correct' : 'wrong') : 'answered';
      if (a && a.flagged) cls += ' flagged';
      if (i === S.idx) cls += ' current';
      return `<button type="button" class="${cls}" data-go="${i}" ${simListening ? 'disabled' : ''} aria-label="${i + 1}">${i + 1}</button>`;
    }).join('');
    $$('[data-go]', el.nav).forEach((b) => b.addEventListener('click', () => go(Number(b.dataset.go))));
  }

  function go(i) {
    if (i < 0 || i >= S.questions.length) return;
    S.idx = i;
    render();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /* ---------------- Menjawab ---------------- */
  async function choose(i) {
    const q = cur();
    if (S.busy || isLocked(q)) return;
    const timeMs = Math.round(performance.now() - S.qStart);
    const prev = ans(q) || {};
    if (isSim) {
      S.answers[String(q.id)] = { ...prev, chosen: i };
      $$('.option', el.options).forEach((b) => b.setAttribute('aria-pressed', Number(b.dataset.i) === i ? 'true' : 'false'));
      if (q.section === 'listening') $$('.option', el.options).forEach((b) => { b.disabled = true; });
      renderNav();
      el.bar.style.width = ((Object.values(S.answers).filter((a) => a.chosen !== null && a.chosen !== undefined).length / S.questions.length) * 100) + '%';
      try {
        const r = await apiRetry(cfg.endpoints.answer, { attempt_id: cfg.attemptId, question_id: q.id, chosen_index: i, time_ms: timeMs, flagged: !!prev.flagged, audio_missing: !!S.audioMissing[q.id] });
        if (r.remaining_s !== undefined && r.remaining_s !== null) S.remaining = r.remaining_s;
      } catch (e) {
        if (e.data && e.data.redirect) { location.href = e.data.redirect; return; }
        if (!(e.data && e.data.error === 'locked')) toast(T('session.save_error'), 'error');
      }
      return;
    }
    // Latihan / review / contoh: kirim lalu tampilkan pembahasan
    S.busy = true;
    $$('.option', el.options).forEach((b) => { b.disabled = true; b.setAttribute('aria-pressed', Number(b.dataset.i) === i ? 'true' : 'false'); });
    try {
      const body = isSample ? { question_id: q.id, chosen_index: i } : { attempt_id: cfg.attemptId, question_id: q.id, chosen_index: i, time_ms: timeMs, flagged: !!prev.flagged, audio_missing: !!S.audioMissing[q.id] };
      const r = await apiRetry(cfg.endpoints.answer, body, {
        onRetry: () => { if (cur() === q) el.qnum.textContent = T('session.retrying'); },
      });
      const a = { ...prev, chosen: i, is_correct: r.is_correct, correct_index: r.correct_index, html: r.html, evidence: r.evidence };
      S.answers[String(q.id)] = a;
      // Pengguna bisa pindah soal selama kirim ulang: tampilkan pembahasan hanya bila soal ini masih terbuka
      if (cur() === q) {
        el.qnum.textContent = T('session.question_of', { n: S.idx + 1, t: S.questions.length });
        applyReveal(q, a, true);
        if (q.section === 'listening') { el.replay.hidden = false; }
      }
      renderNav();
      el.bar.style.width = ((Object.values(S.answers).filter((x) => x.chosen !== null && x.chosen !== undefined).length / S.questions.length) * 100) + '%';
      if (r.progress && r.progress.just_completed) toast(T('session.completed_rumah'), 'success');
    } catch (e) {
      if (cur() === q) {
        el.qnum.textContent = T('session.question_of', { n: S.idx + 1, t: S.questions.length });
        $$('.option', el.options).forEach((btn) => { btn.disabled = false; });
      }
      toast(T('session.save_error'), 'error');
    } finally {
      S.busy = false;
    }
  }

  function applyReveal(q, a, scroll) {
    $$('.option', el.options).forEach((b) => {
      const i = Number(b.dataset.i);
      b.disabled = true;
      b.classList.toggle('correct', i === a.correct_index);
      b.classList.toggle('wrong', i === a.chosen && i !== a.correct_index);
    });
    el.explain.innerHTML = a.html;
    if (q.section === 'reading' && a.evidence) highlightEvidence(el.passageBody, a.evidence);
    if (scroll) {
      const card = el.explain.firstElementChild;
      if (card) setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 60);
    }
  }

  async function toggleFlag() {
    const q = cur();
    const a = S.answers[String(q.id)] || { chosen: null };
    a.flagged = !a.flagged;
    S.answers[String(q.id)] = a;
    el.flag.setAttribute('aria-pressed', a.flagged ? 'true' : 'false');
    el.flagLabel.textContent = a.flagged ? T('session.flagged') : T('session.flag');
    renderNav();
    if (!isSample) { try { await api(cfg.endpoints.flag, { attempt_id: cfg.attemptId, question_id: q.id, flagged: a.flagged }); } catch { /* abaikan */ } }
  }

  function next() {
    const q = cur();
    if (!isSim && !isSample && !isAnswered(q) && !revealed(q)) {
      // Boleh dilewati di mode latihan
    }
    if (S.idx === S.questions.length - 1) { askFinish(); return; }
    go(S.idx + 1);
  }

  /* ---------------- Selesai ---------------- */
  function askFinish() {
    if (isSample) { finishSample(); return; }
    const unanswered = S.questions.filter((q) => !isAnswered(q)).length;
    el.finishText.textContent = unanswered > 0 ? T('session.finish_unanswered', { n: unanswered }) : T('session.finish_confirm_d');
    if (typeof el.finishDlg.showModal === 'function') el.finishDlg.showModal();
    else if (window.confirm(el.finishText.textContent)) finish();
  }

  async function finish() {
    if (S.finished) return;
    S.finished = true;
    stopAudio();
    try {
      const r = await api(cfg.endpoints.finish, {});
      location.href = r.redirect;
    } catch {
      S.finished = false;
      toast(T('session.save_error'), 'error');
    }
  }

  function finishSample() {
    const answered = S.questions.filter((q) => isAnswered(q));
    const correct = answered.filter((q) => ans(q).is_correct).length;
    el.sampleText.textContent = T('session.sample_done_d', { c: correct, t: S.questions.length });
    if (typeof el.sampleDlg.showModal === 'function') el.sampleDlg.showModal();
  }

  /* ---------------- Timer ---------------- */
  let timerInt = null;
  let tickInt = null;
  function setupTimer() {
    const tempo = S.attempt.tempo || 0;
    if (isSim) {
      el.timer.hidden = false;
      paintTimer(S.remaining);
      timerInt = setInterval(() => {
        if (S.remaining === null) return;
        S.remaining = Math.max(0, S.remaining - 1);
        paintTimer(S.remaining);
        if (S.remaining <= 0) { clearInterval(timerInt); toast(T('session.time_up'), 'error'); finish(); }
      }, 1000);
      tickInt = setInterval(async () => {
        try {
          const r = await api(cfg.endpoints.tick, {});
          if (r.finished && r.redirect) { location.href = r.redirect; return; }
          if (typeof r.remaining_s === 'number') S.remaining = r.remaining_s;
        } catch { /* coba lagi pada detak berikutnya */ }
      }, 10000);
      return;
    }
    if (tempo > 0 && !isSample && cfg.section !== 'listening') {
      el.timerToggleWrap.hidden = false;
      const on = lsGet('pi_timer', '1') === '1';
      el.timerToggle.checked = on;
      el.timer.hidden = !on;
      const unanswered = S.questions.filter((q) => !isAnswered(q)).length;
      S.practiceLeft = unanswered * tempo;
      paintTimer(S.practiceLeft, true);
      timerInt = setInterval(() => {
        S.practiceLeft -= 1;
        paintTimer(S.practiceLeft, true);
      }, 1000);
      el.timerToggle.addEventListener('change', () => {
        lsSet('pi_timer', el.timerToggle.checked ? '1' : '0');
        el.timer.hidden = !el.timerToggle.checked;
      });
    }
  }
  function paintTimer(s, reminder = false) {
    el.timerText.textContent = fmt(s);
    el.timer.classList.toggle('over', s < 0 || (!reminder && s <= 0));
    el.timer.classList.toggle('warn', s >= 0 && s <= 60);
    el.timer.setAttribute('aria-label', reminder ? T('session.timer_reminder').replace('{t}', fmt(s)) : fmt(s));
  }

  /* ---------------- Audio: MP3 berurutan + suara cadangan browser ---------------- */
  const audioEl = new Audio();
  audioEl.preload = 'auto';
  let voices = [];
  const loadVoices = () => { try { voices = speechSynthesis.getVoices().filter((v) => /^en[-_]/i.test(v.lang)); } catch { voices = []; } };
  if ('speechSynthesis' in window) { loadVoices(); speechSynthesis.onvoiceschanged = loadVoices; }
  const FEMALE = /female|woman|zira|samantha|aria|jenny|michelle|sonia|emma|karen|moira|tessa|victoria|susan|hazel|libby|ava|allison|serena|fiona|kate|natasha|joanna|salli|kimberly|ivy/i;
  function pickVoice(g, n) {
    if (!voices.length) return null;
    const us = voices.filter((v) => /en[-_]US/i.test(v.lang));
    const pool = (us.length ? us : voices);
    const f = pool.filter((v) => FEMALE.test(v.name));
    const m = pool.filter((v) => !FEMALE.test(v.name));
    const list = g === 'f' ? (f.length ? f : pool) : (m.length ? m : pool);
    return list[n % list.length];
  }

  function stopAudio() {
    S.playToken++;
    try { audioEl.pause(); } catch { /* abaikan */ }
    try { if ('speechSynthesis' in window) speechSynthesis.cancel(); } catch { /* abaikan */ }
    el.audio.classList.remove('playing');
    clearInterval(countdownInt);
    el.countdown.hidden = true;
  }

  function playUrl(url, token) {
    return new Promise((resolve, reject) => {
      if (token !== S.playToken) return resolve();
      audioEl.src = url;
      audioEl.playbackRate = isSim ? 1 : S.rate;
      try { audioEl.preservesPitch = true; audioEl.mozPreservesPitch = true; audioEl.webkitPreservesPitch = true; } catch { /* abaikan */ }
      const done = () => { cleanup(); resolve(); };
      const fail = () => { cleanup(); reject(new Error('audio')); };
      const cleanup = () => { audioEl.removeEventListener('ended', done); audioEl.removeEventListener('error', fail); };
      audioEl.addEventListener('ended', done);
      audioEl.addEventListener('error', fail);
      const pr = audioEl.play();
      if (pr && pr.catch) pr.catch(fail);
    });
  }

  function speak(text, g, n, token) {
    return new Promise((resolve, reject) => {
      if (token !== S.playToken) return resolve();
      if (!('speechSynthesis' in window)) return reject(new Error('tts'));
      const u = new SpeechSynthesisUtterance(text);
      u.lang = 'en-US';
      const v = pickVoice(g, n);
      if (v) u.voice = v;
      u.rate = isSim ? 1 : S.rate;
      u.pitch = g === 'f' ? 1.05 : 0.95;
      let finished = false;
      const end = () => { if (!finished) { finished = true; resolve(); } };
      u.onend = end;
      u.onerror = (ev) => { if (ev.error === 'interrupted' || ev.error === 'canceled') end(); else if (!finished) { finished = true; reject(new Error('tts')); } };
      speechSynthesis.speak(u);
      // Pengaman bila onend tidak terpanggil (beberapa browser)
      setTimeout(end, Math.max(4000, text.length * 110));
    });
  }

  /** Item: {url, text, g, kind} atau {pause: ms} atau {script: [...]} */
  async function playItems(items, q) {
    const token = ++S.playToken;
    el.audio.classList.add('playing');
    el.audioStatus.textContent = T('session.playing');
    let usedTts = false;
    let failed = false;
    for (const it of items) {
      if (token !== S.playToken) return { aborted: true };
      if (it.pause) { await sleep(it.pause); continue; }
      if (it.status) el.audioStatus.textContent = it.status;
      if (it.script) {
        usedTts = true;
        for (let i = 0; i < it.script.length; i++) {
          if (token !== S.playToken) return { aborted: true };
          const line = it.script[i];
          try { await speak(line.text, line.g, voiceIndex(line.speaker), token); } catch { failed = true; break; }
          await sleep(450);
        }
        if (failed) break;
        continue;
      }
      if (it.url) {
        try { await playUrl(it.url, token); continue; } catch { /* jatuh ke TTS */ }
      }
      if (it.text) {
        usedTts = true;
        try { await speak(it.text, it.g || 'm', 0, token); } catch { failed = true; break; }
      } else if (!it.url) {
        failed = true; break;
      }
    }
    if (token !== S.playToken) return { aborted: true };
    el.audio.classList.remove('playing');
    if (usedTts || failed) { S.audioMissing[q.id] = true; }
    return { usedTts, failed };
  }

  const voiceIndex = (sp) => { const m = /\d+/.exec(sp || ''); return m ? Number(m[0]) : 0; };

  function itemsFor(q, replay) {
    const L = q.listening;
    const p = q.passage ? S.passages[String(q.passage)] : null;
    const items = [{ pause: 300 }];
    if (isSim && L.directions && !replay) items.push({ url: L.directions.url, text: L.directions.text, g: 'm', status: T('session.directions') }, { pause: 900 });
    const needTalk = replay || (L.first_in_group || !S.playedTalk.has(q.passage));
    if (needTalk && p) {
      const talkRef = L.talk || findTalkRef(q.passage);
      if (talkRef && talkRef.url) items.push({ url: talkRef.url, status: T('session.talk_audio') });
      else if (p.script) items.push({ script: p.script, status: T('session.audio_tts') });
      else items.push({ url: null, text: null });
      items.push({ pause: 900 });
    }
    if (isSim && L.number) items.push({ url: L.number.url, text: L.number.text, g: 'm' });
    items.push({ url: L.question ? L.question.url : null, text: L.prompt, g: 'm', status: T('session.question_audio') });
    return items;
  }

  function findTalkRef(pid) {
    const first = S.questions.find((x) => x.passage === pid && x.listening && x.listening.talk);
    return first ? first.listening.talk : null;
  }

  async function playFor(q, replay) {
    el.replay.hidden = true;
    el.readTranscript.hidden = true;
    const res = await playItems(itemsFor(q, replay), q);
    if (res.aborted) return;
    S.playedTalk.add(q.passage);
    if (res.failed) {
      el.audioStatus.textContent = T('session.audio_missing');
      el.readTranscript.hidden = false;
    } else {
      el.audioStatus.textContent = res.usedTts ? T('session.audio_tts') + ' · ' + T('session.audio_done') : T('session.audio_done');
    }
    if (isSim) { simCountdown(q); return; }
    if (isAnswered(q)) el.replay.hidden = false;
  }

  let countdownInt = null;
  function simCountdown(q) {
    let left = S.attempt.sim_answer_s || 12;
    el.countdown.hidden = false;
    el.countdown.textContent = left;
    el.audioStatus.textContent = T('session.answer_time');
    clearInterval(countdownInt);
    countdownInt = setInterval(async () => {
      left -= 1;
      el.countdown.textContent = Math.max(0, left);
      if (left <= 0) {
        clearInterval(countdownInt);
        if (cur() !== q) return;
        if (!isAnswered(q)) {
          // Simpan sebagai kosong agar tercatat (audio_missing ikut dikirim)
          try { await apiRetry(cfg.endpoints.answer, { attempt_id: cfg.attemptId, question_id: q.id, chosen_index: null, time_ms: Math.round(performance.now() - S.qStart), flagged: false, audio_missing: !!S.audioMissing[q.id] }); } catch { /* abaikan */ }
        }
        if (S.idx < S.questions.length - 1) go(S.idx + 1);
        else finish();
      }
    }, 1000);
  }

  function showTranscript() {
    const q = cur();
    const p = q.passage ? S.passages[String(q.passage)] : null;
    const lines = (p && p.script) ? p.script.map((l) => `<div><span class="sp mono tiny">${escapeHtml(l.speaker)}</span> ${escapeHtml(l.text)}</div>`).join('') : '';
    el.transcriptBox.innerHTML = `<strong class="small">${escapeHtml(T('session.transcript'))}</strong><div class="transcript small mt-8">${lines}<div><span class="sp mono tiny">N</span> <em>${escapeHtml(q.listening.prompt)}</em></div></div>`;
    el.transcriptBox.hidden = false;
    S.audioMissing[q.id] = true;
  }

  /* ---------------- Event ---------------- */
  el.next.addEventListener('click', next);
  el.prev.addEventListener('click', () => go(S.idx - 1));
  el.flag.addEventListener('click', toggleFlag);
  el.finish.addEventListener('click', askFinish);
  el.finishOk.addEventListener('click', () => { el.finishDlg.close(); finish(); });
  el.finishCancel.addEventListener('click', () => el.finishDlg.close());
  el.replay.addEventListener('click', () => { if (!isSim) playFor(cur(), true); });
  el.readTranscript.addEventListener('click', showTranscript);
  el.passageToggle.addEventListener('click', () => {
    const c = el.passage.dataset.collapsed === 'true';
    el.passage.dataset.collapsed = c ? 'false' : 'true';
    el.passageToggle.setAttribute('aria-expanded', c ? 'true' : 'false');
    el.passageToggle.textContent = c ? T('session.hide_passage') : T('session.show_passage');
  });
  $$('[data-rate]', el.speed).forEach((b) => b.addEventListener('click', () => {
    S.rate = Number(b.dataset.rate);
    audioEl.playbackRate = S.rate;
    $$('[data-rate]', el.speed).forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
  }));
  document.addEventListener('keydown', (ev) => {
    if (!S.started || ev.ctrlKey || ev.metaKey || ev.altKey) return;
    const t = ev.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT')) return;
    if (document.querySelector('dialog[open]')) return;
    const k = ev.key.toLowerCase();
    const map = { a: 0, b: 1, c: 2, d: 3, 1: 0, 2: 1, 3: 2, 4: 3 };
    if (k in map) { ev.preventDefault(); choose(map[k]); }
    else if (k === 'enter' && !(t && t.tagName === 'BUTTON')) { ev.preventDefault(); if (!el.next.hidden) next(); }
  });
  $$('[data-exit]').forEach((a) => a.addEventListener('click', () => stopAudio()));
  window.addEventListener('pagehide', () => stopAudio());

  load();
}
