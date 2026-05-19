<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/public_site_helpers.php';
require_once dirname(__DIR__) . '/includes/CurrentAffairsAccess.php';
require_once dirname(__DIR__) . '/includes/CurrentAffairsExamEngine.php';
require_once dirname(__DIR__) . '/includes/CurrentAffairsDummyData.php';
require_once dirname(__DIR__) . '/models/CurrentAffairsRepository.php';

final class CurrentAffairsController
{
    public function __construct(
        private CurrentAffairsAccess $access = new CurrentAffairsAccess(),
        private CurrentAffairsRepository $repo = new CurrentAffairsRepository(),
    ) {
    }

    public function hub(): void
    {
        redirect(ca_exam_environment_script());
    }

    public function gateway(): void
    {
        redirect(ca_exam_environment_script());
    }

    /** Dedicated CBT exam environment — student only, never admin. */
    public function examEnvironment(): void
    {
        if (!empty($_SESSION['admin']) && empty($_SESSION['user'])) {
            flash('info', 'విద్యార్థి గా లాగిన్ అవ్వండి లేదా లాగౌట్ చేసి ప్రయత్నించండి.');
            redirect('login.php?return=' . rawurlencode(ca_exam_environment_script()));
        }
        $user = require_login();
        if (!empty($_SESSION['admin']) && ($user['role'] ?? '') === 'admin') {
            unset($_SESSION['admin']);
        }
        $userId = (int) $user['id'];
        $ctx = $this->access->hubContext($userId);
        $ctx['archive_months'] = $this->buildArchiveMonths($userId, $ctx);
        $monthDays = [];
        foreach ($ctx['archive_months'] as $m) {
            $ym = (string) ($m['ym'] ?? '');
            $demoDates = CurrentAffairsDummyData::demoDatesInMonth($ym);
            if ($ctx['premium'] && $this->access->isModuleReady()) {
                $dbDates = $this->repo->datesInMonth($ym, $ctx['retention_cutoff'], $ctx['today']);
                $monthDays[$ym] = array_values(array_unique(array_merge($dbDates, $demoDates)));
                rsort($monthDays[$ym]);
            } else {
                $monthDays[$ym] = $demoDates;
            }
        }
        $ctx['today_exam_url'] = base_url(
            ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($ctx['today'])
        );
        $pageTitle = 'డైలీ కరెంట్ అఫైర్స్ · CBT | Acharya Books';
        $backHref = base_url('index.php');
        $backLabel = '← హోమ్';
        require dirname(__DIR__) . '/includes/public/views/exam_environment.php';
    }

    /**
     * @param array<string,mixed> $ctx
     * @return list<array{ym:string,label:string,count:int}>
     */
    private function buildArchiveMonths(int $userId, array $ctx): array
    {
        $premium = !empty($ctx['premium']);
        $dbMonths = $premium && $this->access->isModuleReady()
            ? $this->repo->monthsWithExams($ctx['retention_cutoff'], $ctx['today'])
            : [];
        $demo = CurrentAffairsDummyData::demoArchiveMonths(12);
        $byYm = [];
        foreach (array_merge($demo, $dbMonths) as $m) {
            $byYm[$m['ym']] = $m;
        }
        krsort($byYm);

        return array_values($byYm);
    }

    public function exam(): void
    {
        $user = require_login();
        $userId = (int) $user['id'];
        $examDate = $this->normalizeDateParam((string) ($_GET['date'] ?? $this->repo->todayDate()));

        if (!$this->access->canAccessDate($userId, $examDate)) {
            flash('error', 'ఈ తేదీ పరీక్షకు మీకు అనుమతి లేదు.');
            redirect(ca_exam_environment_script());
        }
        if (!$this->access->canStartExam($userId, $examDate)) {
            flash('error', 'ఈ రోజు పరీక్ష మీరు ఇప్పటికే ఒకసారి పూర్తి చేశారు. Premium తో అపరిమిత ప్రయత్నాలు.');
            redirect(ca_exam_environment_script());
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ca_exam'])) {
            $this->submitExam($userId, $examDate);
        }

        $useDummy = !$this->access->isModuleReady()
            || !$this->repo->dateHasExam($examDate);
        $questions = $useDummy
            ? CurrentAffairsDummyData::questions()
            : $this->repo->pickExamQuestions($examDate);
        if ($questions === []) {
            $questions = CurrentAffairsDummyData::questions();
            $useDummy = true;
        }

        $_SESSION['ca_exam'] = [
            'exam_date' => $examDate,
            'dummy' => $useDummy,
            'question_ids' => array_map(
                static fn ($q) => (string) ($q['id'] ?? ''),
                $questions
            ),
        ];

        $pageTitle = 'Current Affairs Exam | ' . date('d M Y', strtotime($examDate));
        $backHref = base_url(ca_exam_environment_script());
        $backLabel = '← CBT పరిసరం';
        $examDurationSec = CurrentAffairsDummyData::EXAM_DURATION_SEC;
        $formAction = base_url(
            ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($examDate)
        );
        require dirname(__DIR__) . '/includes/public/views/current_affairs_exam.php';
    }

    private function submitExam(int $userId, string $examDate): void
    {
        $session = $_SESSION['ca_exam'] ?? null;
        if (!$session || ($session['exam_date'] ?? '') !== $examDate) {
            flash('error', 'పరీక్ష సెషన్ గడువు ముగిసింది. మళ్ళీ ప్రారంభించండి.');
            redirect(ca_exam_environment_script());
        }
        $allowedIds = array_flip($session['question_ids'] ?? []);
        if (!empty($session['dummy'])) {
            $questions = array_values(array_filter(
                CurrentAffairsDummyData::questions(),
                static fn ($q) => isset($allowedIds[(string) ($q['id'] ?? '')])
            ));
        } else {
            $pool = $this->repo->poolForDate($examDate);
            $questions = array_values(array_filter(
                $pool,
                static fn ($q) => isset($allowedIds[(string) ($q['id'] ?? '')])
            ));
        }
        if (count($questions) < CurrentAffairsRepository::EXAM_QUESTION_COUNT) {
            flash('error', 'ప్రశ్నల బ్యాంక్ మారింది. మళ్ళీ ప్రారంభించండి.');
            redirect(ca_exam_environment_script());
        }
        $answers = is_array($_POST['answer'] ?? null) ? $_POST['answer'] : [];
        $timeTaken = (int) ($_POST['time_taken'] ?? 0);
        $graded = CurrentAffairsExamEngine::grade($questions, $answers, $timeTaken, $examDate);
        if ($this->access->isModuleReady()) {
            $this->repo->recordAttempt($userId, $examDate);
        }
        unset($_SESSION['ca_exam']);
        $_SESSION['ca_last_result'] = $graded;
        redirect(ca_exam_environment_script() . '?action=result');
    }

    public function result(): void
    {
        require_login();
        $result = $_SESSION['ca_last_result'] ?? null;
        if (!$result) {
            redirect(ca_exam_environment_script());
        }
        unset($_SESSION['ca_last_result']);
        $pageTitle = 'Current Affairs Result';
        $backHref = base_url(ca_exam_environment_script());
        require dirname(__DIR__) . '/includes/public/views/current_affairs_result.php';
    }

    private function normalizeDateParam(string $d): string
    {
        $ts = strtotime($d);

        return $ts !== false ? date('Y-m-d', $ts) : $this->repo->todayDate();
    }
}
