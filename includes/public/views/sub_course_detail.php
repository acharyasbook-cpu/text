<?php
/**
 * @var array<string,mixed> $subCourse
 * @var ?array<string,mixed> $course
 * @var list<array<string,mixed>> $subjects
 * @var list<array<string,mixed>> $plans
 * @var array<string,mixed> $termMatrix
 * @var bool $hasAccess
 * @var ?array $user
 * @var array<string,string> $tierTe
 * @var string $checkoutReturn
 * @var string $courseSlug
 */
$courseSlug = (string) ($subCourse['course_slug'] ?? '');
$subSlug = (string) ($subCourse['slug'] ?? '');
$bannerPath = trim((string) ($subCourse['image_path'] ?? ''));

$flashSuccess = flash('success');
$flashError = flash('error');

$tierStrip = null;
$enrolAnchorUrl = '#enrol';
?>
<nav class="text-sm text-slate-500 mb-6 font-telugu" aria-label="Breadcrumb">
  <a href="<?= e(base_url('index.php')) ?>" class="hover:text-royal">హోమ్</a>
  <span class="mx-2">/</span>
  <a href="<?= e(base_url('learn.php?course=' . rawurlencode($courseSlug))) ?>" class="hover:text-royal"><?= e($subCourse['course_name'] ?? '') ?></a>
  <span class="mx-2">/</span>
  <span class="text-royal font-semibold"><?= e($subCourse['name']) ?></span>
</nav>

<?php if ($flashSuccess): ?>
<p class="mb-4 font-telugu text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3"><?= e($flashSuccess) ?></p>
<?php endif; ?>
<?php if ($flashError): ?>
<p class="mb-4 font-telugu text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-4 py-3"><?= e($flashError) ?></p>
<?php endif; ?>

<?php
$titleEn = (string) ($subCourse['name'] ?? '');
$titleTe = trim((string) ($subCourse['name_te'] ?? ''));
$eyebrow = (string) ($subCourse['course_name'] ?? '');
$description = trim((string) ($subCourse['description'] ?? ''));
$bannerAlt = $titleEn;
require __DIR__ . '/partials/programme_hero.php';
?>

<?php require __DIR__ . '/partials/programme_term_matrix.php'; ?>

<?php
$plans = $plans;
$hasAccess = $hasAccess;
$user = $user;
$checkoutReturn = $checkoutReturn;
require __DIR__ . '/partials/pricing_plans.php';
?>

<section class="mb-4">
  <h2 class="font-telugu text-xl font-bold text-slate-900 mb-4">ఈ ప్రోగ్రామ్‌లోని విషయాలు</h2>
  <?php if (!$subjects): ?>
  <p class="font-telugu text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-white">విషయాలు Admin లో మ్యాప్ చేసిన తర్వాత ఇక్కడ కనిపిస్తాయి.</p>
  <?php else: ?>
  <div class="grid sm:grid-cols-2 gap-4 lg:gap-5">
    <?php foreach ($subjects as $sub):
        $subImgPath = trim((string) ($sub['image_path'] ?? ''));
        $subImg = $subImgPath !== '' ? acharya_media_url($subImgPath) : '';
        $subVer = $subImgPath !== '' ? public_media_cache_version($subImgPath) : 0;
        $href = base_url(
            'subject.php?course=' . rawurlencode($courseSlug)
            . '&sub=' . rawurlencode($subSlug)
            . '&subject=' . rawurlencode((string) $sub['slug'])
        );
        $subTitleTe = trim((string) ($sub['name_te'] ?? ''));
        $subDisplay = $subTitleTe !== '' ? $subTitleTe : (string) ($sub['name'] ?? '');
    ?>
    <a href="<?= e($href) ?>" class="programme-subject-card group block">
      <div class="programme-subject-media">
        <?php if ($subImg !== ''): ?>
        <img src="<?= e($subImg) ?><?= $subVer > 0 ? '?v=' . $subVer : '' ?>" alt="" class="w-full h-full object-cover" loading="lazy" />
        <?php else: ?>
        <div class="programme-subject-placeholder font-telugu font-bold text-slate-600 text-center px-4"><?= e($subDisplay) ?></div>
        <?php endif; ?>
      </div>
      <div class="p-5">
        <h3 class="font-semibold text-lg text-royal group-hover:text-royal-light"><?= e((string) ($sub['name'] ?? '')) ?></h3>
        <?php if ($subTitleTe !== ''): ?>
        <p class="font-telugu text-sm text-gold mt-1 font-semibold"><?= e($subTitleTe) ?></p>
        <?php endif; ?>
        <p class="font-telugu text-xs text-gold mt-3 font-semibold">నోట్స్ & ఎగ్జామ్ →</p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
