<?php
/** @var list<array<string,mixed>> $catalog */
/** @var array<string,mixed> $header */
require_once dirname(__DIR__, 2) . '/MediaAvatarHelper.php';
require_once dirname(__DIR__, 2) . '/HomeBannerSettings.php';
require_once dirname(__DIR__, 2) . '/public_site_helpers.php';
$user = current_user();
$banners = $header['home_banners'] ?? HomeBannerSettings::all();
$hero = $banners['hero'] ?? [];
$ca = $banners['ca'] ?? [];
$caExamUrl = base_url(ca_exam_environment_script());
$caGatewayUrl = $user
    ? $caExamUrl
    : base_url('login.php?return=' . rawurlencode(ca_exam_environment_script()));
$heroBg = (string) ($hero['bg_color'] ?? '');
$heroImg = $hero['bg_image_url'] ?? null;
$caBg = (string) ($ca['bg_color'] ?? '');
$caImg = $ca['bg_image_url'] ?? null;
$heroLine1 = trim((string) ($hero['line1'] ?? '')) !== ''
    ? (string) $hero['line1']
    : 'మీ విజయం, మా లక్ష్యం. ఆచార్యతో సిద్ధమవ్వండి.';
$heroEyebrow = trim((string) ($hero['eyebrow'] ?? '')) !== ''
    ? (string) $hero['eyebrow']
    : 'ACHARYASBOOK.COM';
$heroLine2 = trim((string) ($hero['line2'] ?? '')) !== ''
    ? (string) $hero['line2']
    : 'ప్రతిష్టాత్మక ఆన్‌లైన్ లెర్నింగ్ — పరీక్షల విజయానికి సరైన మార్గదర్శకత్వం.';
$caLine2 = trim((string) ($ca['line2'] ?? '')) !== '' ? (string) $ca['line2'] : 'డైలీ కరెంట్ అఫైర్స్';
$caLine3 = trim((string) ($ca['line3'] ?? '')) !== '' ? (string) $ca['line3'] : 'నేటి పరీక్ష';
?>
<section class="home-course-section">
  <div class="home-brand-showcase mb-8">
    <div class="home-brand-showcase__grid">
    <article class="home-brand-hero classical-hero home-hero-banner flex flex-col justify-center relative overflow-hidden"
         style="background: <?= e($heroBg) ?>">
      <?php if ($heroImg): ?>
      <div class="home-hero-banner__bg-image absolute inset-0 bg-cover bg-center opacity-20" style="background-image:url('<?= e($heroImg) ?>')"></div>
      <?php endif; ?>
      <div class="home-brand-hero__inner relative z-10">
        <p class="home-brand-hero__eyebrow"><?= e($heroEyebrow) ?></p>
        <h1 class="font-telugu home-brand-hero__title" style="font-size:<?= e((string) ($hero['line1_size'] ?? '1.875rem')) ?>">
          <?= e($heroLine1) ?>
        </h1>
        <p class="font-telugu home-brand-hero__subtitle" style="font-size:<?= e((string) ($hero['line2_size'] ?? '0.9375rem')) ?>">
          <?= e($heroLine2) ?>
        </p>
      </div>
    </article>

    <aside class="home-brand-ca ca-home-hub ca-home-hub--gold flex flex-col justify-center relative overflow-hidden"
           style="background: <?= e($caBg) ?>">
      <?php if ($caImg): ?>
      <div class="ca-home-hub__bg-image" style="background-image: url('<?= e($caImg) ?>')"></div>
      <?php endif; ?>
      <div class="ca-home-hub__inner relative z-10">
        <p class="ca-home-hub__eyebrow font-telugu" style="font-size:<?= e((string) ($ca['line1_size'] ?? '0.65rem')) ?>">
          <?= e((string) ($ca['line1'] ?? 'డైలీ టెస్ట్')) ?>
        </p>
        <h2 class="ca-home-hub__title font-telugu mt-2 font-bold" style="font-size:<?= e((string) ($ca['line2_size'] ?? '1.35rem')) ?>">
          <?= e($caLine2) ?>
        </h2>
        <p class="ca-home-hub__subtitle font-telugu mt-3" style="font-size:<?= e((string) ($ca['line3_size'] ?? '0.8rem')) ?>">
          <?= e($caLine3) ?>
        </p>
        <div class="mt-6">
          <a href="<?= e($caGatewayUrl) ?>"
             id="caGoldExamWriteBtn"
             class="ca-home-hub__cta font-telugu">ఎగ్జామ్ రాయండి →</a>
        </div>
      </div>
    </aside>
    </div>
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
