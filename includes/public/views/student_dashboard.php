<?php
/** @var array $user @var array $data */
$perf = $data['performance'];
$study = $data['study_progress'];
$subs = $data['subscriptions'];
$chart = $data['exam_history_chart'];
?>
<section>
  <header class="mb-8">
    <p class="text-xs font-bold uppercase tracking-widest text-royal">Student Performance Dashboard</p>
    <h1 class="font-telugu text-2xl sm:text-3xl font-bold text-slate-900 mt-1">స్వాగతం, <?= e($user['name']) ?></h1>
    <p class="text-sm text-slate-600 mt-1"><?= e($user['email']) ?><?= !empty($user['phone']) ? ' · ' . e($user['phone']) : '' ?></p>
  </header>

  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white border border-[#E3E6F0] rounded-xl p-5">
      <p class="text-xs font-bold text-slate-500 uppercase">Tests taken</p>
      <p class="text-3xl font-bold text-royal mt-2"><?= (int) ($perf['total_attempts'] ?? 0) ?></p>
    </div>
    <div class="bg-white border border-[#E3E6F0] rounded-xl p-5">
      <p class="text-xs font-bold text-slate-500 uppercase">Avg score</p>
      <p class="text-3xl font-bold text-royal mt-2"><?= e((string) ($perf['avg_percent'] ?? 0)) ?>%</p>
    </div>
    <div class="bg-white border border-[#E3E6F0] rounded-xl p-5">
      <p class="text-xs font-bold text-slate-500 uppercase font-telugu">సబ్-కోర్స్ సబ్‌స్క్రిప్షన్</p>
      <p class="text-3xl font-bold text-royal mt-2"><?= count($subs) ?></p>
    </div>
    <div class="bg-white border border-[#E3E6F0] rounded-xl p-5">
      <p class="text-xs font-bold text-slate-500 uppercase font-telugu">స్టడీ ప్రగతి</p>
      <p class="text-3xl font-bold text-royal mt-2"><?= (int) ($study['avg_progress'] ?? 0) ?>%</p>
      <p class="text-[11px] text-slate-500 mt-1"><?= (int) ($study['topics_tracked'] ?? 0) ?> topics · <?= (int) ($study['completed'] ?? 0) ?> complete</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
      <section class="bg-white border border-[#E3E6F0] rounded-xl p-6">
        <h2 class="font-telugu font-bold text-lg text-slate-900">పరీక్ష పనితీరు</h2>
        <?php if ($chart): ?>
        <div class="mt-4 flex items-end gap-2 h-40">
          <?php foreach ($chart as $bar): ?>
          <div class="flex-1 flex flex-col items-center gap-1 min-w-0">
            <div class="w-full bg-slate-100 rounded-t relative" style="height:120px">
              <div class="absolute bottom-0 left-0 right-0 bg-royal rounded-t" style="height:<?= max(4, (int) $bar['pct']) ?>%"></div>
            </div>
            <span class="text-[9px] text-slate-500 truncate w-full text-center" title="<?= e($bar['label']) ?>"><?= e($bar['label']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-500 mt-3">ఇంకా పరీక్షలు రాయలేదు.</p>
        <?php endif; ?>
      </section>

      <section class="bg-white border border-[#E3E6F0] rounded-xl p-6 overflow-x-auto">
        <h2 class="font-bold text-lg text-slate-900 mb-4">Recent attempts</h2>
        <?php if (empty($data['recent_attempts'])): ?>
        <p class="text-sm text-slate-500">No attempts yet.</p>
        <?php else: ?>
        <table class="w-full text-sm">
          <thead><tr class="text-left text-slate-500 border-b border-[#E3E6F0]"><th class="pb-2">Test</th><th class="pb-2">Score</th><th class="pb-2">Date</th></tr></thead>
          <tbody>
            <?php foreach ($data['recent_attempts'] as $a):
              $pct = $a['max_score'] > 0 ? round($a['score'] / $a['max_score'] * 100) : 0;
            ?>
            <tr class="border-b border-slate-50">
              <td class="py-2 pr-4 font-medium text-slate-800"><?= e($a['title']) ?></td>
              <td class="py-2 text-royal font-semibold"><?= (int) $a['score'] ?>/<?= (int) $a['max_score'] ?> (<?= $pct ?>%)</td>
              <td class="py-2 text-slate-500"><?= e(date('d M Y', strtotime($a['submitted_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </section>
    </div>

    <aside class="space-y-6">
      <section class="bg-white border border-[#E3E6F0] rounded-xl p-6">
        <h2 class="font-telugu font-bold text-slate-900">ఎన్రోల్ చేసిన సబ్-కోర్సులు</h2>
        <ul class="mt-4 space-y-3">
          <?php if (!$subs): ?>
          <li class="text-sm text-slate-500">No active packages.</li>
          <?php else: ?>
          <?php foreach ($subs as $s): ?>
          <li class="p-3 rounded-lg border border-[#E3E6F0] bg-cream">
            <p class="font-semibold text-sm text-slate-900"><?= e($s['name']) ?></p>
            <?php if (!empty($s['name_te'])): ?><p class="font-telugu text-xs text-gold"><?= e($s['name_te']) ?></p><?php endif; ?>
          </li>
          <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </section>
      <a href="<?= e(base_url('index.php')) ?>" class="classical-btn-primary w-full py-3 text-center block">Browse courses</a>
    </aside>
  </div>
</section>
