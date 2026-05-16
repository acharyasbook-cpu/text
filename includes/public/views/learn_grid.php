<?php
/** @var array<string,mixed> $course @var list<array<string,mixed>> $subCourses */
$courseSlug = (string) $course['slug'];
?>
<section>
  <header class="mb-8">
    <h1 class="font-telugu text-2xl sm:text-3xl font-bold text-slate-900">సబ్-కోర్సు ఎంచుకోండి</h1>
    <p class="font-telugu text-sm text-slate-600 mt-2">మీ పోస్ట్ / పేపర్ ఎంచుకుని → విషయం → అధ్యాయం → మైండ్ మ్యాప్ / టెస్ట్</p>
    <p class="text-xs text-slate-500 mt-1"><?= e($course['name']) ?><?= !empty($course['name_te']) ? ' · ' . e($course['name_te']) : '' ?></p>
  </header>

  <?php if (!$subCourses): ?>
  <p class="text-sm text-slate-500 bg-cream border border-[#E3E6F0] rounded-xl p-6">ఈ కోర్స్‌కు సబ్-కోర్సులు ఇంకా జోడించబడలేదు. Admin Content Manager నుండి జోడించండి.</p>
  <?php else: ?>
  <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-6" id="learnSubCourseGrid" data-media-scope="sub_courses" data-course-id="<?= (int) $course['id'] ?>">
    <?php foreach ($subCourses as $sc):
        $img = !empty($sc['image_path']) ? public_media_url((string) $sc['image_path']) : '';
        $href = public_sub_course_workspace_url($courseSlug, $sc['slug']);
    ?>
    <article class="classical-card group" data-entity-id="<?= (int) $sc['id'] ?>" data-image-path="<?= e((string) ($sc['image_path'] ?? '')) ?>">
      <a href="<?= e($href) ?>" class="block">
        <div class="classical-card-media">
          <?php if ($img !== ''): ?>
          <img src="<?= e($img) ?>" alt="<?= e($sc['name']) ?>" loading="lazy" class="course-cover-img w-full h-full object-cover" />
          <?php else: ?>
          <div class="text-center p-6 text-slate-400">
            <svg class="w-12 h-12 mx-auto opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <p class="text-xs mt-2 font-medium">No image</p>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-5">
          <h3 class="font-semibold text-lg text-slate-900"><?= e($sc['name']) ?></h3>
          <?php if (!empty($sc['name_te'])): ?>
          <p class="font-telugu text-sm text-gold mt-1"><?= e($sc['name_te']) ?></p>
          <?php endif; ?>
          <span class="classical-btn-primary w-full mt-4 py-2.5 text-sm">Continue →</span>
        </div>
      </a>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
