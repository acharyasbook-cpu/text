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

$npMc = $navProgramme['mc'] ?? '';
$npSc = $navProgramme['sc'] ?? '';
$isContentHub = in_array($activeView, ['content', 'schedule', 'pricing', 'whatsapp', 'analytics'], true);

function admin_nav_prog_active(?array $navProgramme, string $mc, string $sc): bool
{
    if (!$navProgramme) {
        return false;
    }

    return ($navProgramme['mc'] ?? '') === $mc && ($navProgramme['sc'] ?? '') === $sc;
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
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            adminbg: { navy: '#0c1222', panel: '#151d2e', hover: '#1e293b' },
            brand: { DEFAULT: '#2563eb', light: '#3b82f6', dark: '#1d4ed8' },
          },
          fontFamily: { sans: ['Inter','system-ui'], telugu: ['"Noto Sans Telugu"','Inter'] },
        },
      },
    };
  </script>
  <style>
    .admin-shell-sidebar { background: linear-gradient(180deg, #0c1222 0%, #151d2e 55%, #0f172a 100%); }
    .admin-shell-sidebar-light { background: #ffffff; }
    .admin-nav-link { @apply flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-slate-300 hover:bg-white/5 hover:text-white; }
    .admin-nav-link-light { @apply text-slate-700 hover:bg-slate-50 hover:text-slate-900; }
    .admin-nav-link-active { @apply bg-brand/25 text-white border-l-[3px] border-brand shadow-inner; }
    .admin-nav-link-active-light { @apply bg-slate-100 text-slate-900 border-l-[3px] border-slate-800 font-semibold; }
    .admin-submenu-link { @apply block pl-11 pr-3 py-1.5 text-[13px] rounded-lg text-slate-400 hover:text-white hover:bg-white/5; }
    .admin-submenu-link-active { @apply text-white bg-white/10 font-medium; }
    .cm-page { background: #ffffff; }
    .cm-card { background: #ffffff; border: 1px solid #E3E6F0; border-radius: 0.75rem; }
    .cm-label { color: #1e293b; font-weight: 600; }
    .cm-input { border: 1px solid #E3E6F0; color: #0f172a; background: #fff; }
    .cm-input:focus { border-color: #94a3b8; outline: none; ring: 2px; ring-color: #e2e8f0; }
  </style>
</head>
<body class="font-sans antialiased min-h-screen <?= $isContentHub ? 'bg-white text-slate-900' : 'bg-slate-100 text-slate-800' ?>">

<div class="flex min-h-screen">
  <aside id="adminSidebar" class="<?= $isContentHub ? 'admin-shell-sidebar-light border-[#E3E6F0] shadow-sm' : 'admin-shell-sidebar border-slate-800/80 shadow-xl' ?> fixed lg:static inset-y-0 left-0 z-50 w-64 lg:w-60 -translate-x-full lg:translate-x-0 transition-transform flex flex-col border-r">
    <div class="p-4 border-b <?= $isContentHub ? 'border-[#E3E6F0]' : 'border-white/5' ?>">
      <p class="<?= $isContentHub ? 'text-slate-900' : 'text-white' ?> font-bold text-lg tracking-tight leading-tight">Acharya Books</p>
      <p class="text-[11px] <?= $isContentHub ? 'text-slate-500' : 'text-slate-400' ?> mt-1 uppercase tracking-wider">Admin Panel</p>
    </div>

    <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
      <?php
      $navClass = static function (string $view) use ($activeView, $isContentHub): string {
          $base = $isContentHub ? 'admin-nav-link admin-nav-link-light' : 'admin-nav-link';
          $active = $activeView === $view
              ? ($isContentHub ? ' admin-nav-link-active-light' : ' admin-nav-link-active')
              : '';
          return $base . $active;
      };
      ?>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'overview'])) ?>" class="<?= $navClass('overview') ?>">
        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </a>

      <a href="<?= admin_e(admin_dashboard_url(['view' => 'content'])) ?>" class="<?= $navClass('content') ?>">
        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        <span class="font-telugu font-semibold">Content Manager</span>
      </a>
      <p class="px-3 pt-1 pb-0.5 text-[10px] uppercase tracking-wider <?= $isContentHub ? 'text-slate-400' : 'text-slate-500' ?>">Main → Sub → Subject → Topic</p>

      <a href="<?= admin_e(admin_dashboard_url(['view' => 'schedule'])) ?>" class="<?= $navClass('schedule') ?>">
        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="font-telugu">Schedule Test</span>
      </a>

      <a href="<?= admin_e(admin_dashboard_url(['view' => 'pricing'])) ?>" class="<?= $navClass('pricing') ?>">
        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Pricing
      </a>

      <a href="<?= admin_e(admin_dashboard_url(['view' => 'whatsapp'])) ?>" class="<?= $navClass('whatsapp') ?>">
        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        WhatsApp
      </a>

      <a href="<?= admin_e(admin_dashboard_url(['view' => 'analytics'])) ?>" class="<?= $navClass('analytics') ?>">
        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Users Analysis
      </a>

    </nav>

    <div class="p-3 border-t border-white/5 flex items-center justify-between gap-2">
      <button type="button" id="sidebarCollapseHint" class="hidden lg:flex text-slate-500 hover:text-white p-2 rounded-lg hover:bg-white/5" title="Sidebar">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
      </button>
      <a href="<?= admin_e(admin_url(admin_logout_path())) ?>" class="text-xs text-red-400 hover:text-red-300 px-2">Sign out</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="sticky top-0 z-40 px-4 sm:px-6 h-[3.25rem] flex items-center justify-between gap-4 border-b <?= $isContentHub ? 'bg-white border-[#E3E6F0] shadow-sm' : 'bg-brand shadow-md shadow-brand/20 border-transparent' ?>">
      <div class="flex items-center gap-3 min-w-0">
        <button type="button" id="sidebarToggle" class="lg:hidden p-2 rounded-lg aria-label="Menu" <?= $isContentHub ? 'text-slate-700 hover:bg-slate-100' : 'text-white/90 hover:bg-white/10' ?>">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="min-w-0">
          <p class="<?= $isContentHub ? 'text-slate-900' : 'text-white' ?> font-semibold text-sm sm:text-base truncate font-telugu">Acharya Books</p>
          <p class="text-[10px] sm:text-[11px] <?= $isContentHub ? 'text-slate-500' : 'text-blue-100' ?> truncate hidden sm:block">Admin · <?= admin_e($pageTitle) ?></p>
        </div>
      </div>

      <div class="flex items-center gap-2 sm:gap-4 shrink-0">
        <a href="#" class="p-2 rounded-lg text-white hover:bg-white/10" title="Help" aria-label="Help">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </a>
        <button type="button" class="relative p-2 rounded-lg text-white hover:bg-white/10" title="Notifications" aria-label="Notifications">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span class="absolute top-1 right-1 min-w-[1rem] h-4 px-1 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center">0</span>
        </button>

        <details class="relative group">
          <summary class="list-none cursor-pointer flex items-center gap-2 rounded-lg pl-1 pr-2 py-1 hover:bg-white/10 text-white [&::-webkit-details-marker]:hidden">
            <span class="w-8 h-8 rounded-full bg-white/20 border border-white/30 text-white flex items-center justify-center text-sm font-bold">
              <?= admin_e(function_exists('mb_substr') ? mb_substr((string) ($admin['name'] ?? 'A'), 0, 1) : substr((string) ($admin['name'] ?? 'A'), 0, 1)) ?>
            </span>
            <span class="text-sm font-medium hidden sm:inline max-w-[100px] truncate">admin</span>
            <svg class="w-4 h-4 opacity-80 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </summary>
          <div class="absolute right-0 mt-2 w-52 rounded-xl bg-white border border-slate-200 shadow-xl py-2 text-sm text-slate-700 z-50">
            <p class="px-4 py-2 border-b border-slate-100 text-xs text-slate-500 truncate"><?= admin_e($admin['email'] ?? '') ?></p>
            <a href="<?= admin_e(admin_site_url('index.php')) ?>" target="_blank" class="block px-4 py-2 hover:bg-slate-50">View site ↗</a>
            <a href="<?= admin_e(admin_url(admin_logout_path())) ?>" class="block px-4 py-2 text-red-600 hover:bg-red-50">Sign out</a>
          </div>
        </details>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden <?= $isContentHub ? 'cm-page bg-white' : '' ?>">
      <?php if ($success): ?>
        <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg"><?= admin_e($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg"><?= admin_e($error) ?></div>
      <?php endif; ?>
