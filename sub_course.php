<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

$courseSlug = $_GET['course'] ?? '';
$subSlug = $_GET['sub'] ?? '';

$courseRepo = new CourseRepository();
$subRepo = new SubscriptionRepository();

$subCourse = $courseRepo->findSubCourseBySlugs($courseSlug, $subSlug);
if (!$subCourse) {
    http_response_code(404);
    exit('Sub-course not found');
}

$course = $courseRepo->findBySlug($courseSlug);
$subjects = $courseRepo->subjectsForSubCourse((int) $subCourse['id']);
$plans = $courseRepo->plansForSubCourse((int) $subCourse['id']);
$user = current_user();

$pageTitle = $subCourse['name'] . ' | ' . ($course['name'] ?? '');
$activeNav = 'courses';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';

$planUi = [
    '6_months' => ['short' => '6 Mo', 'hint' => '6 months access'],
    '1_year' => ['short' => '1 Yr', 'hint' => '12 months access'],
    'until_exam' => ['short' => 'Exam', 'hint' => 'Valid until examination'],
];
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
  <nav class="text-sm text-slate-500 mb-6">
    <a href="<?= e(base_url('courses.php')) ?>" class="hover:text-royal">Courses</a>
    <span class="mx-2">/</span>
    <a href="<?= e(base_url('course.php?slug=' . $subCourse['course_slug'])) ?>" class="hover:text-royal"><?= e($subCourse['course_name']) ?></a>
    <span class="mx-2">/</span>
    <span class="text-royal font-medium"><?= e($subCourse['name']) ?></span>
  </nav>

  <header class="bg-white border border-slate-200 rounded-lg p-6 lg:p-10 mb-8">
    <span class="text-xs font-semibold uppercase text-gold"><?= e($subCourse['course_name']) ?></span>
    <h1 class="font-serif text-3xl lg:text-4xl font-bold text-royal mt-2"><?= e($subCourse['name']) ?></h1>
    <?php if (!empty($subCourse['name_te'])): ?>
      <p class="font-telugu text-lg text-gold mt-2"><?= e($subCourse['name_te']) ?></p>
    <?php endif; ?>
    <?php if (!empty($subCourse['description'])): ?>
      <p class="text-slate-600 mt-4 max-w-3xl"><?= e($subCourse['description']) ?></p>
    <?php endif; ?>
  </header>

  <?php
    $tierTeStrip = public_five_tier_labels_te();
  ?>
  <div class="mb-8 rounded-lg border border-slate-200 bg-white px-4 py-4 lg:px-6">
    <p class="text-[10px] font-semibold uppercase text-slate-400 mb-2">పరీక్ష నమూనా · 5 tiers</p>
    <div class="flex flex-wrap gap-1">
      <?php foreach (public_five_tier_ordered_keys() as $tk): ?>
      <span class="font-telugu text-[10px] px-2 py-0.5 rounded-full bg-gold-pale/70 text-royal border border-gold/20"><?= e($tierTeStrip[$tk]) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($plans): ?>
  <section class="mb-10">
    <h2 class="font-semibold text-xl text-royal mb-4">Subscription pricing</h2>
    <p class="text-sm text-slate-600 mb-4">Transparent plans — choose what fits your preparation timeline.</p>
    <div class="grid sm:grid-cols-3 gap-4 lg:gap-6">
      <?php foreach ($plans as $pl):
          $meta = $planUi[$pl['plan_code']] ?? ['short' => $pl['label'], 'hint' => ''];
      ?>
      <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm flex flex-col">
        <p class="text-xs font-semibold uppercase tracking-wide text-gold"><?= e($meta['short']) ?></p>
        <p class="font-medium text-slate-800 mt-2"><?= e($pl['label']) ?></p>
        <?php if ($meta['hint']): ?>
          <p class="text-xs text-slate-500 mt-1"><?= e($meta['hint']) ?></p>
        <?php endif; ?>
        <p class="text-3xl font-bold text-royal mt-4">₹<?= number_format((float) $pl['price_inr'], 0) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$user): ?>
      <p class="text-sm text-slate-600 mt-4"><a href="<?= e(base_url('login.php')) ?>" class="font-semibold text-royal hover:underline">Login</a> to enrol after payment gateway is connected.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <section>
    <h2 class="font-semibold text-xl text-royal mb-4">Subjects in this programme</h2>
    <?php if (!$subjects): ?>
      <p class="text-sm text-slate-500">Map subjects from the Subject Manager.</p>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 gap-4">
      <?php foreach ($subjects as $sub): ?>
      <a href="<?= e(base_url('subject.php?course=' . $subCourse['course_slug'] . '&sub=' . $subCourse['slug'] . '&subject=' . $sub['slug'])) ?>"
        class="block bg-white border border-slate-200 rounded-lg p-5 hover:border-gold/50 hover:shadow-md transition-all group">
        <h3 class="font-semibold text-royal group-hover:text-royal-light"><?= e($sub['name']) ?></h3>
        <?php if (!empty($sub['name_te'])): ?>
          <p class="font-telugu text-sm text-gold mt-1"><?= e($sub['name_te']) ?></p>
        <?php endif; ?>
        <?php if (!empty($sub['marks_allocated'])): ?>
          <p class="text-xs text-slate-500 mt-2"><?= (int) $sub['marks_allocated'] ?> marks</p>
        <?php endif; ?>
        <p class="text-xs text-gold mt-3 font-medium">Topics & material →</p>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
