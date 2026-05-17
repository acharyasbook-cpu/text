<?php
/**
 * Student daily schedule — multi-subject rows with progress.
 *
 * @var array<string,mixed>|null $scheduleDaily
 * @var string $courseSlug
 */
if (empty($scheduleDaily) || empty($scheduleDaily['rows'])) {
    return;
}
$rows = $scheduleDaily['rows'];
$pct = (int) ($scheduleDaily['progress_percent'] ?? 0);
$day = $scheduleDaily['day'] ?? [];
$hasAccess = !empty($scheduleDaily['has_access']);
$enrollmentDay = (int) ($scheduleDaily['enrollment_day'] ?? 1);
?>
<section class="schedule-daily-workspace mb-10 font-telugu" aria-label="Daily schedule">
  <div class="bg-white border border-[#E3E6F0] rounded-2xl p-5 sm:p-6 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      
      <div>
        <h2 class="text-xl font-bold text-slate-900">నేటి అభ్యాస లక్ష్యం</h2>
        <p class="text-sm text-slate-500 mt-0.5">
          రోజు <?= $enrollmentDay ?>
          <?php if (!empty($day['title_te'])): ?> · <?= e((string) $day['title_te']) ?><?php endif; ?>
        </p>
      </div>
      <span class="text-sm font-semibold text-brand"><?= $pct ?>% పూర్తి</span>
    </div>

    <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden mb-6" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
      <div class="h-full bg-[#4F46E5] rounded-full transition-all duration-500" style="width:<?= $pct ?>%"></div>
    </div>
    <p class="text-xs text-slate-500 mb-5"><?= e((string) ($scheduleDaily['progress_label_te'] ?? '')) ?></p>

    <?php if (!$hasAccess): ?>
    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3">
      సబ్-కోర్స్ ప్లాన్ తర్వాత పరీక్షలు అన్‌లాక్ అవుతాయి.
    </p>
    <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($rows as $row):
          $subLabel = (string) (($row['subject_name_te'] ?? '') !== '' ? $row['subject_name_te'] : ($row['subject_name'] ?? ''));
          $topics = $row['topics'] ?? [];
          $topicNames = array_map(static fn (array $t): string => (string) ($t['title'] ?? ''), $topics);
          $topicNames = array_filter($topicNames);
          $done = !empty($row['completed']);
          $href = (string) ($row['exam_href'] ?? '');
          ?>
      <article class="border border-slate-100 rounded-xl p-4 <?= $done ? 'bg-emerald-50/40' : 'bg-[#F8F9FA]' ?>">
        
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0 flex-1">
            <h3 class="font-bold text-lg text-slate-900"><?= e($subLabel) ?></h3>
            <?php if ($topicNames !== []): ?>
            <p class="text-sm text-slate-600 mt-1 leading-relaxed"><?= e(implode(' · ', $topicNames)) ?></p>
            <?php endif; ?>
            <p class="text-xs text-slate-500 mt-2">గరిష్ట మార్కులు: <strong><?= (int) ($row['total_marks'] ?? 0) ?></strong></p>
          </div>
          <div class="shrink-0">
            <?php if ($done): ?>
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-semibold">పూర్తయింది ✓</span>
            <?php elseif ($href !== ''): ?>
            <a href="<?= e($href) ?>" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-[#4F46E5] text-white text-sm font-semibold hover:bg-indigo-700 transition">
              పరీక్ష రాయండి →
            </a>
            <?php else: ?>
            <span class="text-xs text-slate-400">షెడ్యూల్ సిద్ధం కావడం లేదు</span>
            <?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
