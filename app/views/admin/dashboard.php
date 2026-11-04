<div class="kpis">
  <div class="card card-sm kpi"><div class="big"><?= $kpi['users'] ?></div><div class="eyebrow">Total pengguna</div></div>
  <div class="card card-sm kpi"><div class="big"><?= $kpi['new7'] ?></div><div class="eyebrow">Daftar 7 hari</div></div>
  <div class="card card-sm kpi"><div class="big"><?= $kpi['active7'] ?></div><div class="eyebrow">Aktif 7 hari</div></div>
  <div class="card card-sm kpi"><div class="big"><?= $kpi['today'] ?></div><div class="eyebrow">Sesi hari ini</div></div>
</div>
<div class="grid-2 mt-24">
  <div class="card card-sm">
    <h3>Corong penyelesaian Hari</h3>
    <?php $max = max(1, $kpi['users']); ?>
    <div class="funnel">
      <?php foreach ($funnel as $f): ?>
        <div class="funnel-row"><span>Hari <?= $f['n'] ?></span><span class="bar"><span style="width:<?= round($f['count'] / $max * 100) ?>%"></span></span><span class="mono num"><?= $f['count'] ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="stack">
    <div class="card card-sm">
      <h3>Rata-rata % benar per seksi</h3>
      <?php foreach (Practice::SECTIONS as $s): $d = $secAvg[$s] ?? null; ?>
        <div class="row-between mt-8"><span><span class="sec-dot <?= $s ?>"></span> <?= e(section_label($s)) ?></span><span class="mono"><?= $d ? $d['pct'] . '% · ' . $d['n'] . ' jawaban' : '-' ?></span></div>
        <div class="progress-bar <?= $s ?> mt-8"><span style="width:<?= $d['pct'] ?? 0 ?>%"></span></div>
      <?php endforeach; ?>
    </div>
    <div class="card card-sm">
      <h3>Audio</h3>
      <p class="mb-0"><strong><?= $missingFiles ?></strong> dari <?= $totalAudio ?> file MP3 terdaftar belum ada di server.<br>
      <strong><?= $missingAnswers ?></strong> soal pernah dijawab dengan audio hilang / suara browser (<code>audio_missing</code>).</p>
      <a class="small" href="<?= e(url('admin/grup')) ?>">Kelola audio →</a>
    </div>
  </div>
</div>
<div class="card card-sm mt-24">
  <h3>10 soal tersulit</h3>
  <?php if (!$hardest): ?><p class="muted mb-0">Belum cukup data (minimal 3 jawaban per soal).</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>#</th><th>Hari</th><th>Seksi</th><th>Soal</th><th class="num">% benar</th><th class="num">n</th></tr></thead>
    <tbody><?php foreach ($hardest as $h): ?>
      <tr><td><a href="<?= e(url('admin/soal/' . $h['id'])) ?>"><?= $h['id'] ?></a></td><td><?= e($h['slug']) ?></td><td><?= e($h['section']) ?></td>
      <td data-q><?= e(mb_strimwidth(strip_tags(str_replace(['{A}','{B}','{C}','{D}','{/A}','{/B}','{/C}','{/D}'], '', (string) $h['prompt'])), 0, 90, '…')) ?></td>
      <td class="num mono"><?= round((float) $h['p'] * 100) ?>%</td><td class="num mono"><?= $h['n'] ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</div>
