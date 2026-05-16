<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

$slug = $_GET['slug'] ?? '';
$courseRepo = new CourseRepository();
$testRepo = new TestRepository();
$subRepo = new SubscriptionRepository();

$course = $courseRepo->findBySlug($slug);
if (!$course) {
    http_response_code(404);
    exit('Course not found');
}

$fourTier = SchemaHelper::hierarchyFourTier();
$subCourseBlocks = $fourTier ? $courseRepo->subjectsGroupedBySubCourse((int) $course['id']) : [];
$subjectsGrouped = $fourTier ? [] : $courseRepo->subjectsGroupedByCategory((int) $course['id']);
$tests = $testRepo->forCourse((int) $course['id']);
$packages = $subRepo->packagesForCourse((int) $course['id']);
$user = current_user();

$testsByType = ['topic' => [], 'division' => [], 'revision' => [], 'grand' => [], 'model' => []];
foreach ($tests as $t) {
    $k = $t['test_type'] ?? 'topic';
    if (!isset($testsByType[$k])) {
        $testsByType[$k] = [];
    }
    $testsByType[$k][] = $t;
}

$pageTitle = $course['name'] . ' | Acharya Books';
$activeNav = 'courses';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
  <nav class="text-sm text-slate-500 mb-6">
    <a href="<?= e(base_url('courses.php')) ?>" class="hover:text-royal">Courses</a>
    <span class="mx-2">/</span>
    <span class="text-royal font-medium"><?= e($course['name']) ?></span>
  </nav>

  <header class="bg-white border border-slate-200 rounded-lg p-6 lg:p-10 mb-8">
    <span class="text-xs font-semibold uppercase text-gold"><?= e($course['region'] ?? '') ?></span>
    <h1 class="font-serif text-3xl lg:text-4xl font-bold text-royal mt-2"><?= e($course['name']) ?></h1>
    <?php if ($course['name_te']): ?>
      <p class="font-telugu text-lg text-gold mt-2"><?= e($course['name_te']) ?></p>
    <?php endif; ?>
    <p class="text-slate-600 mt-4 max-w-3xl"><?= e($course['description'] ?? '') ?></p>
  </header>

  <div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
      <section>
        <h2 class="font-semibold text-xl text-royal mb-4"><?= $fourTier ? 'Programmes & papers' : 'Subjects & Study Path' ?></h2>
        <?php if ($fourTier): ?>
          <?php if (!$subCourseBlocks): ?>
            <p class="text-sm text-slate-500">Sub-courses will appear here once added in Admin.</p>
          <?php else: ?>
            <div class="grid sm:grid-cols-2 gap-4">
              <?php foreach ($subCourseBlocks as $block): ?>
              <?php $scrow = $block['sub_course']; ?>
              <a href="<?= e(base_url('sub_course.php?course=' . $course['slug'] . '&sub=' . $scrow['slug'])) ?>"
                 class="block bg-white border border-slate-200 rounded-lg overflow-hidden hover:border-gold/50 hover:shadow-md transition-all group">
                <?php if (!empty($scrow['image_path'])): ?>
                <div class="h-32 bg-slate-50 overflow-hidden"><img src="<?= e(public_media_url((string) $scrow['image_path'])) ?>" alt="" class="w-full h-full object-cover" loading="lazy" /></div>
                <?php endif; ?>
                <div class="p-6">
                <h3 class="font-serif text-xl font-bold text-royal group-hover:text-royal-light"><?= e($scrow['name']) ?></h3>
                <?php if (!empty($scrow['name_te'])): ?>
                  <p class="font-telugu text-sm text-gold mt-1"><?= e($scrow['name_te']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-slate-600 mt-3"><?= count($block['subjects']) ?> subject(s)</p>
                <p class="text-xs text-gold mt-3 font-semibold">View syllabus, pricing & topics →</p>
                </div>
              </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php elseif (!$subjectsGrouped): ?>
          <p class="text-sm text-slate-500">Subjects will appear here once configured.</p>
        <?php else: ?>
          <?php foreach ($subjectsGrouped as $block): ?>
          <div class="mb-8">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3"><?= e($block['label']) ?></h3>
            <div class="grid sm:grid-cols-2 gap-4">
              <?php foreach ($block['subjects'] as $sub): ?>
              <a href="<?= e(base_url('subject.php?course=' . $course['slug'] . '&subject=' . $sub['slug'])) ?>"
                 class="block bg-white border border-slate-200 rounded-lg p-5 hover:border-gold/50 hover:shadow-md transition-all group">
                <h4 class="font-semibold text-royal group-hover:text-royal-light"><?= e($sub['name']) ?></h4>
                <?php if ($sub['name_te']): ?>
                  <p class="font-telugu text-sm text-gold mt-1"><?= e($sub['name_te']) ?></p>
                <?php endif; ?>
                <?php if (!empty($sub['marks_allocated'])): ?>
                  <p class="text-xs text-slate-500 mt-2"><?= (int) $sub['marks_allocated'] ?> marks</p>
                <?php endif; ?>
                <p class="text-xs text-gold mt-3 font-medium">Topics & materials →</p>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <section>
        <h2 class="font-semibold text-xl text-royal mb-2">Online Examination System</h2>
        <p class="font-telugu text-sm text-slate-600 mb-4"><?= e(implode(' · ', public_five_tier_labels_te())) ?></p>
        <?php
        $tierTe = public_five_tier_labels_te();
        $tierEn = [
          'topic' => 'Topic-wise tests',
          'division' => 'Division tests',
          'revision' => 'Revision tests',
          'grand' => 'Grand tests',
          'model' => 'Model papers',
        ];
        foreach (public_five_tier_ordered_keys() as $type):
        ?>
          <?php if (empty($testsByType[$type])) {
              continue;
          } ?>
          <div class="mb-6">
            <h3 class="text-sm font-semibold tracking-wider text-slate-700 mb-3"><span class="font-telugu"><?= e($tierTe[$type] ?? $type) ?></span><span class="text-slate-400 font-normal text-xs uppercase ml-2"><?= e($tierEn[$type] ?? '') ?></span></h3>
            <div class="space-y-2">
              <?php foreach ($testsByType[$type] as $test): ?>
              <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-slate-200 rounded-lg px-4 py-3">
                <div>
                  <p class="font-medium text-slate-800"><?= e($test['title']) ?></p>
                  <?php if ($test['division_label']): ?>
                    <p class="text-xs text-slate-500"><?= e($test['division_label']) ?></p>
                  <?php endif; ?>
                  <p class="text-xs text-slate-500 mt-1"><?= (int) $test['duration_mins'] ?> min · <?= (int) $test['total_questions'] ?> Q</p>
                </div>
                <a href="<?= e(base_url('exam.php?course=' . $course['slug'] . '&test=' . $test['slug'])) ?>"
                   class="px-4 py-1.5 text-sm font-semibold text-white bg-royal rounded hover:bg-royal-light">
                  Start Test
                </a>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </section>
    </div>

    <aside class="space-y-6">
      <section class="bg-gold-pale/50 border border-gold/30 rounded-lg p-6">
        <h2 class="font-semibold text-royal"><?= $fourTier ? 'Other plans' : 'Sub-course Packages' ?></h2>
        <p class="text-xs text-slate-600 mt-2"><?= $fourTier ? 'Open a programme above for standard 6-month, 1-year and up-to-exam pricing.' : 'Purchase individual subjects or division test packs — not daily limits.' ?></p>
        <ul class="mt-4 space-y-3">
          <?php foreach ($packages as $pkg): ?>
          <li class="bg-white rounded border border-slate-200 p-4">
            <p class="font-medium text-sm text-royal"><?= e($pkg['name']) ?></p>
            <?php if ($pkg['name_te']): ?>
              <p class="font-telugu text-xs text-gold"><?= e($pkg['name_te']) ?></p>
            <?php endif; ?>
            <p class="text-lg font-bold text-royal mt-2">₹<?= number_format((float) $pkg['price_inr'], 0) ?></p>
            <p class="text-xs text-slate-500 capitalize mt-1"><?= e(str_replace('_', ' ', $pkg['package_type'])) ?></p>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php if (!$user): ?>
          <a href="<?= e(base_url('login.php')) ?>" class="mt-4 block text-center text-sm font-semibold text-royal hover:underline">Login to access</a>
        <?php endif; ?>
      </section>
    </aside>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
