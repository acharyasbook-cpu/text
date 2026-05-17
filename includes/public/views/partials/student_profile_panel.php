<?php
/**
 * Student profile — subscriptions & enrolled programmes (dashboard profile tab only).
 *
 * @var array<string,mixed> $user
 * @var array<string,mixed> $data
 */
$subs = $data['subscriptions'] ?? [];
$enrollments = $data['sub_course_enrollments'] ?? [];
?>
<section class="student-profile-panel font-telugu" aria-labelledby="profilePanelTitle">
  <header class="mb-6">
    <h2 id="profilePanelTitle" class="text-xl font-bold text-slate-900">మీ ప్రొఫైల్</h2>
    <p class="text-sm text-slate-600 mt-1"><?= e($user['name']) ?> · <?= e($user['email']) ?></p>
    <?php if (!empty($user['phone'])): ?>
    <p class="text-sm text-slate-500"><?= e($user['phone']) ?></p>
    <?php endif; ?>
  </header>

  <div class="grid lg:grid-cols-2 gap-6">
    <section class="bg-white border border-[#E3E6F0] rounded-xl p-6">
      <h3 class="font-bold text-slate-900 mb-1">సక్రియ సబ్‌స్క్రిప్షన్లు</h3>
      <p class="text-xs text-slate-500 mb-4">ప్లాన్ స్థితి · గడువు తేదీ</p>
      <?php if ($subs === []): ?>
      <p class="text-sm text-slate-500">ప్రస్తుతం సక్రియ ప్యాకేజీలు లేవు.</p>
      <a href="<?= e(base_url('index.php')) ?>" class="inline-block mt-4 text-sm font-semibold text-royal hover:underline">కోర్సులు బ్రౌజ్ చేయండి →</a>
      <?php else: ?>
      <ul class="space-y-3">
        <?php foreach ($subs as $s):
            $label = (string) ($s['plan_label'] ?? $s['name'] ?? 'Package');
            $course = (string) ($s['course_slug'] ?? '');
            $subCourse = (string) ($s['plan_sub_course_name'] ?? $s['name_te'] ?? '');
            $expires = !empty($s['expires_at'])
                ? date('d M Y', strtotime((string) $s['expires_at']))
                : 'పరీక్ష వరకు / లైఫ్‌టైమ్';
            $purchased = !empty($s['purchased_at'])
                ? date('d M Y', strtotime((string) $s['purchased_at']))
                : '—';
        ?>
        <li class="p-4 rounded-lg border border-[#E3E6F0] bg-cream">
          <p class="font-semibold text-slate-900"><?= e($label) ?></p>
          <?php if ($subCourse !== ''): ?>
          <p class="text-xs text-gold font-semibold mt-0.5"><?= e($subCourse) ?></p>
          <?php endif; ?>
          <?php if ($course !== ''): ?>
          <p class="text-[11px] text-slate-500 mt-1"><?= e($course) ?></p>
          <?php endif; ?>
          <dl class="mt-3 grid grid-cols-2 gap-2 text-[11px] text-slate-600">
            <div><dt class="font-semibold text-slate-700">కొనుగోలు</dt><dd><?= e($purchased) ?></dd></div>
            <div><dt class="font-semibold text-slate-700">గడువు</dt><dd><?= e($expires) ?></dd></div>
          </dl>
          <span class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wide text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">Active</span>
          <?php
            $cSlug = (string) ($s['course_slug'] ?? '');
            $scSlug = (string) ($s['sub_course_slug'] ?? '');
            if ($cSlug !== '' && $scSlug !== ''):
          ?>
          <a href="<?= e(public_sub_course_workspace_url($cSlug, $scSlug)) ?>"
             class="text-xs text-royal font-semibold mt-2 inline-block hover:underline">ప్రోగ్రామ్ తెరవండి →</a>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>

    <section class="bg-white border border-[#E3E6F0] rounded-xl p-6">
      <h3 class="font-bold text-slate-900 mb-1">ఎన్‌రోల్ చేసిన ప్రోగ్రామ్‌లు</h3>
      <p class="text-xs text-slate-500 mb-4">మీరు యాక్సెస్ కలిగిన సబ్-కోర్సులు</p>
      <?php if ($enrollments === []): ?>
      <p class="text-sm text-slate-500">ఇంకా ఎన్‌రోల్ చేసిన ప్రోగ్రామ్‌లు లేవు.</p>
      <?php else: ?>
      <ul class="space-y-3">
        <?php foreach ($enrollments as $e): ?>
        <li class="p-3 rounded-lg border border-[#E3E6F0]">
          <p class="font-semibold text-sm text-slate-900"><?= e((string) ($e['name'] ?? '')) ?></p>
          <?php if (!empty($e['name_te'])): ?>
          <p class="font-telugu text-xs text-gold"><?= e((string) $e['name_te']) ?></p>
          <?php endif; ?>
          <?php
            $cSlug = (string) ($e['course_slug'] ?? '');
            $scSlug = (string) ($e['sub_course_slug'] ?? $e['slug'] ?? '');
            if ($cSlug !== '' && $scSlug !== ''):
          ?>
          <a href="<?= e(public_sub_course_workspace_url($cSlug, $scSlug)) ?>"
             class="text-xs text-royal font-semibold mt-2 inline-block hover:underline">ప్రోగ్రామ్ తెరవండి →</a>
          <?php elseif ($cSlug !== ''): ?>
          <a href="<?= e(base_url('learn.php?course=' . rawurlencode($cSlug))) ?>"
             class="text-xs text-royal font-semibold mt-2 inline-block hover:underline">కోర్స్ తెరవండి →</a>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>
  </div>
</section>
