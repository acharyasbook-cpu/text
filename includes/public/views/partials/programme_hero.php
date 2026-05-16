<?php
/**
 * @var string $titleEn
 * @var string $titleTe
 * @var string $eyebrow
 * @var string $description
 * @var string $bannerPath
 * @var string $bannerAlt
 */
$titleEn = $titleEn ?? '';
$titleTe = $titleTe ?? '';
$eyebrow = $eyebrow ?? '';
$description = $description ?? '';
$bannerPath = trim((string) ($bannerPath ?? ''));
$bannerAlt = (string) ($bannerAlt ?? $titleEn);
$imgUrl = $bannerPath !== '' ? acharya_media_url($bannerPath) : '';
$imgVer = $bannerPath !== '' ? public_media_cache_version($bannerPath) : 0;
$displayTitle = $titleTe !== '' ? $titleTe : $titleEn;
?>
<header class="programme-hero border border-[#E3E6F0] rounded-xl bg-white overflow-hidden mb-8 shadow-sm">
  <div class="grid lg:grid-cols-2 gap-0">
    <div class="p-6 lg:p-10 flex flex-col justify-center">
      <?php if ($eyebrow !== ''): ?>
      <p class="text-[10px] font-bold uppercase tracking-widest text-gold font-sans"><?= e($eyebrow) ?></p>
      <?php endif; ?>
      <h1 class="font-serif text-3xl lg:text-4xl font-bold text-slate-900 mt-2 leading-tight"><?= e($titleEn) ?></h1>
      <?php if ($titleTe !== ''): ?>
      <p class="font-telugu text-xl text-gold font-semibold mt-2 leading-snug"><?= e($titleTe) ?></p>
      <?php endif; ?>
      <?php if ($description !== ''): ?>
      <p class="font-telugu text-sm text-slate-600 mt-4 leading-relaxed max-w-xl"><?= e($description) ?></p>
      <?php endif; ?>
    </div>
    <div class="programme-hero-banner relative min-h-[220px] lg:min-h-full bg-slate-50 border-t lg:border-t-0 lg:border-l border-[#E3E6F0]"
         data-media-scope="programme_banner"
         data-image-path="<?= e($bannerPath) ?>">
      <?php if ($imgUrl !== ''): ?>
      <img src="<?= e($imgUrl) ?><?= $imgVer > 0 ? '?v=' . $imgVer : '' ?>"
           alt="<?= e($bannerAlt) ?>"
           class="programme-banner-img w-full h-full min-h-[220px] lg:min-h-[280px]"
           loading="eager" />
      <?php else: ?>
      <div class="programme-banner-placeholder w-full h-full min-h-[220px] lg:min-h-[280px] flex items-center justify-center p-8">
        <span class="font-telugu font-bold text-xl text-slate-600 text-center leading-snug"><?= e($displayTitle) ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
</header>
