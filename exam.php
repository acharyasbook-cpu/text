<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$user = require_login();
$userId = (int) $user['id'];

$courseSlug = $_GET['course'] ?? '';
$testSlug = $_GET['test'] ?? '';

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

$questions = $testRepo->questionsForTest((int) $test['id']);

// Submit results
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $answers = $_POST['answer'] ?? [];
    $correct = 0;
    $wrong = 0;
    $unanswered = 0;
    $score = 0.0;
    $maxScore = 0.0;

    $keyStmt = db()->prepare('SELECT id, correct_option, marks FROM test_questions WHERE test_id = ?');
    $keyStmt->execute([(int) $test['id']]);
    $answerKey = [];
    foreach ($keyStmt->fetchAll() as $row) {
        $answerKey[(int) $row['id']] = $row;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, submitted_at, status) VALUES (?, ?, NOW(), "in_progress")');
        $stmt->execute([$userId, $test['id']]);
        $attemptId = (int) $pdo->lastInsertId();

        foreach ($answerKey as $qid => $q) {
            $marks = (float) ($q['marks'] ?? 1);
            $maxScore += $marks;
            $selected = isset($answers[$qid]) && $answers[$qid] !== '' ? $answers[$qid] : null;
            $isCorrect = $selected && strtoupper((string) $selected) === (string) $q['correct_option'];

            if (!$selected) {
                $unanswered++;
            } elseif ($isCorrect) {
                $correct++;
                $score += $marks;
            } else {
                $wrong++;
                $score -= (float) $test['negative_marking'];
            }

            $ins = $pdo->prepare('INSERT INTO test_attempt_answers (attempt_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)');
            $ins->execute([$attemptId, $qid, $selected, $isCorrect ? 1 : 0]);
        }

        if ($answerKey === []) {
            $maxScore = max(1.0, (float) ($test['total_marks'] ?? 1));
        }

        $score = max(0, $score);
        $timeTaken = (int) ($_POST['time_taken'] ?? 0);

        $upd = $pdo->prepare('UPDATE test_attempts SET submitted_at=NOW(), time_taken_secs=?, score=?, max_score=?, correct_count=?, wrong_count=?, unanswered_count=?, status="submitted" WHERE id=?');
        $upd->execute([$timeTaken, $score, $maxScore, $correct, $wrong, $unanswered, $attemptId]);

        $pdo->commit();
        $returnUrl = trim((string) ($_POST['return_url'] ?? $_GET['return'] ?? ''));
        if ($returnUrl !== '' && !preg_match('#^https?://#i', $returnUrl)) {
            $returnUrl = base_url(ltrim($returnUrl, '/'));
        }
        $_SESSION['last_result'] = [
            'score' => $score,
            'max_score' => $maxScore,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'test_title' => $test['title'],
            'time_taken' => $timeTaken,
            'return_url' => $returnUrl !== '' ? $returnUrl : null,
        ];
        redirect('exam-result.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

$returnPath = trim((string) ($_GET['return'] ?? ''));
$returnUrl = '';
if ($returnPath !== '' && !preg_match('#^https?://#i', $returnPath)) {
    $returnUrl = base_url(ltrim($returnPath, '/'));
}

$pageTitle = $test['title'] . ' | Exam';
$activeNav = 'exams';
$durationSecs = (int) $test['duration_mins'] * 60;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <?php if ($returnUrl !== ''): ?>
  <?php
  $backHref = $returnUrl;
  $backLabel = '← వెనుకకు / Back to Exams';
  require __DIR__ . '/includes/public/views/partials/public_back_bar.php';
  ?>
  <?php endif; ?>
  <div class="bg-white border border-slate-200 rounded-lg shadow-sm sticky top-20 z-40 p-4 mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="font-semibold text-royal"><?= e($test['title']) ?></h1>
      <p class="text-xs text-slate-500"><?= count($questions) ?> questions · Negative: <?= e((string) $test['negative_marking']) ?></p>
    </div>
    <div class="flex items-center gap-4">
      <div class="text-center">
        <p class="text-xs text-slate-500 uppercase">Time left</p>
        <p id="timer" class="text-2xl font-bold text-royal tabular-nums">--:--</p>
      </div>
      <p class="text-sm">Q <span id="currentQ">1</span>/<?= count($questions) ?></p>
    </div>
  </div>

  <form id="examForm" method="post" class="space-y-8">
    <input type="hidden" name="submit_exam" value="1" />
    <input type="hidden" name="time_taken" id="timeTaken" value="0" />
    <?php if ($returnPath !== ''): ?>
    <input type="hidden" name="return_url" value="<?= e($returnPath) ?>" />
    <?php endif; ?>

    <?php foreach ($questions as $idx => $q): ?>
    <fieldset class="exam-question bg-white border border-slate-200 rounded-lg p-6 <?= $idx > 0 ? 'hidden' : '' ?>" data-index="<?= $idx ?>">
      <legend class="font-medium text-slate-800 mb-4">
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
          <span class="text-sm"><strong><?= $opt ?>.</strong> <?= e($q[$col]) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <?php endforeach; ?>

    <div class="flex justify-between gap-4 pb-12">
      <button type="button" id="prevBtn" class="px-5 py-2 border border-slate-300 rounded text-sm font-medium disabled:opacity-40" disabled>Previous</button>
      <button type="button" id="nextBtn" class="px-5 py-2 bg-royal text-white rounded text-sm font-semibold">Next</button>
      <button type="submit" id="submitBtn" class="hidden px-5 py-2 bg-gold text-white rounded text-sm font-semibold">Submit Exam</button>
    </div>
  </form>
</main>

<script>
(function () {
  const total = <?= count($questions) ?>;
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
    submitBtn.classList.toggle('hidden', i !== total - 1);
  }

  prevBtn.addEventListener('click', () => show(current - 1));
  nextBtn.addEventListener('click', () => show(current + 1));

  const tick = setInterval(() => {
    remaining--;
    timerEl.textContent = fmt(Math.max(0, remaining));
    if (remaining <= 0) {
      clearInterval(tick);
      document.getElementById('timeTaken').value = Math.floor((Date.now() - startTime) / 1000);
      document.getElementById('examForm').submit();
    }
  }, 1000);
  timerEl.textContent = fmt(remaining);

  document.getElementById('examForm').addEventListener('submit', function () {
    document.getElementById('timeTaken').value = Math.floor((Date.now() - startTime) / 1000);
    clearInterval(tick);
  });

  if (total === 0) {
    submitBtn.classList.remove('hidden');
    nextBtn.classList.add('hidden');
    prevBtn.classList.add('hidden');
    currentEl.textContent = '0';
  } else {
    show(0);
  }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
