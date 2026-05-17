<?php

declare(strict_types=1);

/**
 * 25-mark instant evaluation: supplies 25 MCQs when DB bank is empty and grades in-memory.
 */
final class MockExamEngine
{
    public const QUESTION_COUNT = 25;

    public const MARKS_PER_QUESTION = 1;

    /**
     * @param list<array<string,mixed>> $dbQuestions
     * @return list<array<string,mixed>>
     */
    public static function questionsForTest(array $test, array $dbQuestions): array
    {
        if (count($dbQuestions) >= self::QUESTION_COUNT) {
            return array_slice($dbQuestions, 0, self::QUESTION_COUNT);
        }

        return self::buildMockQuestions((int) ($test['id'] ?? 0), (string) ($test['title'] ?? 'పరీక్ష'));
    }

    public static function usesMockQuestions(array $questions): bool
    {
        foreach ($questions as $q) {
            if (self::isMockKey((string) ($q['id'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    public static function isMockKey(string $id): bool
    {
        return str_starts_with($id, 'mock_');
    }

    /**
     * @param array<string,string> $answers keyed by question id
     * @param list<array<string,mixed>> $questions
     * @return array<string,mixed>
     */
    public static function gradeSubmission(array $answers, array $questions, array $test, int $timeTaken): array
    {
        $correct = 0;
        $wrong = 0;
        $unanswered = 0;
        $score = 0.0;
        $maxScore = 0.0;
        $negative = (float) ($test['negative_marking'] ?? 0.25);
        $sheet = [];

        foreach ($questions as $idx => $q) {
            $qid = (string) ($q['id'] ?? '');
            $marks = (float) ($q['marks'] ?? self::MARKS_PER_QUESTION);
            $maxScore += $marks;
            $correctOpt = strtoupper((string) ($q['correct_option'] ?? 'A'));
            $selected = isset($answers[$qid]) && $answers[$qid] !== ''
                ? strtoupper((string) $answers[$qid])
                : null;

            $isCorrect = $selected !== null && $selected === $correctOpt;
            if ($selected === null) {
                $unanswered++;
            } elseif ($isCorrect) {
                $correct++;
                $score += $marks;
            } else {
                $wrong++;
                $score -= $negative;
            }

            $sheet[] = [
                'num' => $idx + 1,
                'question_text' => (string) ($q['question_text'] ?? ''),
                'question_text_te' => (string) ($q['question_text_te'] ?? ''),
                'options' => [
                    'A' => (string) ($q['option_a'] ?? ''),
                    'B' => (string) ($q['option_b'] ?? ''),
                    'C' => (string) ($q['option_c'] ?? ''),
                    'D' => (string) ($q['option_d'] ?? ''),
                ],
                'selected' => $selected,
                'correct_option' => $correctOpt,
                'is_correct' => $isCorrect,
                'unanswered' => $selected === null,
            ];
        }

        $score = max(0, $score);
        $totalQ = count($questions);

        return [
            'score' => $score,
            'max_score' => $maxScore > 0 ? $maxScore : (float) self::QUESTION_COUNT,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'total_questions' => $totalQ,
            'time_taken' => $timeTaken,
            'answer_sheet' => $sheet,
            'mock_mode' => true,
        ];
    }

    /** @return list<array<string,mixed>> */
    private static function buildMockQuestions(int $testId, string $testTitle): array
    {
        $prefix = 'mock_' . max(1, $testId) . '_';
        $topics = [
            'పెడాగాజీ', 'బాల వికాసం', 'భాషా నైపుణ్యం', 'గణితం', 'విజ్ఞానం',
            'సామాజిక అధ్యయనం', 'పర్యావరణ అధ్యయనం', 'ICT', 'సమాజ శాస్త్రం', 'ఆర్థిక శాస్త్రం',
        ];
        $out = [];
        $optionsCorrect = ['A', 'B', 'C', 'D'];

        for ($n = 1; $n <= self::QUESTION_COUNT; $n++) {
            $topic = $topics[($n - 1) % count($topics)];
            $correct = $optionsCorrect[$n % 4];
            $out[] = [
                'id' => $prefix . $n,
                'question_order' => $n,
                'question_text' => "Q{$n}. {$testTitle} — {$topic} సంబంధిత ప్రశ్న",
                'question_text_te' => "ప్రశ్న {$n}: {$topic} యూనిట్ నుండి మాక్ MCQ (25 మార్క్ ఇంస్టంట్ టెస్ట్)",
                'option_a' => 'ఎంపిక A — సరైన అవగాహన',
                'option_b' => 'ఎంపిక B — అభ్యాస ఫలితం',
                'option_c' => 'ఎంపిక C — విశ్లేషణ',
                'option_d' => 'ఎంపిక D — సమగ్రత',
                'correct_option' => $correct,
                'marks' => self::MARKS_PER_QUESTION,
            ];
        }

        return $out;
    }
}
