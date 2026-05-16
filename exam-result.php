<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

require_login();
$result = $_SESSION['last_result'] ?? null;
if (!$result) {
    redirect('exams.php');
}
unset($_SESSION['last_result']);

$pct = $result['max_score'] > 0 ? round($result['score'] / $result['max_score'] * 100) : 0;

$pageTitle = 'Exam Result | Acharya Books';
$activeNav = 'exams';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="max-w-lg mx-auto px-4 py-16">
  <div class="bg-white border border-slate-200 rounded-lg shadow-lg p-8 text-center">
    <p class="text-sm font-semibold uppercase tracking-wider text-gold">Instant Results</p>
    <h1 class="font-serif text-2xl font-bold text-royal mt-2"><?= e($result['test_title']) ?></h1>

    <div class="my-8">
      <p class="text-5xl font-bold text-royal"><?= $pct ?>%</p>
      <p class="text-slate-600 mt-2"><?= e((string) $result['score']) ?> / <?= e((string) $result['max_score']) ?> marks</p>
    </div>

    <div class="grid grid-cols-3 gap-4 text-sm">
      <div class="p-3 bg-green-50 rounded"><p class="font-bold text-green-800"><?= (int) $result['correct'] ?></p><p class="text-green-700">Correct</p></div>
      <div class="p-3 bg-red-50 rounded"><p class="font-bold text-red-800"><?= (int) $result['wrong'] ?></p><p class="text-red-700">Wrong</p></div>
      <div class="p-3 bg-slate-50 rounded"><p class="font-bold text-slate-800"><?= (int) $result['unanswered'] ?></p><p class="text-slate-600">Skipped</p></div>
    </div>

    <p class="text-xs text-slate-500 mt-6">Time taken: <?= (int) floor($result['time_taken'] / 60) ?>m <?= (int) ($result['time_taken'] % 60) ?>s</p>

    <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
      <?php if (!empty($result['return_url'])): ?>
      <a href="<?= e($result['return_url']) ?>" class="px-6 py-2.5 bg-royal text-white font-semibold rounded hover:bg-royal-light font-telugu">← వెనుకకు / Back</a>
      <?php endif; ?>
      <a href="<?= e(base_url('dashboard.php')) ?>" class="px-6 py-2.5 bg-royal text-white font-semibold rounded hover:bg-royal-light">View Dashboard</a>
      <a href="<?= e(base_url('exams.php')) ?>" class="px-6 py-2.5 border border-royal text-royal font-semibold rounded hover:bg-gold-pale/50">More Exams</a>
    </div>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
