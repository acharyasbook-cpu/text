<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

$courseSlug = $_GET['course'] ?? '';
$subSlug = $_GET['sub'] ?? '';
$subjectSlug = $_GET['subject'] ?? '';

$courseRepo = new CourseRepository();
$testRepo = new TestRepository();
$subRepo = new SubscriptionRepository();

$subject = $courseRepo->findSubjectByPath($courseSlug !== '' ? $courseSlug : null, $subSlug !== '' ? $subSlug : null, $subjectSlug);
if (!$subject && $courseSlug !== '' && $subjectSlug !== '') {
    $subject = $courseRepo->findSubjectBySlugs($courseSlug, $subjectSlug);
}
if (!$subject) {
    http_response_code(404);
    exit('Subject not found');
}

$topics = $courseRepo->topicsForSubject((int) $subject['id']);
$materials = $courseRepo->materialsForSubject((int) $subject['id']);
$modules = $courseRepo->modulesForSubject((int) $subject['id']);
$subjectTests = $testRepo->forSubject((int) $subject['id']);
$testsByKind = ['topic' => [], 'division' => [], 'revision' => [], 'grand' => [], 'model' => []];
foreach ($subjectTests as $st) {
    $k = $st['test_type'] ?? 'topic';
    if (!isset($testsByKind[$k])) {
        $testsByKind[$k] = [];
    }
    $testsByKind[$k][] = $st;
}
$user = current_user();
$hasAccess = $user && $subRepo->userHasSubjectAccess((int) $user['id'], (int) $subject['id']);

