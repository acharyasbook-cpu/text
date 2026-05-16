<?php
/** @var array $header @var string $pageTitle @var string $view */
$pageTitle = $pageTitle ?? 'Acharya Books';
$header = $header ?? (new HeaderController())->build('home', null);
$catalog = $header['catalog'];
$activeNav = $header['active_nav'];
$activeCourseSlug = $header['active_course_slug'];
$user = $header['user'];
?>
<!DOCTYPE html>
<html lang="te" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= e(base_url('assets/css/classical.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/assets/css/classical.css') ?>" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            royal: { DEFAULT: '#1e3a8a', light: '#2f4fa8', dark: '#0f172a' },
            gold: { DEFAULT: '#b8860b', light: '#d4a843', pale: '#f5ecd4' },
            cream: { DEFAULT: '#faf8f5', panel: '#fffefb' },
          },
          fontFamily: {
            serif: ['"Cormorant Garamond"', 'Georgia', 'serif'],
            sans: ['Inter', 'system-ui', 'sans-serif'],
            telugu: ['"Noto Sans Telugu"', 'Inter', 'sans-serif'],
          },
        },
      },
    };
  </script>
</head>
<body class="font-sans text-slate-900 bg-white antialiased min-h-screen classical-site">

<header class="classical-topbar border-b border-[#E3E6F0] bg-white sticky top-0 z-50">
  <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center justify-between gap-4">
    <a href="<?= e(base_url('index.php')) ?>" class="flex items-center gap-3 group min-w-0">
      <span class="classical-logo-ring shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full border-2 border-royal/30 bg-white flex items-center justify-center overflow-hidden shadow-sm">
        <?php if (!empty($header['logo_url'])): ?>
        <img src="<?= e($header['logo_url']) ?>?v=<?= e((string) time()) ?>" alt="" class="w-full h-full object-cover" id="siteLogoImg" />
        <?php else: ?>
        <span class="text-lg font-bold text-royal">ఆ</span>
        <?php endif; ?>
      </span>
      <span class="min-w-0">
        <span class="block font-telugu text-xl sm:text-2xl font-bold text-slate-900 leading-tight group-hover:text-royal transition-colors"><?= e($header['site_name_te']) ?></span>
        <span class="block text-[11px] sm:text-xs text-slate-600 font-telugu"><?= e($header['site_tagline_te']) ?></span>
        <span class="hidden sm:block text-[10px] text-slate-400 mt-0.5">Learn · Practice · Excel</span>
      </span>
    </a>
    <div class="flex items-center gap-2 sm:gap-3">
      <?php if ($user): ?>
      <a href="<?= e(base_url('dashboard.php')) ?>" class="text-sm font-semibold text-royal hover:underline hidden sm:inline"><?= e($user['name']) ?></a>
      <a href="<?= e(base_url('logout.php')) ?>" class="classical-btn-outline text-xs sm:text-sm">Logout</a>
      <?php else: ?>
      <a href="<?= e(base_url('register.php')) ?>" class="hidden sm:inline-flex text-sm font-semibold text-slate-700 hover:text-royal px-3 py-2">Register</a>
      <a href="<?= e(base_url('login.php')) ?>" class="classical-btn-primary text-sm px-5 py-2">Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<nav class="classical-navbar bg-[#0c0c0c] text-white border-b border-black/20" aria-label="Main courses">
  <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
    <ul class="flex flex-wrap items-center gap-0 overflow-x-auto py-0.5 scrollbar-thin">
      <li>
        <a href="<?= e(base_url('index.php')) ?>" class="classical-nav-link <?= $activeNav === 'home' ? 'is-active' : '' ?>">Home</a>
      </li>
      <?php foreach ($catalog as $c): ?>
      <li>
        <a href="<?= e(base_url('learn.php?course=' . rawurlencode($c['slug']))) ?>"
           class="classical-nav-link <?= ($activeNav === $c['slug'] || $activeCourseSlug === $c['slug']) ? 'is-active' : '' ?>">
          <?= e($c['name']) ?>
        </a>
      </li>
      <?php endforeach; ?>
      <li><a href="<?= e(base_url('courses.php')) ?>" class="classical-nav-link">Digital Books</a></li>
    </ul>
  </div>
</nav>

<div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 flex flex-col lg:flex-row gap-6 lg:gap-8">
  <aside class="lg:w-56 shrink-0">
    <div class="classical-sidebar bg-cream border border-[#E3E6F0] rounded-xl p-4 sticky top-36">
      <p class="text-[10px] font-bold uppercase tracking-widest text-royal mb-2">Courses</p>
      <p class="font-telugu text-sm font-bold text-slate-900 mb-3 leading-snug">డిజిటల్ లైబ్రరీ (Books)</p>
      <ul class="space-y-0.5 text-sm">
        <?php foreach ($catalog as $c): ?>
        <li>
          <a href="<?= e(base_url('learn.php?course=' . rawurlencode($c['slug']))) ?>"
             class="classical-side-link block px-3 py-2 rounded-lg font-medium <?= ($activeCourseSlug === $c['slug']) ? 'is-active' : '' ?>">
            <?= e($c['name']) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </aside>

  <main class="flex-1 min-w-0 classical-main">
