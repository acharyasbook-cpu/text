<?php

declare(strict_types=1);

define('EXAM_FOCUS_LAYOUT', true);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';
require_once __DIR__ . '/includes/SecureContentGuard.php';
require_once __DIR__ . '/includes/FreemiumAccess.php';
require_once __DIR__ . '/includes/MockExamEngine.php';

$user = require_login();
$userId = (int) $user['id'];

$courseSlug = (string) ($_GET['course'] ?? '');
$testSlug = (string) ($_GET['test'] ?? '');

$testRepo = new TestRepository();
$subRepo = new SubscriptionRepository();

$test = $testRepo->findBySlug($courseSlug, $testSlug);
if (!$test) {
    http_response_code(404);
    exit('Test not found');
}

if (!$subRepo->userHasTestAccess($userId, (int) $test['id'])) {
    flash('error', 'You need an active sub-course package to access this test.');
    redirect('exams.php');
}

$courseRepo = new CourseRepository();
$subject = null;
if (!empty($test['subject_id'])) {
    $st = db()->prepare(
        'SELECT s.*, c.slug AS course_slug, sc.slug AS sub_course_slug
         FROM subjects s
         JOIN courses c ON c.id = s.course_id
         LEFT JOIN sub_course_subjects scs ON scs.subject_id = s.id
         LEFT JOIN sub_courses sc ON sc.id = scs.sub_course_id
         WHERE s.id = ? LIMIT 1'
    );
    $st->execute([(int) $test['subject_id']]);
    $subject = $st->fetch() ?: null;
}
if ($subject) {
    FreemiumAccess::assertTestAccess($user, $test, $subject);
}

$dbQuestions = $testRepo->questionsForTest((int) $test['id']);
$questions = MockExamEngine::questionsForTest($test, $dbQuestions);
$mockExam = MockExamEngine::usesMockQuestions($questions);
if ($mockExam) {
    $_SESSION['mock_exam_questions'] = $questions;
}

$returnPath = trim((string) ($_GET['return'] ?? ''));
$returnUrl = '';
if ($returnPath !== '' && !preg_match('#^https?://#i', $returnPath)) {
    $returnUrl = base_url(ltrim($returnPath, '/'));
}
if ($returnUrl === '') {
    $returnUrl = base_url('exams.php');
}

$pageTitle = $test['title'] . ' | Exam';
$backHref = $returnUrl;
$backLabel = '← వెనుకకు / Back to Exams';
$unlimitedTime = (int) ($test['duration_mins'] ?? 0) <= 0;
$durationSecs = $unlimitedTime ? 0 : (int) $test['duration_mins'] * 60;
$watermarkStyle = SecureContentGuard::watermarkPatternStyle($user);
$formAction = base_url('exam.php?' . http_build_query(array_filter([
    'course' => $courseSlug,
    'test' => $testSlug,
    'return' => $returnPath !== '' ? $returnPath : null,
])));

