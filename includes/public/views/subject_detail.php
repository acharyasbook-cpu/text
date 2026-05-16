<?php
/**
 * @var array<string,mixed> $subject
 * @var list<array> $topicsWorkspace
 * @var bool $programmeHasAccess
 * @var ?array $user
 * @var string $bannerPath
 * @var string $courseSlug
 */
$flashSuccess = flash('success');
$flashError = flash('error');
?>
<nav class="text-sm text-slate-500 mb-6 font-telugu" aria-label="Breadcrumb">
  <a href="<?= e(base_url('index.php')) ?>" class="hover:text-royal">హోమ్</a>
  <span class="mx-2">/</span>
  <a href="<?= e(base_url('learn.php?course=' . rawurlencode((string) $subject['course_slug']))) ?>" class="hover:text-royal"><?= e($subject['course_name']) ?></a>
  <?php if (!empty($subject['sub_course_slug'])): ?>
  <span class="mx-2">/</span>
  <a href="<?= e(public_sub_course_workspace_url((string) $subject['course_slug'], (string) $subject['sub_course_slug'])) ?>" class="hover:text-royal"><?= e($subject['sub_course_name'] ?? '') ?></a>
  <?php endif; ?>
  <span class="mx-2">/</span>
  <span class="text-royal font-semibold"><?= e($subject['name']) ?></span>
</nav>

<?php if ($flashSuccess): ?>
<p class="mb-4 font-telugu text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3"><?= e($flashSuccess) ?></p>
<?php endif; ?>
<?php if ($flashError): ?>
<p class="mb-4 font-telugu text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-4 py-3"><?= e($flashError) ?></p>
<?php endif; ?>

<?php
$titleEn = (string) ($subject['name'] ?? '');
$titleTe = trim((string) ($subject['name_te'] ?? ''));
$eyebrow = trim((string) ($subject['sub_course_name'] ?? $subject['course_name'] ?? ''));
$description = !empty($subject['marks_allocated'])
    ? 'Marks weight: ' . (int) $subject['marks_allocated']
    : '';
$bannerAlt = $titleEn;
$bannerPath = $bannerPath ?? trim((string) ($subject['image_path'] ?? ''));
require __DIR__ . '/partials/programme_hero.php';
?>

<?php require __DIR__ . '/partials/subject_workspace_hub.php'; ?>
