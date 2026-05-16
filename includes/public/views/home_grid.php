<?php
/** @var list<array<string,mixed>> $catalog */
?>
<section>
  <div class="classical-hero px-6 sm:px-8 py-8 sm:py-10 mb-8">
    <p class="text-xs font-bold uppercase tracking-widest text-amber-300">ACHARYA BOOK · FREE PREVIEW</p>
    <h1 class="font-telugu text-2xl sm:text-3xl font-bold mt-3 leading-snug">మీ విజయం, మా లక్ష్యం. ఆచార్యతో సిద్ధమవ్వండి.</h1>
    <p class="text-sm text-blue-100/90 mt-3 max-w-2xl">కోర్సు కార్డ్ → సబ్ కోర్సులు, విషయాలు, టెస్టులు — అడ్మిన్ అప్‌లోడ్ చిత్రాలు ఇక్కడ ఆటోమేటిక్‌గా కనిపిస్తాయి.</p>
  </div>

  <header class="mb-6">
    <p class="text-[10px] font-bold uppercase tracking-widest text-royal">Courses</p>
    <h2 class="font-telugu text-2xl sm:text-3xl font-bold text-slate-900 mt-1">అన్ని కోర్సులు</h2>
    <p class="text-sm text-slate-600 mt-1">డేటాబేస్ నుండి లోడ్ — క్రమం అడ్మిన్ లో sort order</p>
  </header>

  <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-6" id="homeCourseGrid" data-media-scope="courses">
    <?php foreach ($catalog as $course):
        $imgPath = (string) ($course['image_path'] ?? '');
        $img = $imgPath !== '' ? public_media_url($imgPath) : '';
        $imgVer = public_media_cache_version($imgPath ?: null);
        $learnUrl = base_url('learn.php?course=' . rawurlencode($course['slug']));
    ?>
    <article class="classical-card group" data-entity-id="<?= (int) $course['id'] ?>" data-image-path="<?= e((string) ($course['image_path'] ?? '')) ?>">
      <a href="<?= e($learnUrl) ?>" class="block">
        <div class="classical-card-media">
          <?php if ($img !== ''): ?>
          <img src="<?= e($img) ?><?= $imgVer ? '?v=' . $imgVer : '' ?>" alt="<?= e($course['name']) ?>" loading="lazy" class="course-cover-img w-full h-full object-cover" />
          <?php else: ?>
          <div class="text-center p-6 text-slate-400">
            <svg class="w-12 h-12 mx-auto opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p class="text-xs mt-2 font-medium">No image</p>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-5">
          <h3 class="font-serif text-xl font-bold text-slate-900 group-hover:text-royal"><?= e($course['name']) ?></h3>
          <?php if (!empty($course['name_te'])): ?>
          <p class="font-telugu text-sm text-gold font-semibold mt-1"><?= e($course['name_te']) ?></p>
          <?php endif; ?>
          <p class="text-xs text-slate-500 mt-3"><?= count($course['programmes'] ?? []) ?> programme(s)</p>
          <span class="classical-btn-primary w-full mt-4 py-2.5 text-sm">Continue →</span>
        </div>
      </a>
    </article>
    <?php endforeach; ?>
  </div>
</section>
