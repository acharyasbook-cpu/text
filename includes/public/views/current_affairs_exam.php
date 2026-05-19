<?php
/** @var list<array<string,mixed>> $questions */
/** @var string $examDate */
/** @var string $formAction */
/** @var int $examDurationSec */
require_once dirname(__DIR__, 3) . '/includes/public_site_helpers.php';
$user = current_user();
$formAction = $formAction ?? base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($examDate));
$examDurationSec = $examDurationSec ?? 25 * 60;
require dirname(__DIR__, 2) . '/includes/secure/secure_shell_start.php';
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/current-affairs-cbt.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/current-affairs-cbt.css') ?>" />

<main class="ca-cbt-exam max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 font-telugu">
  <div class="rounded-xl border border-[#E3E6F0] bg-white shadow-sm overflow-hidden mb-4">
    <div class="sticky top-[3.25rem] z-40 p-4 border-b border-slate-100 bg-white/95 backdrop-blur flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="font-semibold text-royal">డైలీ కరెంట్ అఫైర్స్ · CBT</h1>
        <p class="text-xs text-slate-500"><?= e(date('d M Y', strtotime($examDate))) ?> · <?= count($questions) ?> ప్రశ్నలు · 25 మార్క్</p>
      </div>
      <div class="flex items-center gap-4">
        <p class="text-sm">Q <span id="currentQ">1</span>/<?= count($questions) ?></p>
        <div class="ca-exam-timer" id="examTimer" aria-live="polite">
          <span class="ca-exam-timer__label">సమయం</span>
          <span class="ca-exam-timer__value" id="timerDisplay">25:00</span>
        </div>
      </div>
    </div>

    <form id="examForm" method="post" action="<?= e($formAction) ?>" class="p-4 sm:p-6 space-y-6">
      <input type="hidden" name="submit_ca_exam" value="1" />
      <input type="hidden" name="time_taken" id="timeTaken" value="0" />
      <?= csrf_field() ?>

      <?php foreach ($questions as $idx => $q):
          $qid = (string) ($q['id'] ?? '');
      ?>
      <fieldset class="exam-question bg-white border border-slate-100 rounded-lg p-5 sm:p-6 <?= $idx > 0 ? 'hidden' : '' ?>" data-index="<?= $idx ?>">
        <legend class="font-medium text-slate-800 mb-4">
          <span class="text-gold font-semibold">Q<?= $idx + 1 ?>.</span>
          <?= e((string) $q['question_text']) ?>
        </legend>
        <div class="space-y-2">
          <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $opt => $col): ?>
          <label class="flex items-start gap-3 p-3 rounded border border-slate-100 hover:border-royal/30 cursor-pointer">
            <input type="radio" name="answer[<?= e($qid) ?>]" value="<?= $opt ?>" class="mt-1 text-royal" />
            <span class="text-sm"><strong><?= $opt ?>.</strong> <?= e((string) $q[$col]) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <?php endforeach; ?>

      <div class="flex flex-wrap justify-between gap-4 pb-4">
        <button type="button" id="prevBtn" class="px-5 py-2 border rounded text-sm" disabled>మునుపటి</button>
        <button type="button" id="nextBtn" class="px-5 py-2 bg-royal text-white rounded text-sm">తదుపరి</button>
        <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-gold text-white rounded text-sm font-bold">Submit Exam</button>
      </div>
    </form>
  </div>
</main>
<script>
(function () {
  const total = <?= count($questions) ?>;
  const durationSec = <?= (int) $examDurationSec ?>;
  let current = 0;
  let remaining = durationSec;
  const startTime = Date.now();
  const panels = document.querySelectorAll('.exam-question');
  const currentEl = document.getElementById('currentQ');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const timerEl = document.getElementById('timerDisplay');
  const form = document.getElementById('examForm');
  let submitted = false;

  function pad(n) { return n < 10 ? '0' + n : String(n); }
  function renderTimer() {
    const m = Math.floor(remaining / 60);
    const s = remaining % 60;
    timerEl.textContent = pad(m) + ':' + pad(s);
    if (remaining <= 60) timerEl.parentElement.classList.add('ca-exam-timer--urgent');
  }
  function show(i) {
    panels.forEach((p, j) => p.classList.toggle('hidden', j !== i));
    current = i;
    currentEl.textContent = i + 1;
    prevBtn.disabled = i === 0;
    nextBtn.classList.toggle('hidden', i === total - 1);
  }
  function autoSubmit() {
    if (submitted) return;
    submitted = true;
    document.getElementById('timeTaken').value = durationSec;
    form.submit();
  }
  const tick = setInterval(function () {
    remaining -= 1;
    renderTimer();
    if (remaining <= 0) {
      clearInterval(tick);
      autoSubmit();
    }
  }, 1000);
  renderTimer();
  prevBtn.addEventListener('click', () => show(current - 1));
  nextBtn.addEventListener('click', () => show(current + 1));
  form.addEventListener('submit', function () {
    if (submitted) return;
    submitted = true;
    clearInterval(tick);
    document.getElementById('timeTaken').value = Math.min(
      durationSec,
      Math.floor((Date.now() - startTime) / 1000)
    );
  });
  if (total > 0) show(0);
})();
</script>
<?php require dirname(__DIR__, 2) . '/includes/secure/secure_shell_end.php'; ?>
