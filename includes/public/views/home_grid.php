<?php
/** @var list<array<string,mixed>> $catalog */
require_once dirname(__DIR__, 2) . '/MediaAvatarHelper.php';
?>
<section class="home-course-section">
  <div class="classical-hero px-6 sm:px-8 py-8 sm:py-10 mb-8 rounded-xl border border-[#E3E6F0] bg-gradient-to-br from-royal to-royal-light text-white shadow-sm">
    <p class="text-xs font-bold uppercase tracking-widest text-amber-300 font-sans">ACHARYA BOOK · FREE PREVIEW</p>
    <h1 class="font-telugu text-2xl sm:text-3xl font-bold mt-3 leading-snug text-white">మీ విజయం, మా లక్ష్యం. ఆచార్యతో సిద్ధమవ్వండి.</h1>
    <p class="font-telugu text-sm text-blue-100/95 mt-3 max-w-2xl leading-relaxed">
      అడ్మిన్ ప్యానెల్ నుండి అప్‌లోడ్ చేసిన కోర్సు చిత్రాలు — ఇక్కడ ఆటోమేటిక్‌గా కనిపిస్తాయి.
    </p>
  </div>

  <header class="mb-8">
    <p class="text-[10px] font-bold uppercase tracking-widest text-royal font-sans">Courses</p>
    <h2 class="font-telugu text-2xl sm:text-3xl font-bold text-slate-900 mt-1">అన్ని కోర్సులు</h2>
    <p class="font-telugu text-sm text-slate-600 mt-1">డేటాబేస్ నుండి డైనమిక్ లోడ్ — AP DSC, TSPSC, TET, CTET మరియు కొత్త ప్యాకేజీలు</p>
  </header>

  <?php if (empty($catalog)): ?>
  <div class="home-course-empty font-telugu text-center py-16 px-6 bg-white border border-[#E3E6F0] rounded-xl">
    <p class="text-lg font-semibold text-slate-800">ప్రస్తుతం సక్రియ కోర్సులు లేవు</p>
    <p class="text-sm text-slate-500 mt-2">అడ్మిన్ కంటెంట్ మేనేజర్ నుండి కోర్సులను జోడించండి.</p>
  </div>
  <?php else: ?>

  <div class="home-course-grid grid sm:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-6" id="homeCourseGrid" data-media-scope="courses">
    <?php foreach ($catalog as $course):
        $courseId = (int) ($course['id'] ?? 0);
        $imgPath = trim((string) ($course['image_path'] ?? ''));
        $imgUrl = MediaAvatarHelper::resolvedUrl($imgPath !== '' ? $imgPath : null);
        $imgVer = $imgUrl !== '' ? MediaAvatarHelper::cacheVersion($imgPath) : 0;
        $learnUrl = base_url('learn.php?course=' . rawurlencode((string) ($course['slug'] ?? '')));
        $titleEn = (string) ($course['name'] ?? 'Course');
        $titleTe = trim((string) ($course['name_te'] ?? ''));
        $displayTitle = $titleTe !== '' ? $titleTe : $titleEn;
        $programmeCount = count($course['programmes'] ?? []);
    ?>
    <article class="home-course-card classical-card group"
             data-entity-id="<?= $courseId ?>"
             data-image-path="<?= e($imgPath) ?>">
      <a href="<?= e($learnUrl) ?>" class="block h-full">
        <div class="home-course-media classical-card-media">
          <?php if ($imgUrl !== ''): ?>
          <img src="<?= e($imgUrl) ?><?= $imgVer > 0 ? '?v=' . $imgVer : '' ?>"
               alt="<?= e($titleEn) ?>"
               loading="lazy"
               class="course-cover-img home-course-cover-img"
               width="400"
               height="200" />
          <?php else: ?>
          <div class="home-course-placeholder" aria-hidden="true">
            <span class="font-telugu font-bold text-lg sm:text-xl text-slate-700 text-center leading-snug px-4">
              <?= e($displayTitle) ?>
            </span>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-5 bg-white">
          <h3 class="font-serif text-xl font-bold text-slate-900 group-hover:text-royal transition-colors">
            <?= e($titleEn) ?>
          </h3>
          <?php if ($titleTe !== ''): ?>
          <p class="font-telugu text-sm text-gold font-semibold mt-1 leading-relaxed"><?= e($titleTe) ?></p>
          <?php endif; ?>
          <?php if (!empty($course['description'])): ?>
          <p class="font-telugu text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed"><?= e((string) $course['description']) ?></p>
          <?php endif; ?>
          <p class="font-telugu text-xs text-slate-500 mt-3">
            <?= $programmeCount ?> ప్రోగ్రామ్<?= $programmeCount === 1 ? '' : 'లు' ?>
          </p>
          <span class="classical-btn-primary w-full mt-4 py-2.5 text-sm font-telugu">కొనసాగించు →</span>
        </div>
      </a>
    </article>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</section>
