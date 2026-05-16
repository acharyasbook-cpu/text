<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$user = require_login();
$testRepo = new TestRepository();
$subRepo = new SubscriptionRepository();
$courseRepo = new CourseRepository();

$courses = $courseRepo->allActive();
$allTests = [];
foreach ($courses as $c) {
    foreach ($testRepo->forCourse((int) $c['id']) as $t) {
        $t['course_name'] = $c['name'];
        $t['course_slug'] = $c['slug'];
        $t['can_access'] = $subRepo->userHasTestAccess((int) $user['id'], (int) $t['id']);
        $allTests[] = $t;
    }
}

$pageTitle = 'Online Exams | Acharya Books';
$activeNav = 'exams';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
  <div class="mb-8">
    <h1 class="font-serif text-3xl font-bold text-royal">Online Examination Center</h1>
    <p class="font-telugu text-gold mt-2">టాపిక్, డివిజన్ మరియు గ్రాండ్ టెస్టులు</p>
    <p class="text-sm text-slate-600 mt-2">Timer-enabled MCQ tests with instant results. Access based on your sub-course packages.</p>
  </div>

  <div class="flex flex-wrap gap-2 mb-8">
    <span class="px-3 py-1 text-xs font-semibold rounded test-type-topic">Topic-wise</span>
    <span class="px-3 py-1 text-xs font-semibold rounded test-type-division">Division</span>
    <span class="px-3 py-1 text-xs font-semibold rounded test-type-grand">Grand</span>
  </div>

  <div class="space-y-3">
    <?php foreach ($allTests as $test): ?>
    <article class="bg-white border border-slate-200 rounded-lg p-5 flex flex-wrap items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="px-2 py-0.5 text-xs font-semibold rounded test-type-<?= e($test['test_type']) ?>">
            <?= e(ucfirst($test['test_type'])) ?>
          </span>
          <span class="text-xs text-slate-500"><?= e($test['course_name']) ?></span>
        </div>
        <h2 class="font-semibold text-royal mt-2"><?= e($test['title']) ?></h2>
        <?php if ($test['title_te']): ?>
          <p class="font-telugu text-sm text-gold"><?= e($test['title_te']) ?></p>
        <?php endif; ?>
        <p class="text-xs text-slate-500 mt-1">
          <?= (int) $test['duration_mins'] ?> minutes · <?= (int) $test['total_questions'] ?> questions
          <?php if ($test['subject_name']): ?> · <?= e($test['subject_name']) ?><?php endif; ?>
        </p>
      </div>
      <div class="flex items-center gap-3">
        <?php if ($test['can_access']): ?>
          <a href="<?= e(base_url('exam.php?course=' . $test['course_slug'] . '&test=' . $test['slug'])) ?>"
             class="px-5 py-2 text-sm font-semibold text-white bg-royal rounded hover:bg-royal-light">
            Start Exam
          </a>
        <?php else: ?>
          <span class="text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded border border-amber-200">Package required</span>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
