<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

require_login();
$result = $_SESSION['last_result'] ?? null;
if (!$result) {
    redirect('exams.php');
}
unset($_SESSION['last_result']);

$totalQ = (int) ($result['correct'] ?? 0) + (int) ($result['wrong'] ?? 0) + (int) ($result['unanswered'] ?? 0);
$pct = $result['max_score'] > 0 ? round($result['score'] / $result['max_score'] * 100) : 0;
$accuracy = $totalQ > 0 ? round(((int) $result['correct'] / $totalQ) * 100) : 0;

$pageTitle = 'పరీక్ష ఫలితం | Acharya Books';
$activeNav = 'exams';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="max-w-2xl mx-auto px-4 py-12 font-telugu">
  <?php if (!empty($result['return_url'])): ?>
  <?php
  $backHref = (string) $result['return_url'];
  $backLabel = '← వెనుకకు / Back to Exams';
  require __DIR__ . '/includes/public/views/partials/public_back_bar.php';
  ?>
  <?php endif; ?>

  <div class="exam-scorecard bg-white border-2 border-royal rounded-2xl shadow-lg overflow-hidden">
    <header class="bg-royal text-white px-6 py-5 text-center">
      <p class="text-xs uppercase tracking-widest opacity-90">పరీక్ష ఫలితం / Scorecard</p>
      <h1 class="text-xl sm:text-2xl font-bold mt-1"><?= e((string) $result['test_title']) ?></h1>
    </header>

    <div class="p-6 sm:p-8 text-center border-b border-slate-100">
      <p class="text-6xl font-bold text-royal tabular-nums"><?= $pct ?>%</p>
      <p class="text-slate-700 mt-2 font-semibold">
        <?= e((string) $result['score']) ?> / <?= e((string) $result['max_score']) ?> మార్కులు
      </p>
      <p class="text-sm text-slate-500 mt-1">ఖచ్చితత్వం: <?= $accuracy ?>% · మొత్తం ప్రశ్నలు: <?= $totalQ ?></p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-6">
      <div class="exam-stat-pill exam-stat-pill--correct">
        <p class="exam-stat-value"><?= (int) $result['correct'] ?></p>
        <p class="exam-stat-label">సరైనవి</p>
      </div>
      <div class="exam-stat-pill exam-stat-pill--wrong">
        <p class="exam-stat-value"><?= (int) $result['wrong'] ?></p>
        <p class="exam-stat-label">తప్పు</p>
      </div>
      <div class="exam-stat-pill exam-stat-pill--skip">
        <p class="exam-stat-value"><?= (int) $result['unanswered'] ?></p>
        <p class="exam-stat-label">వదిలివేసినవి</p>
      </div>
      <div class="exam-stat-pill exam-stat-pill--time">
        <p class="exam-stat-value"><?= (int) floor($result['time_taken'] / 60) ?>:<?= str_pad((string) ((int) $result['time_taken'] % 60), 2, '0', STR_PAD_LEFT) ?></p>
        <p class="exam-stat-label">సమయం</p>
      </div>
    </div>

    <div class="px-6 pb-8 flex flex-col sm:flex-row gap-3 justify-center">
      <?php if (!empty($result['return_url'])): ?>
      <a href="<?= e((string) $result['return_url']) ?>" class="classical-btn-primary text-center font-telugu px-6 py-2.5">← వెనుకకు / Exams</a>
      <?php endif; ?>
      <a href="<?= e(base_url('dashboard.php')) ?>" class="px-6 py-2.5 border-2 border-royal text-royal font-semibold rounded-lg text-center hover:bg-royal hover:text-white transition-colors">డాష్‌బోర్డ్</a>
    </div>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
