<?php
/**
 * Minimal secure layout (notes / exam) — logo + back only; no main site nav.
 *
 * @var string $pageTitle
 * @var array<string,mixed>|null $user
 * @var string $backHref
 * @var string $backLabel
 */
require_once dirname(__DIR__) . '/SecureContentGuard.php';
require_once dirname(__DIR__) . '/public_site_helpers.php';
require_once dirname(__DIR__, 2) . '/models/PlatformRepository.php';

SecureContentGuard::registerAssets();

$pageTitle = $pageTitle ?? 'Acharya Books';
$backHref = $backHref ?? base_url('index.php');
$backLabel = $backLabel ?? '← వెనుకకు';
$user = $user ?? current_user();
$watermarkStyle = SecureContentGuard::watermarkPatternStyle($user);

$logoPath = (new PlatformRepository())->logoPath();
$logoUrl = $logoPath ? public_media_url($logoPath) : null;
$logoVer = public_media_cache_version($logoPath);
$examFocus = defined('EXAM_FOCUS_LAYOUT') && EXAM_FOCUS_LAYOUT;
?>
<!DOCTYPE html>
<html lang="te" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title><?= e($pageTitle) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= e(base_url('assets/css/classical.css')) ?>" />
  <link rel="stylesheet" href="<?= e(base_url('assets/css/secure-content.css')) ?>" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            royal: { DEFAULT: '#1e3a8a', light: '#2f4fa8' },
            gold: { DEFAULT: '#b8860b' },
          },
          fontFamily: {
            telugu: ['"Noto Sans Telugu"', 'Inter', 'sans-serif'],
            serif: ['"Cormorant Garamond"', 'Georgia', 'serif'],
          },
        },
      },
    };
  </script>
</head>
<body class="secure-content-page font-sans text-slate-900 antialiased<?= $examFocus ? ' exam-focus-page' : '' ?>">
<header class="secure-topbar exam-focus-topbar" role="banner">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
    <a href="<?= e($backHref) ?>" class="exam-focus-back font-telugu text-sm font-semibold text-royal hover:underline shrink-0 min-w-[7rem]">
      <?= e($backLabel) ?>
    </a>
    <a href="<?= e(base_url('index.php')) ?>" class="exam-focus-brand flex items-center justify-center gap-2 min-w-0 flex-1" title="Acharya Books">
      <span class="w-9 h-9 rounded-full border border-gold/40 bg-white flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
        <?php if ($logoUrl): ?>
        <img src="<?= e($logoUrl) ?>?v=<?= (int) $logoVer ?>" alt="" class="w-full h-full object-contain p-0.5" />
        <?php else: ?>
        <span class="font-telugu text-base font-bold text-royal" aria-hidden="true">ఆ</span>
        <?php endif; ?>
      </span>
      <span class="font-serif text-lg sm:text-xl font-bold text-royal truncate hidden xs:inline sm:inline">Acharya Books</span>
    </a>
    <span class="exam-focus-badge text-[10px] text-slate-400 shrink-0 w-[7rem] text-right hidden sm:inline" aria-hidden="true">🔒 Focus</span>
  </div>
</header>
