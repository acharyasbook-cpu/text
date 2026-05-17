<?php

declare(strict_types=1);

define('EXAM_FOCUS_LAYOUT', true);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';
require_once __DIR__ . '/includes/SecureContentGuard.php';

$user = require_login();
$result = $_SESSION['last_result'] ?? null;
if (!$result) {
    redirect('exams.php');
}
unset($_SESSION['last_result']);

$totalQ = (int) ($result['total_questions'] ?? 0);
if ($totalQ < 1) {
    $totalQ = (int) ($result['correct'] ?? 0) + (int) ($result['wrong'] ?? 0) + (int) ($result['unanswered'] ?? 0);
}
$pct = ($result['max_score'] ?? 0) > 0
    ? round((float) $result['score'] / (float) $result['max_score'] * 100)
    : 0;
$accuracy = $totalQ > 0 ? round(((int) $result['correct'] / $totalQ) * 100) : 0;
$sheet = $result['answer_sheet'] ?? [];
$returnUrl = (string) ($result['return_url'] ?? '');

$pageTitle = 'పరీక్ష విశ్లేషణ | Acharya Books';
$backHref = $returnUrl !== '' ? $returnUrl : base_url('exams.php');
$backLabel = '← వెనుకకు / ఎగ్జామ్ జాబితా';

require __DIR__ . '/includes/secure/secure_shell_start.php';
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/subject-workspace.css')) ?>?v=<?= (int) @filemtime(__DIR__ . '/assets/css/subject-workspace.css') ?>" />

<main class="max-w-3xl mx-auto px-4 py-6 sm:py-10 font-telugu">
  <div class="exam-scorecard bg-white border-2 border-royal rounded-2xl shadow-lg overflow-hidden mb-8">
    <header class="bg-royal text-white px-6 py-5 text-center">
      <p class="text-xs uppercase tracking-widest opacity-90">ఇన్‌స్టంట్ పర్ఫార్మెన్స్ విశ్లేషణ</p>
      <h1 class="text-xl sm:text-2xl font-bold mt-1"><?= e((string) ($result['test_title'] ?? 'పరీక్ష')) ?></h1>
      <p class="text-sm opacity-90 mt-1">25 మార్క్ మాతృక · <?= $totalQ ?> ప్రశ్నలు</p>
    </header>

    <div class="p-6 sm:p-8 text-center border-b border-slate-100">
      <p class="text-6xl font-bold text-royal tabular-nums"><?= $pct ?>%</p>
      <p class="text-slate-700 mt-2 font-semibold">
        <?= e((string) $result['score']) ?> / <?= e((string) $result['max_score']) ?> మార్కులు
      </p>
      <p class="text-sm text-slate-500 mt-1">ఖచ్చితత్వం: <?= $accuracy ?>%</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-6 border-b border-slate-100">
      <div class="exam-stat-pill exam-stat-pill--correct">
        <p class="exam-stat-value"><?= (int) ($result['correct'] ?? 0) ?></p>
        <p class="exam-stat-label">సరైనవి ✓</p>
      </div>
      <div class="exam-stat-pill exam-stat-pill--wrong">
        <p class="exam-stat-value"><?= (int) ($result['wrong'] ?? 0) ?></p>
        <p class="exam-stat-label">తప్పు ✗</p>
      </div>
      <div class="exam-stat-pill exam-stat-pill--skip">
        <p class="exam-stat-value"><?= (int) ($result['unanswered'] ?? 0) ?></p>
        <p class="exam-stat-label">వదిలివేసినవి</p>
      </div>
      <div class="exam-stat-pill exam-stat-pill--time">
        <p class="exam-stat-value"><?= (int) floor(((int) ($result['time_taken'] ?? 0)) / 60) ?>:<?= str_pad((string) (((int) ($result['time_taken'] ?? 0)) % 60), 2, '0', STR_PAD_LEFT) ?></p>
        <p class="exam-stat-label">సమయం</p>
      </div>
    </div>
  </div>

  <?php if ($sheet !== []): ?>
  <section class="exam-analysis-sheet" aria-label="Answer sheet">
    <h2 class="text-lg font-bold text-slate-900 mb-4">జవాబు షీట్ — సరైన / తప్పు విశ్లేషణ</h2>
    <p class="text-xs text-slate-600 mb-4">
      <span class="inline-block px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold">పచ్చ</span> = సరైన సమాధానం ·
      <span class="inline-block px-2 py-0.5 rounded bg-red-100 text-red-800 font-semibold ml-1">ఎరుపు</span> = మీ తప్పు ప్రయత్నం
    </p>
    <?php foreach ($sheet as $row):
        $cls = !empty($row['unanswered']) ? 'is-skip' : (!empty($row['is_correct']) ? 'is-correct' : 'is-wrong');
        $opts = $row['options'] ?? [];
        $correctKey = (string) ($row['correct_option'] ?? '');
        $selected = $row['selected'] ?? null;
    ?>
    <article class="exam-analysis-q <?= e($cls) ?>">
      <p class="font-bold text-slate-900 mb-1">
        Q<?= (int) ($row['num'] ?? 0) ?>.
        <?= e((string) ($row['question_text_te'] ?: $row['question_text'] ?? '')) ?>
      </p>
      <?php foreach (['A', 'B', 'C', 'D'] as $opt):
          if (empty($opts[$opt])) {
              continue;
          }
          $optCls = 'exam-opt';
          if ($opt === $correctKey) {
              $optCls .= ' exam-opt--correct-key';
          }
          if ($selected === $opt && $opt !== $correctKey) {
              $optCls .= ' exam-opt--wrong-pick';
          }
      ?>
      <span class="<?= e($optCls) ?>">
        <strong><?= $opt ?>.</strong> <?= e((string) $opts[$opt]) ?>
        <?php if ($opt === $correctKey): ?> ✓ సరైనది<?php endif; ?>
        <?php if ($selected === $opt && $opt !== $correctKey): ?> ✗ మీ జవాబు<?php endif; ?>
      </span>
      <?php endforeach; ?>
      <?php if (!empty($row['unanswered'])): ?>
      <p class="text-xs text-slate-500 mt-2">వదిలివేయబడింది — సరైన సమాధానం: <strong><?= e($correctKey) ?></strong></p>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if ($returnUrl !== ''): ?>
  <p class="mt-10 text-center">
    <a href="<?= e($returnUrl) ?>" class="classical-btn-primary inline-block font-telugu px-8 py-2.5">← వెనుకకు / ఎగ్జామ్ జాబితా</a>
  </p>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/secure/secure_shell_end.php'; ?>
