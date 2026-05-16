<?php
/** @var list<array{icon:string,label_te:string,label_en:string,value:string|int}> $badges */
/** @var list<array{key:string,label:string}>|null $tierStrip */
?>
<section class="programme-features mb-8" aria-label="Programme highlights">
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
    <?php foreach ($badges as $badge): ?>
    <div class="programme-feature-badge">
      <span class="programme-feature-icon" aria-hidden="true"><?= e((string) ($badge['icon'] ?? '◆')) ?></span>
      <p class="font-telugu text-2xl font-bold text-royal leading-none mt-2"><?= e((string) ($badge['value'])) ?></p>
      <p class="font-telugu text-sm font-semibold text-slate-800 mt-1 leading-snug"><?= e((string) ($badge['label_te'])) ?></p>
      <p class="text-[10px] uppercase tracking-wide text-slate-500 mt-0.5 font-sans"><?= e((string) ($badge['label_en'])) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($tierStrip)): ?>
  <div class="programme-tier-strip mt-4">
    <p class="font-telugu text-xs font-semibold text-slate-500 mb-2">పరీక్షా నమూనా · 5 టైర్లు</p>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($tierStrip as $tier): ?>
      <span class="programme-tier-pill font-telugu"><?= e((string) ($tier['label'] ?? '')) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
