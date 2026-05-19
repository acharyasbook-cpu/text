<?php
/** @var string $pageTitle */
/** @var string $activeView */
/** @var array{mc:string,sc:string}|null $navProgramme */
$admin = require_admin();
$pageTitle = $pageTitle ?? 'Admin | Acharya Books';
$activeView = $activeView ?? 'overview';
$navProgramme = $navProgramme ?? null;
$success = admin_flash('success');
$error = admin_flash('error');

$adminCss = admin_site_url('assets/css/admin-premium.css');
$caCss = admin_site_url('assets/css/current-affairs-cbt.css');

function admin_nav_active(string $view, string $activeView): string
{
    return $activeView === $view ? ' is-active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= admin_e(admin_csrf_token()) ?>" />
  <title><?= admin_e($pageTitle) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= admin_e($adminCss) ?>?v=2" />
  <link rel="stylesheet" href="<?= admin_e($caCss) ?>?v=1" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            admin: { bg: '#F8F9FA', primary: '#4F46E5', 'primary-dark': '#4338CA' },
          },
          fontFamily: { sans: ['Inter','system-ui'], telugu: ['"Noto Sans Telugu"','Inter'] },
        },
      },
    };
  </script>
  <style>
    .subject-image-picker { margin-top: 0.25rem; }
    .subject-image-hitbox, .cm-cover-hitbox {
      position: relative; min-height: 9rem; border: 1px dashed #cbd5e1; border-radius: 0.75rem;
      overflow: hidden; background: #f8fafc;
    }
    .subject-image-hitbox.is-clickable, .cm-cover-hitbox.is-clickable {
      cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .subject-image-hitbox.is-clickable:hover, .cm-cover-hitbox.is-clickable:hover {
      border-color: #94a3b8; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    }
    .subject-image-preview, .cm-cover-preview { width: 100%; height: 100%; min-height: 9rem; object-fit: cover; display: block; }
    .subject-image-avatar, .cm-cover-avatar {
      min-height: 9rem; display: flex; flex-direction: column; align-items: center;
      justify-content: center; padding: 1rem; text-align: center; gap: 0.35rem;
    }
    .subject-image-avatar-initials, .cm-cover-avatar-initials { font-size: 1.75rem; font-weight: 800; }
    .subject-image-uploader.hidden, .cm-cover-form-wrap.hidden { display: none; }
  </style>
</head>
<body class="admin-premium-shell font-sans antialiased min-h-screen">

<div class="flex min-h-screen">
  <aside id="adminSidebar" class="admin-sidebar fixed lg:static inset-y-0 left-0 z-50 w-64 lg:w-60 -translate-x-full lg:translate-x-0 transition-transform flex flex-col">
    <div class="admin-sidebar-brand p-4">
      <p class="text-lg font-bold text-slate-900 tracking-tight">Acharya Books</p>
      <p class="text-[11px] text-slate-500 mt-0.5 uppercase tracking-wider font-semibold">Enterprise Admin</p>
    </div>

    <nav class="flex-1 px-3 pb-3 space-y-0.5 overflow-y-auto">
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'home_banner'])) ?>" class="admin-nav-link<?= admin_nav_active('home_banner', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a2 2 0 012-2h12a2 2 0 012 2v2H4V5zm0 4h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9z"/></svg>
        <span class="font-telugu">హోమ్ బ్యానర్</span>
      </a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'overview'])) ?>" class="admin-nav-link<?= admin_nav_active('overview', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </a>

      <p class="admin-nav-section font-telugu">కంటెంట్ &amp; టెస్ట్</p>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'content'])) ?>" class="admin-nav-link<?= admin_nav_active('content', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        <span class="font-telugu">Content Manager</span>
      </a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'current_affairs'])) ?>" class="admin-nav-link<?= admin_nav_active('current_affairs', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        <span class="font-telugu">Current Affairs</span>
      </a>
      <a href="<?= admin_e(admin_url('schedule_test.php')) ?>" class="admin-nav-link<?= admin_nav_active('schedule', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="font-telugu">Schedule Test</span>
      </a>

      <p class="admin-nav-section font-telugu">వ్యాపారం &amp; విద్యార్థులు</p>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'pricing'])) ?>" class="admin-nav-link<?= admin_nav_active('pricing', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-telugu">Pricing &amp; Plans</span>
      </a>
      <a href="<?= admin_e(admin_url('coupon_manager.php')) ?>" class="admin-nav-link<?= admin_nav_active('coupons', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
        <span class="font-telugu">కూపన్‌లు</span>
      </a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'whatsapp'])) ?>" class="admin-nav-link<?= admin_nav_active('whatsapp', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        WhatsApp Hub
      </a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'analytics'])) ?>" class="admin-nav-link<?= admin_nav_active('analytics', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span class="font-telugu">Users Analysis</span>
      </a>

      <p class="admin-nav-section">Catalog</p>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses'])) ?>" class="admin-nav-link<?= admin_nav_active('courses', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        Courses &amp; Subjects
      </a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'exams'])) ?>" class="admin-nav-link<?= admin_nav_active('exams', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Exam Manager
      </a>
      <a href="<?= admin_e(admin_url('mcq_generator/')) ?>" class="admin-nav-link<?= admin_nav_active('mcq_generator', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        <span class="font-telugu">AI MCQ Engine</span>
      </a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'students'])) ?>" class="admin-nav-link<?= admin_nav_active('students', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Students
      </a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'hierarchy'])) ?>" class="admin-nav-link<?= admin_nav_active('hierarchy', $activeView) ?>">
        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
        Live / Draft
      </a>
    </nav>

    <div class="p-3 border-t border-slate-100 flex items-center justify-between gap-2">
      <a href="<?= admin_e(admin_url(admin_logout_path())) ?>" class="text-xs font-semibold text-red-600 hover:text-red-700 px-2">Sign out</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="admin-topbar sticky top-0 z-40 px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3 min-w-0">
        <button type="button" id="sidebarToggle" class="lg:hidden p-2 rounded-lg text-slate-700 hover:bg-slate-100" aria-label="Menu">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="min-w-0">
          <p class="font-semibold text-sm text-slate-900 truncate"><?= admin_e($pageTitle) ?></p>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <a href="<?= admin_e(admin_site_url('index.php')) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn-ghost text-xs hidden sm:inline-flex">View site ↗</a>
        <details class="relative">
          <summary class="list-none cursor-pointer flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
            <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
              <?= admin_e(function_exists('mb_substr') ? mb_substr((string) ($admin['name'] ?? 'A'), 0, 1) : substr((string) ($admin['name'] ?? 'A'), 0, 1)) ?>
            </span>
          </summary>
          <div class="absolute right-0 mt-2 w-52 rounded-xl bg-white border border-slate-200 shadow-lg py-2 text-sm text-slate-700 z-50">
            <p class="px-4 py-2 border-b border-slate-100 text-xs text-slate-500 truncate"><?= admin_e($admin['email'] ?? '') ?></p>
            <a href="<?= admin_e(admin_site_url('index.php')) ?>" target="_blank" class="block px-4 py-2 hover:bg-slate-50">View site ↗</a>
            <a href="<?= admin_e(admin_url(admin_logout_path())) ?>" class="block px-4 py-2 text-red-600 hover:bg-red-50">Sign out</a>
          </div>
        </details>
      </div>
    </header>

    <main class="admin-main flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden max-w-7xl w-full mx-auto">
      <?php if ($success): ?>
        <div class="admin-alert-success mb-4"><?= admin_e($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="admin-alert-error mb-4"><?= admin_e($error) ?></div>
      <?php endif; ?>
