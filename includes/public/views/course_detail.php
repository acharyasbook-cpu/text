<?php
/** @var array<string,mixed> $course @var bool $fourTier @var list<array> $subCourseBlocks @var list<array> $subjectsGrouped @var array<string,list> $testsByType @var list<array> $packages @var ?array $user */
$courseSlug = (string) $course['slug'];
$tierTe = public_five_tier_labels_te();
$tierEn = [
    'topic' => 'Topic-wise tests',
    'division' => 'Division tests',
    'revision' => 'Revision tests',
    'grand' => 'Grand tests',
    'model' => 'Model papers',
];
?>
<nav class="text-sm text-slate-500 mb-6" aria-label="Breadcrumb">
  <a href="<?= e(base_url('index.php')) ?>" class="hover:text-royal">Home</a>
  <span class="mx-2">/</span>
  <a href="<?= e(base_url('learn.php?course=' . rawurlencode($courseSlug))) ?>" class="hover:text-royal"><?= e($course['name']) ?></a>
  <span class="mx-2">/</span>
  <span class="text-royal font-medium">Overview</span>
</nav>

<header class="border border-[#E3E6F0] rounded-xl p-6 lg:p-10 mb-8 bg-white">
  <?php if (!empty($course['region'])): ?>
  <span class="text-[10px] font-bold uppercase tracking-widest text-gold"><?= e($course['region']) ?></span>
  <?php endif; ?>
  <h1 class="font-serif text-3xl lg:text-4xl font-bold text-slate-900 mt-2"><?= e($course['name']) ?></h1>
  <?php if (!empty($course['name_te'])): ?>
  <p class="font-telugu text-lg text-gold mt-2 font-semibold"><?= e($course['name_te']) ?></p>
  <?php endif; ?>
  <?php if (!empty($course['description'])): ?>
  <p class="text-slate-600 mt-4 max-w-3xl text-sm leading-relaxed"><?= e($course['description']) ?></p>
  <?php endif; ?>
</header>

