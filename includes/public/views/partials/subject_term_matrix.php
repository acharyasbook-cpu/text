<?php
/**
 * @var array<string,mixed> $termMatrix
 * @var array<string,mixed> $subject
 * @var string $courseSlug
 */
$boxes = $termMatrix['boxes'] ?? [];
$programmeAccess = !empty($termMatrix['programme_access']);
$enrollmentDay = (int) ($termMatrix['enrollment_day'] ?? 1);
$scheduleDays = (int) ($termMatrix['schedule_days'] ?? 250);
$subCourseUrl = !empty($subject['sub_course_slug'])
    ? public_sub_course_workspace_url((string) $subject['course_slug'], (string) $subject['sub_course_slug'])
    : base_url('learn.php?course=' . rawurlencode((string) $subject['course_slug']));
?>
<section class="subject-term-matrix mb-10" aria-label="Short and long term schedule">
  <?php if (!$programmeAccess): ?>
  <div class="programme-access-banner font-telugu mb-5">
    <p class="font-bold text-amber-900">ఈ విషయం యాక్సెస్ కోసం సబ్-కోర్స్ ప్లాన్ అవసరం.</p>
    <a href="<?= e($subCourseUrl) ?>#enrol" class="inline-block mt-2 text-sm font-semibold text-royal hover:underline">సబ్-కోర్స్ ఎన్‌రోల్ పేజీకి వెళ్లండి →</a>
  </div>
  <?php else: ?>
  <p class="font-telugu text-sm text-slate-600 mb-4">
    రోజు <span class="font-bold text-royal"><?= $enrollmentDay ?></span> / <?= $scheduleDays ?> —
    షార్ట్ & లాంగ్ టర్మ్ రోజువారీ పరీక్షలు (ఒకే సబ్-కోర్స్ ప్లాన్‌తో రెండూ అన్‌లాక్).
  </p>
  <?php endif; ?>

  <?php if ($boxes === []): ?>
  <p class="font-telugu text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-white">టర్మ్ బాక్స్‌లు Admin లో డిసేబుల్ చేయబడ్డాయి.</p>
  <?php else: ?>
  <div class="grid md:grid-cols-2 gap-5 lg:gap-6">
    <?php foreach ($boxes as $box):
        $termKey = (string) ($box['term_key'] ?? '');
        $labelTe = (string) ($box['label_te'] ?? '');
        $labelEn = (string) ($box['label_en'] ?? '');
        $slot = $box['today_slot'] ?? null;
        $canTake = !empty($box['can_take_today']);
        $termDays = (int) ($box['schedule_days'] ?? $scheduleDays);
        $examHref = !empty($box['today_exam_href'])
            ? base_url((string) $box['today_exam_href'])
            : '';
    ?>
    <article class="term-matrix-box" data-term="<?= e($termKey) ?>">
      <header class="term-matrix-box-head">
        <h2 class="font-telugu text-xl font-bold text-slate-900"><?= e($labelTe) ?></h2>
        <p class="text-[10px] uppercase tracking-widest text-slate-500 mt-0.5"><?= e($labelEn) ?></p>
      </header>
      <div class="term-matrix-box-body">
        <?php if (!$programmeAccess): ?>
        <p class="font-telugu text-sm text-slate-600">సబ్-కోర్స్ ఎన్‌రోల్ తర్వాత రోజువారీ పరీక్షలు అన్‌లాక్ అవుతాయి.</p>
        <?php elseif ($slot): ?>
        <p class="font-telugu text-xs text-slate-500 mb-2">నేటి స్లాట్ · రోజు <?= (int) ($box['enrollment_day'] ?? $enrollmentDay) ?> / <?= $termDays ?></p>
        <p class="font-telugu text-lg font-bold text-royal leading-snug">
          <?= e((string) ($slot['title_te'] ?? $slot['title'] ?? $slot['test_title'] ?? 'రోజువారీ పరీక్ష')) ?>
        </p>
        <?php if (!empty($slot['duration_mins']) || !empty($slot['total_questions'])): ?>
        <p class="text-xs text-slate-500 mt-2">
          <?= (int) ($slot['duration_mins'] ?? 0) ?> నిమిషాలు · <?= (int) ($slot['total_questions'] ?? 0) ?> ప్రశ్నలు
        </p>
        <?php endif; ?>
        <?php if ($canTake && $examHref !== ''): ?>
        <a href="<?= e($examHref) ?>" class="classical-btn-primary w-full mt-4 py-2.5 text-sm font-telugu text-center block">నేటి పరీక్ష ప్రారంభించు →</a>
        <?php else: ?>
        <p class="font-telugu text-xs text-amber-800 mt-3 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">ఈ రోజు పరీక్ష షెడ్యూల్ లో లేదు.</p>
        <?php endif; ?>
        <?php else: ?>
        <p class="font-telugu text-sm text-slate-600">రోజు <?= (int) ($box['enrollment_day'] ?? $enrollmentDay) ?> — షెడ్యూల్ త్వరలో అప్‌డేట్ అవుతుంది.</p>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
