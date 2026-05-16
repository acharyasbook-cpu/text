<?php
/** @var string $activeNav */
/** @var bool $showMega */
$activeNav = $activeNav ?? '';
$showMega = $showMega ?? true;
$user = current_user();
require_once __DIR__ . '/public_site_helpers.php';
$logoPath = (new PlatformRepository())->logoPath();
$logoUrl = $logoPath ? public_media_url($logoPath) : null;
$logoVer = public_media_cache_version($logoPath);
$coursesMega = [];
if ($showMega) {
    try {
        $coursesMega = (new CourseRepository())->allWithSubjects();
    } catch (Throwable $e) {
        $coursesMega = [];
    }
}
?>
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b-2 border-gold/30 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16 lg:h-[4.25rem] gap-4">
      <a href="<?= e(base_url('index.php')) ?>" class="shrink-0 group flex items-center gap-3 min-w-0">
        <span class="w-11 h-11 lg:w-12 lg:h-12 rounded-full border-2 border-gold/40 bg-white flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
          <?php if ($logoUrl): ?>
          <img src="<?= e($logoUrl) ?>?v=<?= (int) $logoVer ?>" alt="" id="siteLogoImg" class="w-full h-full object-contain p-0.5" />
          <?php else: ?>
          <span class="font-telugu text-lg font-bold text-royal" id="siteLogoImg" aria-hidden="true">ఆ</span>
          <?php endif; ?>
        </span>
        <span class="min-w-0">
          <span class="font-serif text-2xl lg:text-3xl font-bold text-royal group-hover:text-royal-light transition-colors block leading-tight">Acharya Books</span>
          <span class="font-telugu hidden sm:block text-xs text-gold font-medium">మీ విజయానికి సరైన మార్గం</span>
        </span>
      </a>

      <nav class="hidden lg:flex items-center gap-1 flex-1 justify-center" aria-label="Main">
        <a href="<?= e(base_url('index.php')) ?>" class="nav-item <?= $activeNav === 'home' ? 'nav-item-active' : '' ?>">Home</a>
        <?php if ($showMega && $coursesMega): ?>
        <div class="mega-wrap relative">
          <button type="button" class="nav-item flex items-center gap-1 <?= $activeNav === 'courses' ? 'nav-item-active' : '' ?>" aria-haspopup="true">
            Courses
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div class="mega-panel absolute left-1/2 -translate-x-1/2 top-full pt-2 w-[min(100vw-2rem,56rem)]">
            <div class="bg-white border border-slate-200 rounded-lg shadow-2xl p-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
              <?php foreach ($coursesMega as $course): ?>
              <div>
                <a href="<?= e(base_url('course.php?slug=' . $course['slug'])) ?>" class="font-semibold text-royal hover:text-gold transition-colors text-sm">
                  <?= e($course['name']) ?>
                </a>
                <?php if (!empty($course['name_te'])): ?>
                  <p class="font-telugu text-xs text-gold mt-0.5"><?= e($course['name_te']) ?></p>
                <?php endif; ?>
                <ul class="mt-3 space-y-1.5">
                  <?php if (!empty($course['sub_courses'])): ?>
                  <?php foreach (array_slice($course['sub_courses'], 0, 5) as $sc): ?>
                  <li>
                    <a href="<?= e(base_url('sub_course.php?course=' . $course['slug'] . '&sub=' . $sc['slug'])) ?>"
                       class="text-xs text-slate-600 hover:text-royal block truncate">
                      <?= e($sc['name']) ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <?php foreach (array_slice($course['subjects'] ?? [], 0, 5) as $sub): ?>
                  <li>
                    <a href="<?= e(base_url('subject.php?course=' . $course['slug'] . (!empty($sub['sub_course_slug']) ? '&sub=' . $sub['sub_course_slug'] : '') . '&subject=' . $sub['slug'])) ?>"
                       class="text-xs text-slate-600 hover:text-royal block truncate">
                      <?= e($sub['name']) ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                  <?php endif; ?>
                  <li>
                    <a href="<?= e(base_url('course.php?slug=' . $course['slug'])) ?>" class="text-xs font-medium text-gold hover:underline">View all →</a>
                  </li>
                </ul>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php else: ?>
        <a href="<?= e(base_url('courses.php')) ?>" class="nav-item <?= $activeNav === 'courses' ? 'nav-item-active' : '' ?>">Courses</a>
        <?php endif; ?>
        <a href="<?= e(base_url('courses.php')) ?>" class="nav-item <?= $activeNav === 'browse' ? 'nav-item-active' : '' ?>">Browse</a>
        <a href="<?= e(base_url('dashboard.php')) ?>" class="nav-item <?= $activeNav === 'dashboard' ? 'nav-item-active' : '' ?>">Dashboard</a>
        <a href="<?= e(base_url('exams.php')) ?>" class="nav-item <?= $activeNav === 'exams' ? 'nav-item-active' : '' ?>">Exams</a>
      </nav>

      <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <?php if ($user): ?>
        <a href="<?= e(base_url('dashboard.php')) ?>" class="hidden sm:flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-full border border-slate-200 hover:border-gold/50 bg-slate-50 transition-colors">
          <span class="w-8 h-8 rounded-full bg-royal text-white flex items-center justify-center text-sm font-semibold">
            <?= e(mb_substr($user['name'], 0, 1)) ?>
          </span>
          <span class="text-sm font-medium text-slate-700 max-w-[120px] truncate"><?= e($user['name']) ?></span>
        </a>
        <a href="<?= e(base_url('logout.php')) ?>" class="text-xs text-slate-500 hover:text-royal px-2">Logout</a>
        <?php else: ?>
        <a href="<?= e(base_url('login.php')) ?>" class="px-4 py-2 text-sm font-semibold text-royal border border-royal/30 rounded hover:bg-gold-pale/50 transition-colors">Login</a>
        <a href="<?= e(base_url('login.php')) ?>" class="px-4 py-2 text-sm font-semibold text-white bg-royal rounded hover:bg-royal-light transition-colors">Student Portal</a>
        <?php endif; ?>
        <button id="mobileMenuBtn" type="button" class="lg:hidden w-10 h-10 flex items-center justify-center text-royal border border-royal/20 rounded" aria-label="Menu">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
      </div>
    </div>

    <nav id="mobileNav" class="hidden lg:hidden pb-4 border-t border-slate-100" aria-label="Mobile">
      <ul class="pt-3 space-y-1">
        <li><a href="<?= e(base_url('index.php')) ?>" class="block px-3 py-2 text-sm hover:bg-gold-pale/40 rounded">Home</a></li>
        <li><a href="<?= e(base_url('courses.php')) ?>" class="block px-3 py-2 text-sm hover:bg-gold-pale/40 rounded">Courses</a></li>
        <li><a href="<?= e(base_url('dashboard.php')) ?>" class="block px-3 py-2 text-sm hover:bg-gold-pale/40 rounded">Dashboard</a></li>
        <li><a href="<?= e(base_url('exams.php')) ?>" class="block px-3 py-2 text-sm hover:bg-gold-pale/40 rounded">Exams</a></li>
        <?php if ($user): ?>
        <li><a href="<?= e(base_url('logout.php')) ?>" class="block px-3 py-2 text-sm text-slate-500">Logout</a></li>
        <?php else: ?>
        <li><a href="<?= e(base_url('login.php')) ?>" class="block px-3 py-2 text-sm font-semibold text-royal">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
<script>
document.getElementById('mobileMenuBtn')?.addEventListener('click', function () {
  document.getElementById('mobileNav')?.classList.toggle('hidden');
});
</script>
