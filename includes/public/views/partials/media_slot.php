<?php
/**
 * Image slot with Telugu text avatar when no valid file on disk.
 *
 * @var string $label
 * @var string $imagePath stored relative path (optional)
 * @var string $slotClass extra classes on outer wrapper
 * @var string $shape circle|rounded|banner|card
 * @var string $imgClass classes on img when present
 * @var string $alt
 * @var string $avatarMode initials|full
 */
require_once dirname(__DIR__, 3) . '/MediaAvatarHelper.php';

$label = trim((string) ($label ?? ''));
$imagePath = trim((string) ($imagePath ?? ''));
$slotClass = trim((string) ($slotClass ?? ''));
$shape = (string) ($shape ?? 'card');
$imgClass = trim((string) ($imgClass ?? 'w-full h-full object-cover'));
$alt = (string) ($alt ?? $label);
$avatarMode = (string) ($avatarMode ?? 'initials');

$imgUrl = MediaAvatarHelper::resolvedUrl($imagePath !== '' ? $imagePath : null);
$imgVer = $imgUrl !== '' ? MediaAvatarHelper::cacheVersion($imagePath) : 0;
$initials = MediaAvatarHelper::initials($label);
$avatarText = $avatarMode === 'full' ? ($label !== '' ? $label : '—') : $initials;
$avatarTextClass = $avatarMode === 'full' ? 'media-slot-avatar-text--full' : '';
$palette = MediaAvatarHelper::palette($label !== '' ? $label : 'subject');
$shapeClass = match ($shape) {
    'circle' => 'media-slot--circle',
    'rounded' => 'media-slot--rounded',
    'banner' => 'media-slot--banner',
    default => 'media-slot--card',
};
?>
<div class="media-slot <?= e($shapeClass) ?> <?= e($slotClass) ?>" data-image-path="<?= e($imagePath) ?>">
  <?php if ($imgUrl !== ''): ?>
  <img src="<?= e($imgUrl) ?><?= $imgVer > 0 ? '?v=' . $imgVer : '' ?>"
       alt="<?= e($alt) ?>"
       class="media-slot-img <?= e($imgClass) ?>"
       loading="lazy" />
  <?php else: ?>
  <div class="media-slot-avatar font-telugu" style="background:<?= e($palette['background']) ?>;color:<?= e($palette['color']) ?>;" role="img" aria-label="<?= e($label) ?>">
    <span class="media-slot-avatar-text <?= e($avatarTextClass) ?>" aria-hidden="true"><?= e($avatarText) ?></span>
  </div>
  <?php endif; ?>
</div>
