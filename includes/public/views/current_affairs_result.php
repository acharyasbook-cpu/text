<?php
/** @var array<string,mixed> $result */
require_once dirname(__DIR__, 3) . '/includes/public_site_helpers.php';
$user = current_user();
$backHref = $backHref ?? base_url(ca_exam_environment_script());
$backLabel = '← CBT పరిసరం';
$totalQ = (int) ($result['total_questions'] ?? 0);
$pct = ($result['max_score'] ?? 0) > 0
    ? round((float) $result['score'] / (float) $result['max_score'] * 100)
    : 0;
$sheet = $result['answer_sheet'] ?? [];
require dirname(__DIR__, 2) . '/includes/secure/secure_shell_start.php';
?>
<main class="max-w-3xl mx-auto px-4 py-8 font-telugu">
  <div class="bg-white border-2 border-royal rounded-2xl shadow-lg overflow-hidden mb-8">
    <header class="bg-royal text-white px-6 py-5 text-center">
      <p class="text-xs uppercase tracking-widest opacity-90">డైలీ కరెంట్ అఫైర్స్</p>
      <h1 class="text-xl font-bold mt-1"><?= e((string) ($result['test_title'] ?? 'పరీక్ష')) ?></h1>
    </header>
    <div class="p-8 text-center border-b border-slate-100">
      <p class="text-5xl font-bold text-royal"><?= $pct ?>%</p>
      <p class="mt-2 font-semibold"><?= e((string) $result['score']) ?> / <?= e((string) $result['max_score']) ?> మార్కులు</p>
    </div>
    <div class="grid grid-cols-3 gap-3 p-6 text-center text-sm">
      <div><p class="text-2xl font-bold text-emerald-700"><?= (int) ($result['correct'] ?? 0) ?></p><p>సరైనవి</p></div>
      <div><p class="text-2xl font-bold text-red-700"><?= (int) ($result['wrong'] ?? 0) ?></p><p>తప్పు</p></div>
      <div><p class="text-2xl font-bold text-slate-600"><?= (int) ($result['unanswered'] ?? 0) ?></p><p>వదిలివేసినవి</p></div>
    </div>
  </div>

  <?php if ($sheet !== []): ?>
  <section class="space-y-3">
    <h2 class="font-bold text-slate-900">జవాబు షీట్</h2>
    <?php foreach ($sheet as $row): ?>
    <article class="p-4 rounded-lg border <?= !empty($row['is_correct']) ? 'border-emerald-200 bg-emerald-50/50' : (!empty($row['unanswered']) ? 'border-slate-200' : 'border-red-200 bg-red-50/40') ?>">
      <p class="font-semibold text-sm mb-2">Q<?= (int) ($row['num'] ?? 0) ?>. <?= e((string) ($row['question_text'] ?? '')) ?></p>
      <p class="text-xs text-slate-600">మీ జవాబు: <?= e((string) ($row['selected'] ?? '—')) ?> · సరైనది: <?= e((string) ($row['correct_option'] ?? '')) ?></p>
    </article>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <a href="<?= e($backHref) ?>" class="classical-btn-primary inline-flex mt-8 px-6 py-3"><?= e($backLabel) ?></a>
</main>
<?php require dirname(__DIR__, 2) . '/includes/secure/secure_shell_end.php'; ?>
