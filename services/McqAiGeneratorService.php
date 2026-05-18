<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AiProviderService.php';
require_once dirname(__DIR__) . '/services/McqDifficultyProfiles.php';
require_once dirname(__DIR__) . '/models/QuestionsStagingRepository.php';
require_once dirname(__DIR__) . '/models/McqGenerationJobRepository.php';
require_once dirname(__DIR__) . '/models/PdfSegmentRepository.php';
require_once dirname(__DIR__) . '/models/SchemaHelper.php';

final class McqAiGeneratorService
{
    /** @var array<string,array{bilingual:bool,languages:list<string>}> */
    public const SUBJECT_PROFILES = [
        'Telugu' => ['bilingual' => false, 'languages' => ['telugu']],
        'English' => ['bilingual' => false, 'languages' => ['english']],
        'Hindi' => ['bilingual' => false, 'languages' => ['hindi']],
        'Maths' => ['bilingual' => true, 'languages' => ['english', 'telugu']],
        'Mathematics' => ['bilingual' => true, 'languages' => ['english', 'telugu']],
        'Science' => ['bilingual' => true, 'languages' => ['english', 'telugu']],
        'Social Studies' => ['bilingual' => true, 'languages' => ['english', 'telugu']],
        'General' => ['bilingual' => true, 'languages' => ['english', 'telugu']],
    ];

    public function __construct(
        private AiProviderService $ai = new AiProviderService(),
        private QuestionsStagingRepository $staging = new QuestionsStagingRepository(),
        private McqGenerationJobRepository $jobs = new McqGenerationJobRepository(),
        private PdfSegmentRepository $segments = new PdfSegmentRepository(),
    ) {
    }

    /**
     * @param array{provider:string,model_name:string,api_key:string} $slot
     * @return array{ok:bool,page:int,total:int,inserted:int,batches:int,status:string,error?:string}
     */
    public function processPageChunk(int $jobId, array $slot): array
    {
        $job = $this->jobs->find($jobId);
        if (!$job) {
            return $this->fail(0, 0, 0, 'Job not found');
        }
        $segment = $this->segments->find((int) $job['segment_id']);
        if (!$segment) {
            return $this->fail(0, 0, 0, 'Segment not found');
        }

        $total = (int) $job['total_pages'];
        $nextPage = (int) $job['current_page'] + 1;
        if ($nextPage > $total) {
            return ['ok' => true, 'page' => $total, 'total' => $total, 'inserted' => 0, 'batches' => 0, 'status' => 'completed'];
        }

        $this->jobs->markProcessing($jobId);
        $absPage = (int) $segment['start_page'] + $nextPage - 1;
        if ($absPage > (int) $segment['end_page']) {
            $this->jobs->advancePage($jobId, $total);

            return ['ok' => true, 'page' => $total, 'total' => $total, 'inserted' => 0, 'batches' => 0, 'status' => 'completed'];
        }

        try {
            $sourceText = $this->extractPageText($segment, $absPage);
            $requested = McqDifficultyProfiles::normalizeQuestionCount((int) ($job['questions_per_page'] ?? 3));
            $batchSizes = McqDifficultyProfiles::batchSizes($requested);
            $inserted = 0;
            $batchNum = 0;
            $seen = [];

            foreach ($batchSizes as $batchCount) {
                $batchNum++;
                $system = $this->buildSystemPrompt($job, $segment);
                $user = $this->buildUserPrompt($job, $segment, $absPage, $sourceText, $batchCount, $batchNum, count($batchSizes));
                $raw = $this->ai->complete($slot, $system, $user);
                $valid = $this->filterValidRows($this->parseMcqJson($raw), $seen);
                $subject = (string) ($job['subject_key'] ?? $segment['assigned_subject']);
                $bilingual = $this->isBilingualSubject($subject);
                $scale = McqDifficultyProfiles::normalize((string) ($job['difficulty_scale'] ?? 'SGT'));

                foreach ($valid as $norm) {
                    if ($inserted >= $requested) {
                        break 2;
                    }

                    $this->staging->insertRaw([
                        'segment_id' => (int) $segment['id'],
                        'job_id' => $jobId,
                        'page_number' => $absPage,
                        'subject_key' => $subject,
                        'difficulty_scale' => $scale,
                        'question_text' => $norm['question_text'],
                        'question_text_te' => $norm['question_text_te'],
                        'option_a' => $norm['option_a'],
                        'option_b' => $norm['option_b'],
                        'option_c' => $norm['option_c'],
                        'option_d' => $norm['option_d'],
                        'option_a_te' => $norm['option_a_te'],
                        'option_b_te' => $norm['option_b_te'],
                        'option_c_te' => $norm['option_c_te'],
                        'option_d_te' => $norm['option_d_te'],
                        'correct_option' => $norm['correct_option'],
                        'bilingual_layout' => $bilingual ? 1 : 0,
                        'metadata' => [
                            'page' => $absPage,
                            'topic' => $segment['topic_name'],
                            'batch' => $batchNum,
                        ],
                    ]);
                    $inserted++;
                }
            }

            $this->jobs->advancePage($jobId, $nextPage);
            $status = $nextPage >= $total ? 'completed' : 'processing';

            return [
                'ok' => true,
                'page' => $nextPage,
                'total' => $total,
                'inserted' => $inserted,
                'batches' => $batchNum,
                'requested' => $requested,
                'status' => $status,
            ];
        } catch (Throwable $e) {
            $this->jobs->advancePage($jobId, $nextPage, $e->getMessage());

            return $this->fail($nextPage, $total, 0, $e->getMessage());
        }
    }

