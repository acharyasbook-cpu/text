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
require_once dirname(__DIR__, 2) . '/MediaAvatarHelper.php';

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

<?php require __DIR__ . '/partials/schedule_daily_workspace.php'; ?>
<?php if (empty($scheduleDaily) || empty($scheduleDaily['rows'])): ?>
<?php require __DIR__ . '/partials/programme_term_matrix.php'; ?>
<?php endif; ?>

<?php
$plans = $plans;
$hasAccess = $hasAccess;
$user = $user;
$checkoutReturn = $checkoutReturn;
require __DIR__ . '/partials/pricing_plans.php';
?>

<link rel="stylesheet" href="<?= e(base_url('assets/css/subject-workspace.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/subject-workspace.css') ?>" />
<div class="mb-4">
  <a href="<?= e(base_url('index.php')) ?>" class="subject-workspace-back font-telugu inline-flex mb-2">← వెనుకకు / మెయిన్ కోర్సులు</a>
</div>
<section class="mb-4">
  <h2 class="font-telugu text-xl font-bold text-slate-900 mb-4">ఈ ప్రోగ్రామ్‌లోని విషయాలు</h2>
  <?php if (!$subjects): ?>
  <p class="font-telugu text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-white">విషయాలు Admin లో మ్యాప్ చేసిన తర్వాత ఇక్కడ కనిపిస్తాయి.</p>
  <?php else: ?>
  <div class="grid sm:grid-cols-2 gap-4 lg:gap-5">
    <?php foreach ($subjects as $sub):
        $href = base_url(
            'subject.php?course=' . rawurlencode($courseSlug)
            . '&sub=' . rawurlencode($subSlug)
            . '&subject=' . rawurlencode((string) $sub['slug'])
        );
        $subTitleTe = trim((string) ($sub['name_te'] ?? ''));
        $label = MediaAvatarHelper::displayLabel($sub);
        $imagePath = trim((string) ($sub['image_path'] ?? ''));
        $shape = 'card';
        $slotClass = 'programme-subject-media';
        $avatarMode = 'full';
        $alt = (string) ($sub['name'] ?? '');
    ?>
    <a href="<?= e($href) ?>" class="programme-subject-card group block">
      <?php require __DIR__ . '/partials/media_slot.php'; ?>
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