<div class="grid lg:grid-cols-3 gap-8">
  <div class="lg:col-span-2 space-y-8">
    <section>
      <h2 class="font-telugu text-xl font-bold text-slate-900 mb-4"><?= $fourTier ? 'ప్రోగ్రామ్‌లు & పేపర్లు' : 'విషయాలు & అధ్యయన మార్గం' ?></h2>
      <?php if ($fourTier): ?>
        <?php if (!$subCourseBlocks): ?>
        <p class="text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-cream">సబ్-కోర్సులు Admin లో జోడించిన తర్వాత ఇక్కడ కనిపిస్తాయి.</p>
        <?php else: ?>
        <div class="grid sm:grid-cols-2 gap-5" id="courseSubCourseGrid" data-media-scope="sub_courses" data-course-id="<?= (int) $course['id'] ?>">
          <?php foreach ($subCourseBlocks as $block):
              $scrow = $block['sub_course'];
              $img = !empty($scrow['image_path']) ? public_media_url((string) $scrow['image_path']) : '';
              $href = public_sub_course_workspace_url($courseSlug, (string) $scrow['slug']);
          ?>
          <article class="classical-card group" data-entity-id="<?= (int) $scrow['id'] ?>" data-image-path="<?= e((string) ($scrow['image_path'] ?? '')) ?>">
            <a href="<?= e($href) ?>" class="block">
              <div class="classical-card-media">
                <?php if ($img !== ''): ?>
                <img src="<?= e($img) ?>" alt="" class="w-full h-full object-cover course-cover-img" loading="lazy" />
                <?php else: ?>
                <div class="text-center p-6 text-slate-400 text-xs">No image</div>
                <?php endif; ?>
              </div>
              <div class="p-5">
                <h3 class="font-serif text-xl font-bold text-slate-900 group-hover:text-royal"><?= e($scrow['name']) ?></h3>
                <?php if (!empty($scrow['name_te'])): ?>
                <p class="font-telugu text-sm text-gold mt-1"><?= e($scrow['name_te']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-slate-500 mt-3"><?= count($block['subjects']) ?> subject(s)</p>
                <span class="classical-btn-primary w-full mt-4 py-2.5 text-sm">View syllabus →</span>
              </div>
            </a>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php elseif (!$subjectsGrouped): ?>
      <p class="text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-cream">విషయాలు కాన్ఫిగర్ అయిన తర్వాత ఇక్కడ కనిపిస్తాయి.</p>
      <?php else: ?>
        <?php foreach ($subjectsGrouped as $block): ?>
        <div class="mb-8">
          <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3"><?= e($block['label']) ?></h3>
          <div class="grid sm:grid-cols-2 gap-4">
            <?php foreach ($block['subjects'] as $sub): ?>
            <a href="<?= e(public_subject_workspace_url($courseSlug, null, (string) $sub['slug'])) ?>"
               class="classical-card block p-5 hover:no-underline group">
              <h4 class="font-semibold text-royal group-hover:text-royal-light"><?= e($sub['name']) ?></h4>
              <?php if (!empty($sub['name_te'])): ?>
              <p class="font-telugu text-sm text-gold mt-1"><?= e($sub['name_te']) ?></p>
              <?php endif; ?>
              <?php if (!empty($sub['marks_allocated'])): ?>
              <p class="text-xs text-slate-500 mt-2"><?= (int) $sub['marks_allocated'] ?> marks</p>
              <?php endif; ?>
              <p class="text-xs text-gold mt-3 font-semibold">Topics & materials →</p>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section>
      <h2 class="font-telugu text-xl font-bold text-slate-900 mb-2">ఆన్‌లైన్ పరీక్షలు</h2>
      <p class="font-telugu text-xs text-slate-600 mb-4"><?= e(implode(' · ', $tierTe)) ?></p>
      <?php foreach (public_five_tier_ordered_keys() as $type):
          if (empty($testsByType[$type])) {
              continue;
          }
      ?>
      <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-3">
          <span class="font-telugu"><?= e($tierTe[$type] ?? $type) ?></span>
          <span class="text-slate-400 font-normal text-xs uppercase ml-2"><?= e($tierEn[$type] ?? '') ?></span>
        </h3>
        <div class="space-y-2">
          <?php foreach ($testsByType[$type] as $test): ?>
          <div class="flex flex-wrap items-center justify-between gap-3 border border-[#E3E6F0] rounded-xl px-4 py-3 bg-white">
            <div>
              <p class="font-medium text-slate-800"><?= e($test['title']) ?></p>
              <?php if (!empty($test['division_label'])): ?>
              <p class="text-xs text-slate-500"><?= e($test['division_label']) ?></p>
              <?php endif; ?>
              <p class="text-xs text-slate-500 mt-1"><?= (int) $test['duration_mins'] ?> min · <?= (int) $test['total_questions'] ?> Q</p>
            </div>
            <a href="<?= e(base_url('exam.php?course=' . rawurlencode($courseSlug) . '&test=' . rawurlencode((string) $test['slug']))) ?>"
               class="classical-btn-primary text-sm px-4 py-2 shrink-0">Start Test</a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </section>
  </div>

  <aside class="space-y-6">
    <section class="border border-gold/30 bg-gold-pale/40 rounded-xl p-6">
      <h2 class="font-semibold text-royal"><?= $fourTier ? 'Other plans' : 'Sub-course Packages' ?></h2>
      <p class="text-xs text-slate-600 mt-2"><?= $fourTier ? 'ప్రోగ్రామ్ కార్డ్ ఓపెన్ చేసి pricing చూడండి.' : 'విషయం లేదా డివిజన్ ప్యాకేజీలు.' ?></p>
      <ul class="mt-4 space-y-3">
        <?php foreach ($packages as $pkg): ?>
        <li class="bg-white rounded-lg border border-[#E3E6F0] p-4">
          <p class="font-medium text-sm text-royal"><?= e($pkg['name']) ?></p>
          <?php if (!empty($pkg['name_te'])): ?>
          <p class="font-telugu text-xs text-gold"><?= e($pkg['name_te']) ?></p>
          <?php endif; ?>
          <p class="text-lg font-bold text-royal mt-2">₹<?= number_format((float) $pkg['price_inr'], 0) ?></p>
          <p class="text-xs text-slate-500 capitalize mt-1"><?= e(str_replace('_', ' ', (string) $pkg['package_type'])) ?></p>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if (!$user): ?>
      <a href="<?= e(base_url('login.php')) ?>" class="mt-4 block text-center text-sm font-semibold text-royal hover:underline">Login to access</a>
      <?php endif; ?>
    </section>
  </aside>
</div>