<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/FreemiumAccess.php';
require_once __DIR__ . '/includes/MockExamEngine.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

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
    $subjectRow = $st->fetch();
    if ($subjectRow) {
        FreemiumAccess::assertTestAccess($user, $test, $subjectRow);
    }
}

// Submit results → instant analysis dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    if (!empty($_POST['mock_exam'])) {
        $questions = $_SESSION['mock_exam_questions'] ?? [];
        if ($questions === []) {
            $questions = MockExamEngine::questionsForTest($test, []);
        }
        $answers = is_array($_POST['answer'] ?? null) ? $_POST['answer'] : [];
        $timeTaken = (int) ($_POST['time_taken'] ?? 0);
        $graded = MockExamEngine::gradeSubmission($answers, $questions, $test, $timeTaken);
        unset($_SESSION['mock_exam_questions']);

        $returnPath = trim((string) ($_POST['return_url'] ?? $_GET['return'] ?? ''));
        $returnUrl = '';
        if ($returnPath !== '' && !preg_match('#^https?://#i', $returnPath)) {
            $returnUrl = base_url(ltrim($returnPath, '/'));
        }

        $_SESSION['last_result'] = $graded + [
            'test_title' => (string) ($test['title_te'] ?: $test['title']),
            'return_url' => $returnUrl !== '' ? $returnUrl : null,
        ];
        redirect('exam-result.php');
    }

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

        $scheduleRowId = (int) ($_POST['schedule_row_id'] ?? 0);
        if ($scheduleRowId > 0 && SchemaHelper::scheduleTestManagerEnabled()) {
            require_once __DIR__ . '/models/ScheduleTestStudentService.php';
            (new ScheduleTestStudentService())->markRowComplete($userId, $scheduleRowId);
        }

        $returnUrl = trim((string) ($_POST['return_url'] ?? $_GET['return'] ?? ''));
        if ($returnUrl !== '' && !preg_match('#^https?://#i', $returnUrl)) {
            $returnUrl = base_url(ltrim($returnUrl, '/'));
        }
        $sheet = [];
        $qStmt = db()->prepare(
            'SELECT id, question_text, question_text_te, option_a, option_b, option_c, option_d, correct_option
             FROM test_questions WHERE test_id = ? ORDER BY question_order'
        );
        $qStmt->execute([(int) $test['id']]);
        $allQ = $qStmt->fetchAll();
        foreach ($allQ as $idx => $q) {
            $qid = (int) $q['id'];
            $selected = isset($answers[$qid]) && $answers[$qid] !== ''
                ? strtoupper((string) $answers[$qid])
                : null;
            $correctOpt = strtoupper((string) $q['correct_option']);
            $isCorrect = $selected !== null && $selected === $correctOpt;
            $sheet[] = [
                'num' => $idx + 1,
                'question_text' => (string) $q['question_text'],
                'question_text_te' => (string) ($q['question_text_te'] ?? ''),
                'options' => [
                    'A' => (string) $q['option_a'],
                    'B' => (string) $q['option_b'],
                    'C' => (string) $q['option_c'],
                    'D' => (string) $q['option_d'],
                ],
                'selected' => $selected,
                'correct_option' => $correctOpt,
                'is_correct' => $isCorrect,
                'unanswered' => $selected === null,
            ];
        }

        $_SESSION['last_result'] = [
            'score' => $score,
            'max_score' => $maxScore,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'test_title' => (string) ($test['title_te'] ?: $test['title']),
            'time_taken' => $timeTaken,
            'return_url' => $returnUrl !== '' ? $returnUrl : null,
            'answer_sheet' => $sheet,
            'mock_mode' => false,
        ];
        redirect('exam-result.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

$qs = array_filter([
    'course' => $courseSlug,
    'test' => $testSlug,
    'return' => trim((string) ($_GET['return'] ?? '')) ?: null,
]);
redirect('exam_running.php?' . http_build_query($qs));