    /** @return array{ok:bool,page:int,total:int,inserted:int,batches:int,status:string,error?:string} */
    private function fail(int $page, int $total, int $inserted, string $error): array
    {
        return [
            'ok' => false,
            'page' => $page,
            'total' => $total,
            'inserted' => $inserted,
            'batches' => 0,
            'status' => 'failed',
            'error' => $error,
        ];
    }

    /** @param array<string,mixed> $segment */
    private function extractPageText(array $segment, int $page): string
    {
        $path = (string) ($segment['storage_path'] ?? '');
        if ($path !== '' && is_readable($path)) {
            $cmd = sprintf('pdftotext -f %d -l %d %s - 2>/dev/null', $page, $page, escapeshellarg($path));
            if (function_exists('exec')) {
                $out = [];
                exec($cmd, $out);
                $text = trim(implode("\n", $out));
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return sprintf(
            'Textbook: %s | Topic: %s | Page %d (attach PDF for full text extraction)',
            $segment['pdf_name'] ?? '',
            $segment['topic_name'] ?? '',
            $page
        );
    }

    /** @param array<string,mixed> $job @param array<string,mixed> $segment */
    private function buildSystemPrompt(array $job, array $segment): string
    {
        $scale = McqDifficultyProfiles::normalize((string) ($job['difficulty_scale'] ?? 'SGT'));
        $subject = (string) ($job['subject_key'] ?? 'General');
        $bilingual = $this->isBilingualSubject($subject);
        $tier = McqDifficultyProfiles::systemInstruction($scale);

        return 'You are an expert Indian competitive exam MCQ author for Andhra Pradesh and Telangana DSC/TET pipelines. '
            . "Subject domain: {$subject}. Difficulty tier: {$scale}. {$tier} "
            . 'Every item must be complete: full question stem, four distinct non-empty options (A–D), exactly one correct key. '
            . 'Never output placeholders like "Option A", "TBD", or duplicate stems. Never truncate mid-sentence. '
            . ($bilingual
                ? 'Bilingual layout: English on top, Telugu on bottom for question and each option. Use LaTeX for formulas: \\( ... \\). '
                : 'Use the subject language only. ')
            . 'Return ONLY a valid JSON array of objects with keys: '
            . ($bilingual
                ? 'question_en, question_te, option_a_en, option_a_te, option_b_en, option_b_te, option_c_en, option_c_te, option_d_en, option_d_te, correct'
                : 'question_text, option_a, option_b, option_c, option_d, correct')
            . '. Topic: ' . ($segment['topic_name'] ?? '');
    }

    /**
     * @param array<string,mixed> $job
     * @param array<string,mixed> $segment
     */
    private function buildUserPrompt(
        array $job,
        array $segment,
        int $page,
        string $sourceText,
        int $batchCount,
        int $batchIndex,
        int $totalBatches,
    ): string {
        $mapping = $job['excel_mapping'] ?? null;
        if (is_string($mapping)) {
            $mapping = json_decode($mapping, true);
        }
        $mapHint = is_array($mapping) ? "\nExcel column mapping: " . json_encode($mapping) : '';
        $courseHint = '';
        if (!empty($segment['sub_course_id']) && SchemaHelper::hasTable('sub_courses')) {
            $st = db()->prepare('SELECT sc.name, sc.name_te, c.name AS course_name FROM sub_courses sc JOIN courses c ON c.id = sc.course_id WHERE sc.id=? LIMIT 1');
            $st->execute([(int) $segment['sub_course_id']]);
            if ($row = $st->fetch()) {
                $courseHint = "\nMain programme: " . ($row['course_name'] ?? '') . ' — ' . ($row['name_te'] ?? $row['name'] ?? '');
            }
        }

        return "Generate exactly {$batchCount} unique MCQs (batch {$batchIndex} of {$totalBatches} for this page).\n"
            . "PDF: {$segment['pdf_name']} | Lesson: {$segment['topic_name']} | Page {$page}\n"
            . $courseHint . $mapHint
            . "\nDo not repeat questions from other batches. Vary cognitive skills across items.\n\nCONTENT:\n"
            . mb_substr($sourceText, 0, 12000);
    }

    /** @return list<array<string,mixed>> */
    private function parseMcqJson(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/\[[\s\S]*\]/', $raw, $m)) {
            $raw = $m[0];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        if (isset($decoded['questions']) && is_array($decoded['questions'])) {
            return $decoded['questions'];
        }

        return array_is_list($decoded) ? $decoded : [];
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @param array<string,true> $seen
     * @return list<array<string,mixed>>
     */
    private function filterValidRows(array $rows, array &$seen): array
    {
        $out = [];
        foreach ($rows as $row) {
            $norm = $this->normalizeRow($row);
            if (!$this->rowIsComplete($norm)) {
                continue;
            }
            $fp = $this->fingerprint($norm);
            if (isset($seen[$fp])) {
                continue;
            }
            $seen[$fp] = true;
            $out[] = $norm;
        }

        return $out;
    }

    /** @param array<string,mixed> $row */
    private function normalizeRow(array $row): array
    {
        return [
            'question_text' => trim((string) ($row['question_en'] ?? $row['question_text'] ?? '')),
            'question_text_te' => trim((string) ($row['question_te'] ?? $row['question_text_te'] ?? '')) ?: null,
            'option_a' => trim((string) ($row['option_a_en'] ?? $row['option_a'] ?? '')),
            'option_b' => trim((string) ($row['option_b_en'] ?? $row['option_b'] ?? '')),
            'option_c' => trim((string) ($row['option_c_en'] ?? $row['option_c'] ?? '')),
            'option_d' => trim((string) ($row['option_d_en'] ?? $row['option_d'] ?? '')),
            'option_a_te' => trim((string) ($row['option_a_te'] ?? '')) ?: null,
            'option_b_te' => trim((string) ($row['option_b_te'] ?? '')) ?: null,
            'option_c_te' => trim((string) ($row['option_c_te'] ?? '')) ?: null,
            'option_d_te' => trim((string) ($row['option_d_te'] ?? '')) ?: null,
            'correct_option' => strtoupper(substr((string) ($row['correct'] ?? $row['correct_option'] ?? 'A'), 0, 1)),
        ];
    }

    /** @param array<string,mixed> $norm */
    private function rowIsComplete(array $norm): bool
    {
        if (mb_strlen($norm['question_text']) < 12) {
            return false;
        }
        foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $k) {
            if (mb_strlen($norm[$k]) < 1) {
                return false;
            }
            if (preg_match('/^(option\s*[a-d]|tbd|\.\.\.)$/i', $norm[$k])) {
                return false;
            }
        }
        if (!in_array($norm['correct_option'], ['A', 'B', 'C', 'D'], true)) {
            return false;
        }
        $opts = [$norm['option_a'], $norm['option_b'], $norm['option_c'], $norm['option_d']];
        if (count(array_unique(array_map('mb_strtolower', $opts))) < 4) {
            return false;
        }

        return true;
    }

    /** @param array<string,mixed> $norm */
    private function fingerprint(array $norm): string
    {
        return hash('sha256', mb_strtolower($norm['question_text']));
    }

    private function isBilingualSubject(string $subject): bool
    {
        foreach (self::SUBJECT_PROFILES as $key => $profile) {
            if (strcasecmp($key, $subject) === 0) {
                return !empty($profile['bilingual']);
            }
        }

        return true;
    }
}