require __DIR__ . '/includes/secure/secure_shell_start.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
  <div class="secure-viewport rounded-xl border border-[#E3E6F0] bg-white shadow-sm overflow-hidden mb-4"
       data-watermark="<?= SecureContentGuard::watermarkLabelEscaped($user) ?>"
       style="<?= $watermarkStyle ?>">
    <div class="secure-content-body">
      <div class="sticky top-[3.25rem] z-40 p-4 border-b border-slate-100 bg-white/95 backdrop-blur flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="font-semibold text-royal font-telugu"><?= e($test['title']) ?></h1>
            <p class="text-xs text-slate-500 font-telugu"><?= count($questions) ?> ప్రశ్నలు · <?= (int) ($test['total_marks'] ?? 25) ?> మార్క్ · నెగటివ్: <?= (float) ($test['negative_marking'] ?? 0) > 0 ? e((string) $test['negative_marking']) : 'లేదు' ?></p>
        </div>
        <div class="flex items-center gap-4">
          <div class="text-center">
            <p class="text-xs text-slate-500 uppercase font-telugu">సమయం</p>
            <p id="timer" class="text-2xl font-bold text-royal tabular-nums">--:--</p>
          </div>
          <p class="text-sm font-telugu">Q <span id="currentQ">1</span>/<?= count($questions) ?></p>
        </div>
      </div>

      <form id="examForm" method="post" action="<?= e($formAction) ?>" class="p-4 sm:p-6 space-y-6">
        <input type="hidden" name="submit_exam" value="1" />
        <?php if (!empty($_GET['schedule_row'])): ?>
        <input type="hidden" name="schedule_row_id" value="<?= (int) $_GET['schedule_row'] ?>" />
        <?php endif; ?>
        <?php if ($mockExam): ?>
        <input type="hidden" name="mock_exam" value="1" />
        <?php endif; ?>
        <input type="hidden" name="time_taken" id="timeTaken" value="0" />
        <?php if ($returnPath !== ''): ?>
        <input type="hidden" name="return_url" value="<?= e($returnPath) ?>" />
        <?php endif; ?>

        <?php foreach ($questions as $idx => $q): ?>
        <fieldset class="exam-question bg-white border border-slate-100 rounded-lg p-5 sm:p-6 <?= $idx > 0 ? 'hidden' : '' ?>" data-index="<?= $idx ?>">
          <legend class="font-medium text-slate-800 mb-4 font-telugu">
            <span class="text-gold font-semibold">Q<?= $idx + 1 ?>.</span>
            <?= e($q['question_text']) ?>
          </legend>
          <?php if (!empty($q['question_text_te'])): ?>
          <p class="font-telugu text-sm text-slate-600 mb-4 -mt-2"><?= e($q['question_text_te']) ?></p>
          <?php endif; ?>
          <div class="space-y-2">
            <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $opt => $col): ?>
            <label class="flex items-start gap-3 p-3 rounded border border-slate-100 hover:border-royal/30 hover:bg-slate-50 cursor-pointer has-[:checked]:border-royal has-[:checked]:bg-blue-50/50">
              <input type="radio" name="answer[<?= (int) $q['id'] ?>]" value="<?= $opt ?>" class="mt-1 text-royal focus:ring-royal" />
              <span class="text-sm font-telugu"><strong><?= $opt ?>.</strong> <?= e($q[$col]) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <?php endforeach; ?>

        <div class="flex justify-between gap-4 pb-4">
          <button type="button" id="prevBtn" class="px-5 py-2 border border-slate-300 rounded text-sm font-medium font-telugu disabled:opacity-40" disabled>మునుపటి</button>
          <button type="button" id="nextBtn" class="px-5 py-2 bg-royal text-white rounded text-sm font-semibold font-telugu">తదుపరి</button>
          <button type="submit" id="submitBtn" class="px-5 py-2 bg-gold text-white rounded text-sm font-semibold font-telugu">సబ్మిట్ / Submit</button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
(function () {
  const total = <?= count($questions) ?>;
  const unlimited = <?= $unlimitedTime ? 'true' : 'false' ?>;
  const duration = <?= $durationSecs ?>;
  let remaining = duration;
  let current = 0;
  const startTime = Date.now();
  const panels = document.querySelectorAll('.exam-question');
  const timerEl = document.getElementById('timer');
  const currentEl = document.getElementById('currentQ');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const submitBtn = document.getElementById('submitBtn');

  function fmt(s) {
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
  }

  function show(i) {
    panels.forEach((p, j) => p.classList.toggle('hidden', j !== i));
    current = i;
    currentEl.textContent = i + 1;
    prevBtn.disabled = i === 0;
    nextBtn.classList.toggle('hidden', i === total - 1);
  }

  prevBtn.addEventListener('click', () => show(current - 1));
  nextBtn.addEventListener('click', () => show(current + 1));

  let tick = null;
  if (unlimited) {
    timerEl.textContent = 'అపరిమిత';
    timerEl.classList.add('text-emerald-700', 'text-lg');
  } else {
    tick = setInterval(() => {
      remaining--;
      timerEl.textContent = fmt(Math.max(0, remaining));
      if (remaining <= 0) {
        clearInterval(tick);
        document.getElementById('timeTaken').value = Math.floor((Date.now() - startTime) / 1000);
        document.getElementById('examForm').submit();
      }
    }, 1000);
    timerEl.textContent = fmt(remaining);
  }

  document.getElementById('examForm').addEventListener('submit', function () {
    document.getElementById('timeTaken').value = Math.floor((Date.now() - startTime) / 1000);
    if (tick) clearInterval(tick);
  });

  if (total === 0) {
    nextBtn.classList.add('hidden');
    prevBtn.classList.add('hidden');
    currentEl.textContent = '0';
  } else {
    show(0);
  }
})();
</script>

<?php require __DIR__ . '/includes/secure/secure_shell_end.php'; ?>