$pageTitle = $subject['name'] . ' | ' . $subject['course_name'];
$activeNav = 'courses';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
  <nav class="text-sm text-slate-500 mb-6">
    <a href="<?= e(base_url('courses.php')) ?>" class="hover:text-royal">Courses</a>
    <span class="mx-2">/</span>
    <a href="<?= e(base_url('course.php?slug=' . $subject['course_slug'])) ?>" class="hover:text-royal"><?= e($subject['course_name']) ?></a>
    <?php if (!empty($subject['sub_course_slug'])): ?>
      <span class="mx-2">/</span>
      <a href="<?= e(base_url('sub_course.php?course=' . $subject['course_slug'] . '&sub=' . $subject['sub_course_slug'])) ?>" class="hover:text-royal"><?= e($subject['sub_course_name'] ?? '') ?></a>
    <?php endif; ?>
    <span class="mx-2">/</span>
    <span class="text-royal font-medium"><?= e($subject['name']) ?></span>
  </nav>

  <header class="bg-white border border-slate-200 rounded-lg p-6 lg:p-8 mb-8">
    <h1 class="font-serif text-3xl font-bold text-royal"><?= e($subject['name']) ?></h1>
    <?php if ($subject['name_te']): ?>
      <p class="font-telugu text-lg text-gold mt-2"><?= e($subject['name_te']) ?></p>
    <?php endif; ?>
    <?php if (!empty($subject['marks_allocated'])): ?>
      <p class="text-sm text-slate-600 mt-3">Marks weight: <span class="font-semibold text-royal"><?= (int) $subject['marks_allocated'] ?></span></p>
    <?php endif; ?>
    <?php if (!$hasAccess && $user): ?>
      <p class="mt-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded px-4 py-2">
        Purchase a sub-course plan to unlock all topics. Free preview topics are marked below.
      </p>
    <?php elseif (!$user): ?>
      <p class="mt-4 text-sm text-slate-600"><a href="<?= e(base_url('login.php')) ?>" class="text-royal font-semibold hover:underline">Login</a> to track progress.</p>
    <?php endif; ?>
  </header>

  <?php
  $tierTe = public_five_tier_labels_te();
  $tierEn = [
      'topic' => 'Topic-wise tests',
      'division' => 'Division tests',
      'revision' => 'Revision tests',
      'grand' => 'Grand tests',
      'model' => 'Model papers',
  ];
  ?>
  <?php if (array_sum(array_map('count', $testsByKind)) > 0): ?>
  <section class="bg-white border border-slate-200 rounded-lg p-6 mb-8">
    <h2 class="font-semibold text-xl text-royal mb-2">Online tests</h2>
    <p class="font-telugu text-xs text-slate-600 mb-4"><?= e(implode(' · ', $tierTe)) ?></p>
    <?php
    $firstTestHeading = true;
    foreach (public_five_tier_ordered_keys() as $tk):
        if (empty($testsByKind[$tk])) {
            continue;
        }
    ?>
      <h3 class="text-sm font-semibold tracking-wider text-slate-700 mb-3 <?= $firstTestHeading ? 'mt-0' : 'mt-6' ?>">
        <span class="font-telugu"><?= e($tierTe[$tk] ?? $tk) ?></span>
        <span class="text-slate-400 font-normal text-xs uppercase ml-2"><?= e($tierEn[$tk] ?? '') ?></span>
      </h3>
      <?php $firstTestHeading = false; ?>
      <div class="grid sm:grid-cols-2 gap-3">
        <?php foreach ($testsByKind[$tk] as $tst): ?>
        <div class="flex flex-wrap items-center justify-between gap-3 border border-slate-100 rounded-lg px-4 py-3">
          <div>
            <p class="font-medium text-slate-800"><?= e($tst['title']) ?></p>
            <?php if (!empty($tst['division_label'])): ?>
              <p class="text-xs text-slate-500"><?= e($tst['division_label']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-slate-500 mt-1"><?= (int) $tst['duration_mins'] ?> min · <?= (int) $tst['total_questions'] ?> Q</p>
          </div>
          <a href="<?= e(base_url('exam.php?course=' . $subject['course_slug'] . '&test=' . $tst['slug'])) ?>"
             class="px-4 py-1.5 text-sm font-semibold text-white bg-royal rounded hover:bg-royal-light shrink-0">Start</a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if ($modules): ?>
  <section class="bg-white border border-slate-200 rounded-lg p-6 mb-8">
    <h2 class="font-semibold text-lg text-royal mb-4">Practice modules</h2>
    <ul class="space-y-3">
      <?php
      $labels = ['exam' => 'Examinations', 'revision_test' => 'Revision Tests', 'division_test' => 'Division Tests'];
      foreach ($modules as $m):
          $mt = $m['module_type'] ?? '';
      ?>
      <li class="flex flex-wrap items-start justify-between gap-3 p-4 border border-slate-100 rounded-lg">
        <div>
          <span class="text-xs font-semibold uppercase tracking-wide text-gold"><?= e($labels[$mt] ?? $mt) ?></span>
          <p class="font-medium text-slate-800 mt-1"><?= e($m['title']) ?></p>
          <?php if (!empty($m['description'])): ?>
            <p class="text-sm text-slate-600 mt-1"><?= e($m['description']) ?></p>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <section class="mb-8">
    <h2 class="font-semibold text-xl text-royal mb-4">Topics & material</h2>
    <?php if (!$topics): ?>
      <p class="text-sm text-slate-500 bg-white border border-slate-200 rounded-lg p-6">Topics coming soon.</p>
    <?php else: ?>
    <ol class="space-y-6">
      <?php foreach ($topics as $i => $topic):
        $locked = !$hasAccess && empty($topic['is_free_preview']);
        $topicMats = $courseRepo->materialsForTopic((int) $topic['id']);
        $topicNotes = $courseRepo->topicNotesForDisplay($topic);
        $hasNotes = $topicNotes !== '' && !$locked;
        $suiteTests = $courseRepo->examSuiteTestsForTopic((int) $topic['id']);
        $topicExams = $courseRepo->examsForTopic((int) $topic['id']);
        $notesUrl = public_topic_notes_url($subject['course_slug'], $subject['sub_course_slug'] ?? null, $subject['slug'], $topic['slug']);
        $subjectReturn = public_subject_workspace_url($subject['course_slug'], $subject['sub_course_slug'] ?? null, $subject['slug']);
        $examTest = (!empty($topic['exam_test_id'])) ? $courseRepo->testById((int) $topic['exam_test_id']) : null;
      ?>
      <li class="bg-white border border-slate-200 rounded-lg p-5 lg:p-6 <?= $locked ? 'opacity-80' : '' ?>">
        <div class="flex flex-wrap gap-3 items-start justify-between">
          <div class="flex gap-4 min-w-0">
            <span class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-royal text-white text-sm font-bold"><?= $i + 1 ?></span>
            <div>
              <h3 class="font-semibold text-royal text-lg"><?= e($topic['title']) ?></h3>
              <?php if (!empty($topic['title_te'])): ?>
                <p class="font-telugu text-sm text-gold mt-1"><?= e($topic['title_te']) ?></p>
              <?php endif; ?>
              <?php if (!empty($topic['summary'])): ?>
                <p class="text-sm text-slate-600 mt-2"><?= e($topic['summary']) ?></p>
              <?php endif; ?>
              <p class="text-xs text-slate-500 mt-2"><?= (int) $topic['duration_mins'] ?> min
                <?php if (!empty($topic['is_free_preview'])): ?> · <span class="text-green-700 font-medium">Free preview</span><?php endif; ?>
                <?php if ($locked): ?> · <span class="text-amber-700">Locked</span><?php endif; ?>
              </p>
              <?php if (!$locked): ?>
              <div class="mt-4 grid sm:grid-cols-2 gap-3 max-w-lg">
                <?php if ($hasNotes): ?>
                <a href="<?= e($notesUrl) ?>" class="block rounded-lg border border-slate-200 bg-white p-4 hover:border-royal/40 hover:shadow-sm">
                  <p class="font-telugu text-sm font-bold text-royal">స్టడీ మెటీరియల్ / Notes</p>
                </a>
                <?php endif; ?>
                <?php foreach ($suiteTests as $stest):
                    if (empty($stest['test_slug'])) {
                        continue;
                    }
                    $examHref = base_url('exam.php?course=' . rawurlencode($subject['course_slug']) . '&test=' . rawurlencode((string) $stest['test_slug']) . '&return=' . rawurlencode($subjectReturn));
                ?>
                <a href="<?= e($examHref) ?>" class="block rounded-lg border border-slate-200 bg-white p-4 hover:border-royal/40 hover:shadow-sm">
                  <p class="font-telugu text-sm font-bold text-royal"><?= e($stest['custom_title_te'] ?: $stest['custom_title'] ?: 'ఆన్‌లైన్ పరీక్ష') ?></p>
                  <p class="text-xs text-slate-500 mt-1"><?= (int) ($stest['question_count'] ?? 50) ?> questions</p>
                </a>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex flex-col gap-2 items-end shrink-0">
            <?php if ($topicExams): ?>
              <?php foreach ($topicExams as $tex):
                  if (!empty($tex['material_url'])):
                      $murl = (string) $tex['material_url'];
                      $mhref = str_starts_with($murl, 'http') ? $murl : base_url($murl);
                  ?>
              <a href="<?= e($mhref) ?>" target="_blank" rel="noopener"
                class="px-4 py-1.5 text-sm font-semibold bg-slate-800 text-white rounded hover:bg-slate-700"><?= e($tex['title']) ?> — material →</a>
                  <?php endif;
                  if (!empty($tex['external_url'])): ?>
              <a href="<?= e((string) $tex['external_url']) ?>" target="_blank" rel="noopener"
                class="px-4 py-1.5 text-sm font-semibold text-white bg-royal rounded hover:bg-royal-light"><?= e($tex['title']) ?> →</a>
                  <?php endif;
                  if (!empty($tex['test_id'])):
                      $plat = $courseRepo->testById((int) $tex['test_id']);
                      if ($plat): ?>
              <a href="<?= e(base_url('exam.php?course=' . $subject['course_slug'] . '&test=' . $plat['slug'])) ?>"
                class="px-4 py-1.5 text-sm font-semibold border-2 border-royal text-royal rounded hover:bg-gold-pale/40"><?= e($tex['title']) ?></a>
                      <?php endif;
                  endif;
              endforeach; ?>
            <?php else: ?>
              <?php if (!empty($topic['exam_link'])): ?>
              <a href="<?= e($topic['exam_link']) ?>" target="_blank" rel="noopener" class="px-4 py-1.5 text-sm font-semibold text-white bg-royal rounded hover:bg-royal-light">Practice exam →</a>
              <?php endif; ?>
              <?php if ($examTest): ?>
              <a href="<?= e(base_url('exam.php?course=' . $subject['course_slug'] . '&test=' . $examTest['slug'])) ?>"
                class="px-4 py-1.5 text-sm font-semibold border-2 border-royal text-royal rounded hover:bg-gold-pale/40">Platform test →</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($topicMats): ?>
        <ul class="mt-4 pt-4 border-t border-slate-100 space-y-2">
          <?php foreach ($topicMats as $m): ?>
          <li class="flex flex-wrap items-center gap-2 text-sm">
            <span class="px-2 py-0.5 text-xs font-semibold uppercase bg-slate-100 text-slate-600 rounded"><?= e($m['material_type']) ?></span>
            <span class="text-slate-800"><?= e($m['title']) ?></span>
            <?php if (!empty($m['file_url'])):
                $mu = (string) $m['file_url'];
                $href = str_starts_with($mu, 'http') ? $mu : base_url($mu);
            ?>
              <a href="<?= e($href) ?>" class="text-royal font-medium text-xs hover:underline" target="_blank" rel="noopener">Open</a>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>
  </section>

  <?php if ($materials): ?>
  <section class="bg-white border border-slate-200 rounded-lg p-6">
    <h2 class="font-semibold text-lg text-royal mb-4">General study materials</h2>
    <ul class="space-y-3">
      <?php foreach ($materials as $m): ?>
      <li class="flex items-center gap-3 p-3 border border-slate-100 rounded">
        <span class="px-2 py-1 text-xs font-semibold uppercase bg-slate-100 text-slate-600 rounded"><?= e($m['material_type']) ?></span>
        <span class="text-sm font-medium text-slate-800"><?= e($m['title']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
