<?php
/** @var string $pageTitle */
/** @var string $bodyClass */
$pageTitle = $pageTitle ?? 'Acharya Books';
$bodyClass = $bodyClass ?? 'font-sans text-slate-700 bg-slate-50 antialiased min-h-screen';
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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            royal: { DEFAULT: '#1e3a8a', light: '#2f4fa8', dark: '#152a63' },
            gold: { DEFAULT: '#b8860b', light: '#d4a843', pale: '#f5ecd4' },
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
  <link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>" />
</head>
<body class="<?= e($bodyClass) ?>">
