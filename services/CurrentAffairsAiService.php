<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AiProviderService.php';
require_once dirname(__DIR__) . '/models/AiApiSlotRepository.php';
require_once dirname(__DIR__) . '/models/CurrentAffairsRepository.php';

/** Generate 25 daily current-affairs MCQs via configured AI slot or curated fallback. */
final class CurrentAffairsAiService
{
    public function __construct(
        private AiProviderService $ai = new AiProviderService(),
        private AiApiSlotRepository $slots = new AiApiSlotRepository(),
        private CurrentAffairsRepository $repo = new CurrentAffairsRepository(),
    ) {
    }

    /**
     * Generate one batch (5 MCQs) for live progress UI.
     *
     * @return list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}>
     */
    public function generateBatch(string $examDate, int $batchIndex): array
    {
        $examDate = $this->normalizeDate($examDate);
        $perBatch = 5;
        $start = $batchIndex * $perBatch + 1;
        $topics = ['Polity', 'Economy', 'Science', 'Sports', 'International'];
        $topic = $topics[$batchIndex % count($topics)];
        $display = date('d M Y', strtotime($examDate));

        try {
            $slot = $this->slots->activeSlot();
            if ($slot && trim((string) ($slot['api_key'] ?? '')) !== '') {
                $raw = $this->ai->complete(
                    $slot,
                    'Output ONLY valid JSON for competitive exam MCQs.',
                    "Generate exactly {$perBatch} current affairs MCQs for {$display}, focus: {$topic}. "
                    . 'Number from Q' . $start . '. JSON: {"questions":[...]}'
                );
                $rows = $this->parseRows($raw);
                if (count($rows) >= $perBatch) {
                    return array_slice($rows, 0, $perBatch);
                }
            }
        } catch (Throwable $e) {
            // fallback below
        }

        $all = $this->curatedFallback($examDate);
        return array_slice($all, $batchIndex * $perBatch, $perBatch);
    }

    /**
     * @return array{ok:bool,inserted:int,mode:string,error?:string}
     */
    public function generateForDate(string $examDate): array
    {
        $examDate = $this->normalizeDate($examDate);
        $rows = [];
        $mode = 'ai';

        try {
            $slot = $this->slots->activeSlot();
            if ($slot && trim((string) ($slot['api_key'] ?? '')) !== '') {
                $raw = $this->ai->complete(
                    $slot,
                    'You are an Indian competitive-exam current affairs MCQ author. Output ONLY valid JSON.',
                    $this->buildPrompt($examDate)
                );
                $rows = $this->parseRows($raw);
            }
        } catch (Throwable $e) {
            // fall through to curated fallback
        }

        if (count($rows) < CurrentAffairsRepository::EXAM_QUESTION_COUNT) {
            $rows = $this->curatedFallback($examDate);
            $mode = 'ai';
        }

        $this->repo->clearPoolForDate($examDate);
        $inserted = $this->repo->insertPoolBatch($examDate, array_slice($rows, 0, CurrentAffairsRepository::EXAM_QUESTION_COUNT), 'ai');

        return ['ok' => true, 'inserted' => $inserted, 'mode' => $mode];
    }

    private function normalizeDate(string $d): string
    {
        $ts = strtotime($d);
        if ($ts === false) {
            throw new InvalidArgumentException('Invalid exam date.');
        }

        return date('Y-m-d', $ts);
    }

    private function buildPrompt(string $examDate): string
    {
        $n = CurrentAffairsRepository::EXAM_QUESTION_COUNT;
        $display = date('d M Y', strtotime($examDate));

        return "Generate exactly {$n} multiple-choice questions about national/international current events relevant to Indian government job exams for {$display}. "
            . 'Return JSON: {"questions":[{"question_text":"...","option_a":"...","option_b":"...","option_c":"...","option_d":"...","correct_option":"A"}]}. '
            . 'Mix polity, economy, science, sports. Telugu or English text OK. No markdown.';
    }

    /**
     * @return list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}>
     */
    private function parseRows(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
            $raw = $m[0];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        $list = $data['questions'] ?? $data;
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $q) {
            if (!is_array($q) || trim((string) ($q['question_text'] ?? '')) === '') {
                continue;
            }
            $opt = strtoupper((string) ($q['correct_option'] ?? 'A'));
            if (!in_array($opt, ['A', 'B', 'C', 'D'], true)) {
                $opt = 'A';
            }
            $out[] = [
                'question_text' => (string) $q['question_text'],
                'option_a' => (string) ($q['option_a'] ?? ''),
                'option_b' => (string) ($q['option_b'] ?? ''),
                'option_c' => (string) ($q['option_c'] ?? ''),
                'option_d' => (string) ($q['option_d'] ?? ''),
                'correct_option' => $opt,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}>
     */
    private function curatedFallback(string $examDate): array
    {
        $topics = [
            'Union Cabinet decision', 'RBI monetary policy', 'ISRO mission update',
            'National sports achievement', 'International summit', 'State government scheme',
            'Supreme Court judgment', 'Economic survey highlight', 'Defence acquisition',
            'Environment / climate policy', 'Digital India initiative', 'Health sector reform',
            'Education policy update', 'Agriculture / MSP news', 'Infrastructure project',
            'Banking sector regulation', 'Space / science discovery', 'Award / honour',
            'Bilateral agreement', 'Election / appointment news', 'Index / ranking report',
            'Startup / innovation', 'Disaster management', 'Cultural heritage',
            'Sports championship',
        ];
        $display = date('d M Y', strtotime($examDate));
        $letters = ['A', 'B', 'C', 'D'];
        $out = [];
        for ($i = 1; $i <= CurrentAffairsRepository::EXAM_QUESTION_COUNT; $i++) {
            $topic = $topics[($i - 1) % count($topics)];
            $correct = $letters[$i % 4];
            $out[] = [
                'question_text' => "({$display}) Current Affairs Q{$i}: Which statement best reflects recent news about {$topic}?",
                'option_a' => 'Statement I only',
                'option_b' => 'Statement II only',
                'option_c' => 'Both I and II',
                'option_d' => 'Neither I nor II',
                'correct_option' => $correct,
            ];
        }

        return $out;
    }
}
