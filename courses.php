<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

$courseRepo = new CourseRepository();
$catalog = $courseRepo->catalogForPublicSite();

$pageTitle = 'All Courses | Acharya Books';
$activeNav = 'courses';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';

$tierTe = public_five_tier_labels_te();
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <p class="text-sm font-semibold uppercase tracking-widest text-gold">Course Catalog</p>
    <h1 class="font-serif text-3xl lg:text-4xl font-bold text-royal mt-2">మా కోర్సులు</h1>
    <p class="font-telugu text-slate-600 mt-3">డైనమిక్ క్యాటలాగ్ — అడ్మిన్‌లో Live అయిన కోర్సులు &amp; ప్రోగ్రామ్‌లు మాత్రమే</p>
  </div>

  <div class="grid lg:grid-cols-2 gap-8">
    <?php foreach ($catalog as $course): ?>
    <article class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-shadow flex flex-col" data-course-id="<?= (int) $course['id'] ?>">
      <div class="h-1.5 bg-gradient-to-r from-royal to-gold"></div>
      <div class="p-6 lg:p-8 flex flex-col flex-1">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <span class="text-xs font-semibold uppercase tracking-wider text-gold"><?= e($course['region'] ?? '') ?></span>
            <h2 class="font-serif text-2xl font-bold text-royal mt-2"><?= e($course['name']) ?></h2>
            <?php if (!empty($course['name_te'])): ?>
              <p class="font-telugu text-sm text-gold mt-1"><?= e($course['name_te']) ?></p>
            <?php endif; ?>
          </div>
          <a href="<?= e(public_course_overview_url($course['slug'])) ?>"
             class="shrink-0 px-4 py-2 text-sm font-semibold text-white bg-royal rounded hover:bg-royal-light whitespace-nowrap">
            Overview
          </a>
        </div>
        <p class="text-sm text-slate-600 mt-4 leading-relaxed"><?= e($course['description'] ?? '') ?></p>

        <div class="mt-5 pt-4 border-t border-slate-100">
          <p class="text-[10px] font-semibold uppercase text-slate-400 mb-2">పరీక్ష నమూనా · 5 tiers</p>
          <div class="flex flex-wrap gap-1">
            <?php foreach (public_five_tier_ordered_keys() as $tk): ?>
            <span class="font-telugu text-[10px] px-2 py-0.5 rounded-full bg-gold-pale/70 text-royal border border-gold/20"><?= e($tierTe[$tk]) ?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if (!empty($course['programmes'])): ?>
        <h3 class="text-xs font-semibold uppercase text-slate-500 mt-6 mb-3">Programmes</h3>
        <div class="space-y-4 flex-1">
          <?php foreach ($course['programmes'] as $block): ?>
            <?php $sc = $block['sub_course']; ?>
            <div class="rounded-lg border border-slate-100 bg-slate-50/80 p-4" data-sub-course-id="<?= (int) $sc['id'] ?>">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <p class="font-semibold text-royal"><?= e($sc['name']) ?></p>
                  <?php if (!empty($sc['name_te'])): ?>
                  <p class="font-telugu text-xs text-gold mt-1"><?= e($sc['name_te']) ?></p>
                  <?php endif; ?>
                </div>
                <a href="<?= e(public_sub_course_workspace_url($course['slug'], $sc['slug'])) ?>" class="text-xs font-semibold text-white bg-royal px-3 py-1.5 rounded hover:bg-royal-light shrink-0">Open programme</a>
              </div>
              <?php if (!empty($block['subjects'])): ?>
              <div class="mt-3 flex flex-wrap gap-2">
                <?php foreach ($block['subjects'] as $sub): ?>
                <a href="<?= e(public_subject_workspace_url($course['slug'], $sc['slug'], $sub['slug'])) ?>"
                   data-subject-id="<?= (int) $sub['id'] ?>"
                   class="px-3 py-1.5 text-sm bg-white border border-slate-200 rounded hover:border-gold/50 hover:bg-gold-pale/40 transition-colors">
                  <span class="font-medium text-royal"><?= e($sub['name']) ?></span>
                  <?php if (!empty($sub['name_te'])): ?>
                    <span class="font-telugu text-xs text-gold block"><?= e($sub['name_te']) ?></span>
                  <?php endif; ?>
                </a>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php elseif (!empty($course['legacy_subjects'])): ?>
        <h3 class="text-xs font-semibold uppercase text-slate-500 mt-6 mb-3">Subjects</h3>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($course['legacy_subjects'] as $sub): ?>
          <a href="<?= e(public_subject_workspace_url($course['slug'], null, $sub['slug'])) ?>"
             data-subject-id="<?= (int) $sub['id'] ?>"
             class="px-3 py-1.5 text-sm bg-slate-50 border border-slate-200 rounded hover:border-gold/50 hover:bg-gold-pale/40 transition-colors font-telugu">
            <?= e($sub['name_te'] ?: $sub['name']) ?>
          </a>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="mt-6 text-sm text-slate-500">No live programmes yet — publish from Admin.</p>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
