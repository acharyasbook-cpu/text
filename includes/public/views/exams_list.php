<?php /** @var list<array<string,mixed>> $allTests */ ?>
<div class="mb-8">
  <h1 class="font-serif text-3xl font-bold text-slate-900">Online Examination Center</h1>
  <p class="font-telugu text-gold mt-2 font-semibold">టాపిక్, డివిజన్ మరియు గ్రాండ్ టెస్టులు</p>
  <p class="text-sm text-slate-600 mt-2">Timer-enabled MCQ tests with instant results. Access based on your sub-course packages.</p>
</div>

<div class="flex flex-wrap gap-2 mb-8">
  <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-50 text-royal border border-blue-100">Topic-wise</span>
  <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-50 text-amber-900 border border-amber-100">Division</span>
  <span class="px-3 py-1 text-xs font-semibold rounded-full bg-violet-50 text-violet-900 border border-violet-100">Grand</span>
</div>

<div class="space-y-3">
  <?php if (!$allTests): ?>
  <p class="text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-cream">పరీక్షలు ఇంకా జోడించబడలేదు.</p>
  <?php endif; ?>
  <?php foreach ($allTests as $test): ?>
  <article class="border border-[#E3E6F0] rounded-xl p-5 bg-white flex flex-wrap items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 flex-wrap">
        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-slate-100 text-slate-700 capitalize"><?= e($test['test_type']) ?></span>
        <span class="text-xs text-slate-500"><?= e($test['course_name']) ?></span>
      </div>
      <h2 class="font-semibold text-royal mt-2"><?= e($test['title']) ?></h2>
      <?php if (!empty($test['title_te'])): ?>
      <p class="font-telugu text-sm text-gold"><?= e($test['title_te']) ?></p>
      <?php endif; ?>
      <p class="text-xs text-slate-500 mt-1">
        <?= (int) $test['duration_mins'] ?> minutes · <?= (int) $test['total_questions'] ?> questions
        <?php if (!empty($test['subject_name'])): ?> · <?= e($test['subject_name']) ?><?php endif; ?>
      </p>
    </div>
    <div class="flex items-center gap-3">
      <?php if ($test['can_access']): ?>
      <a href="<?= e(base_url('exam.php?course=' . rawurlencode((string) $test['course_slug']) . '&test=' . rawurlencode((string) $test['slug']))) ?>"
         class="classical-btn-primary text-sm px-5 py-2">
        Start Exam
      </a>
      <?php else: ?>
      <span class="text-xs text-amber-800 bg-amber-50 px-3 py-2 rounded-lg border border-amber-200">Package required</span>
      <?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
</div>
